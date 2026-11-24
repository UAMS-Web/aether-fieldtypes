<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Statamic\Auth\File\User as FileUser;
use Statamic\Facades\User;
use Symfony\Component\HttpFoundation\Response;
use UAMSWeb\Aether\Fieldtypes\Contracts\EgressGuard;
use UAMSWeb\Aether\Fieldtypes\Http\Controllers\NominatimSearchController;
use UAMSWeb\Aether\Fieldtypes\Support\DefaultEgressGuard;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\RecordingEgressGuard;

/**
 * The GPS picker's server-side address-search proxy: Control Panel auth, result normalization,
 * the identifying request, the rate limit, and the guard seam the outbound fetch goes through.
 *
 * @command  vendor/bin/pest tests/Integration/Http/NominatimSearchControllerTest.php
 */
covers(NominatimSearchController::class);

const NOMINATIM_RATE_LIMITER_KEY = 'maps:nominatim:outbound';

beforeEach(function (): void {
    // Pin a known, policy-shaped config so assertions are deterministic
    config()->set('maps.nominatim.endpoint', 'https://nominatim.openstreetmap.org/search');
    config()->set('maps.nominatim.min_query_length', 3);
    config()->set('maps.nominatim.max_requests_per_second', 1);
    config()->set('maps.nominatim.user_agent', 'Aether Test Agent');

    // Reset the application-wide limiter so each test starts with a fresh allowance
    RateLimiter::clear(NOMINATIM_RATE_LIMITER_KEY);
});

afterEach(function (): void {
    RateLimiter::clear(NOMINATIM_RATE_LIMITER_KEY);
});

/**
 * Authenticate as a super user, who may reach the Control Panel.
 */
function nominatimActAsUser(): void
{
    $user = mustBe(FileUser::class, User::make(), 'user');
    $user->email('nominatim-search@example.test');
    $user->makeSuper();

    test()->actingAs($user);
}

/**
 * Bind a recording guard and authenticate, returning the guard.
 *
 * @param  mixed  $body  The canned JSON body
 */
function nominatimGuard(mixed $body = [], bool $allow = true, int $status = 200, bool $throw = false): RecordingEgressGuard
{
    $guard = new RecordingEgressGuard($allow, $body, $status, $throw);
    app()->instance(EgressGuard::class, $guard);
    nominatimActAsUser();

    return $guard;
}

/**
 * Search through the Control Panel route.
 *
 * @param  array<string, string>  $query  The query string
 * @return TestResponse<Response> The response
 */
function nominatimSearch(array $query = ['q' => 'Little Rock']): TestResponse
{
    /** @var string $url */
    $url = cp_route('gps.nominatim-search', $query);

    return test()->getJson($url);
}

it('rejects a guest before any outbound request', function (): void {
    $guard = new RecordingEgressGuard;
    app()->instance(EgressGuard::class, $guard);

    nominatimSearch()->assertUnauthorized();

    expect($guard->asked)->toBe([])->and($guard->fetched)->toBe([]);
});

it('geocodes an address into normalized coordinate results', function (): void {
    nominatimGuard([
        ['lat' => '34.7492719', 'lon' => '-92.3198281', 'display_name' => 'UAMS, 4301 W Markham St, Little Rock, AR'],
        ['lat' => '34.7465', 'lon' => '-92.2896', 'display_name' => 'Little Rock, Pulaski County, AR'],
    ]);

    nominatimSearch(['q' => '4301 W Markham St, Little Rock'])
        ->assertOk()
        ->assertExactJson([
            'results' => [
                ['lat' => 34.7492719, 'lng' => -92.3198281, 'label' => 'UAMS, 4301 W Markham St, Little Rock, AR'],
                ['lat' => 34.7465, 'lng' => -92.2896, 'label' => 'Little Rock, Pulaski County, AR'],
            ],
        ]);
});

it('drops upstream rows without a usable coordinate pair', function (): void {
    nominatimGuard([
        ['lat' => '34.75', 'lon' => '-92.32', 'display_name' => 'Good'],
        ['lat' => 'not-a-number', 'lon' => '-92.32', 'display_name' => 'Bad latitude'],
        ['lat' => '34.75', 'lon' => 'not-a-number', 'display_name' => 'Bad longitude'],
        ['lon' => '-92.32', 'display_name' => 'No latitude'],
        ['lat' => '34.75', 'display_name' => 'No longitude'],
        'not even an object',
    ]);

    nominatimSearch()->assertExactJson([
        'results' => [
            ['lat' => 34.75, 'lng' => -92.32, 'label' => 'Good'],
        ],
    ]);
});

it('labels a result with its display name, then its name, then nothing', function (): void {
    nominatimGuard([
        ['lat' => '1', 'lon' => '2', 'display_name' => 'Display', 'name' => 'Name'],
        ['lat' => '3', 'lon' => '4', 'name' => 'Name only'],
        ['lat' => '5', 'lon' => '6'],
        ['lat' => '7', 'lon' => '8', 'display_name' => ['not', 'a', 'string']],
    ]);

    nominatimSearch()->assertExactJson([
        'results' => [
            ['lat' => 1.0, 'lng' => 2.0, 'label' => 'Display'],
            ['lat' => 3.0, 'lng' => 4.0, 'label' => 'Name only'],
            ['lat' => 5.0, 'lng' => 6.0, 'label' => ''],
            ['lat' => 7.0, 'lng' => 8.0, 'label' => ''],
        ],
    ]);
});

it('returns no results when the upstream body is not a list', function (): void {
    nominatimGuard('just a string');

    nominatimSearch()->assertOk()->assertExactJson(['results' => []]);
});

it('does not issue an outbound request for a query below the minimum length', function (): void {
    $guard = nominatimGuard();

    nominatimSearch(['q' => 'ab'])->assertOk()->assertExactJson(['results' => []]);

    expect($guard->asked)->toBe([]);
});

it('issues a request for a query exactly at the minimum length', function (): void {
    $guard = nominatimGuard();

    nominatimSearch(['q' => 'abc'])->assertOk();

    expect($guard->fetched)->toHaveCount(1);
});

it('clamps a minimum query length below one to one, and falls back to three for a non-number', function (): void {
    config()->set('maps.nominatim.min_query_length', 0);
    $guard = nominatimGuard();

    nominatimSearch(['q' => 'a'])->assertOk();
    expect($guard->fetched)->toHaveCount(1);

    config()->set('maps.nominatim.min_query_length', 'nonsense');
    RateLimiter::clear(NOMINATIM_RATE_LIMITER_KEY);
    nominatimSearch(['q' => 'ab'])->assertOk()->assertExactJson(['results' => []]);
    expect($guard->fetched)->toHaveCount(1);
});

it('requires a query parameter', function (): void {
    $guard = nominatimGuard();

    nominatimSearch([])->assertStatus(422);

    expect($guard->asked)->toBe([]);
});

it('rejects a query longer than 255 characters', function (): void {
    $guard = nominatimGuard();

    nominatimSearch(['q' => str_repeat('a', 256)])->assertStatus(422);
    nominatimSearch(['q' => str_repeat('a', 255)])->assertOk();

    expect($guard->fetched)->toHaveCount(1);
});

it('enforces the one-request-per-second cap with a translated message and a retry header', function (): void {
    $guard = nominatimGuard();

    // Freeze time so the limiter's window cannot elapse between the two requests
    $second = test()->freezeTime(function (): TestResponse {
        nominatimSearch()->assertOk();

        return nominatimSearch(['q' => 'Conway']);
    });

    $second->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJson(['results' => [], 'message' => __('aether-fieldtypes::fieldtypes.gps.field.address_search_error')]);
    expect($guard->fetched)->toHaveCount(1)
        ->and(__('aether-fieldtypes::fieldtypes.gps.field.address_search_error'))->not->toBe('aether-fieldtypes::fieldtypes.gps.field.address_search_error');
});

it('honors a higher configured rate and clamps one below one to one', function (): void {
    config()->set('maps.nominatim.max_requests_per_second', 2);
    $guard = nominatimGuard();

    test()->freezeTime(function (): void {
        nominatimSearch()->assertOk();
        nominatimSearch()->assertOk();
        nominatimSearch()->assertStatus(429);
    });
    expect($guard->fetched)->toHaveCount(2);

    config()->set('maps.nominatim.max_requests_per_second', 0);
    RateLimiter::clear(NOMINATIM_RATE_LIMITER_KEY);

    test()->freezeTime(function (): void {
        nominatimSearch()->assertOk();
        nominatimSearch()->assertStatus(429);
    });
    expect($guard->fetched)->toHaveCount(3);
});

it('degrades to an empty 502 response when the upstream fails', function (): void {
    nominatimGuard('upstream error', status: 500);

    nominatimSearch()->assertStatus(502)->assertExactJson(['results' => []]);
});

it('degrades to an empty 502 response when the guard refuses the URL, without fetching', function (): void {
    $guard = nominatimGuard(allow: false);

    nominatimSearch()->assertStatus(502)->assertExactJson(['results' => []]);

    expect($guard->asked)->toHaveCount(1)->and($guard->fetched)->toBe([]);
});

it('degrades to an empty 502 response when the fetch throws', function (): void {
    nominatimGuard(throw: true);

    nominatimSearch()->assertStatus(502)->assertExactJson(['results' => []]);
});

it('asks the guard about the URL it then fetches, with the GET verb', function (): void {
    $guard = nominatimGuard();

    nominatimSearch(['q' => 'Little Rock'])->assertOk();

    expect($guard->fetched)->toHaveCount(1)
        ->and($guard->fetched[0][0])->toBe('GET')
        ->and($guard->asked)->toBe([$guard->fetched[0][1]]);
});

it('builds the search URL from the endpoint and the policy parameters', function (): void {
    config()->set('maps.nominatim.limit', 7);
    config()->set('maps.nominatim.country_codes', 'us,ca');
    config()->set('maps.nominatim.email', 'maps@example.test');

    $guard = nominatimGuard();

    nominatimSearch(['q' => '4301 W Markham'])->assertOk();

    expect($guard->asked[0])->toBe('https://nominatim.openstreetmap.org/search?'.http_build_query([
        'q' => '4301 W Markham',
        'format' => 'jsonv2',
        'addressdetails' => '1',
        'limit' => '7',
        'countrycodes' => 'us,ca',
        'email' => 'maps@example.test',
    ]));
});

it('drops blank optional parameters, clamps the limit to one and defaults a non-number to five', function (): void {
    config()->set('maps.nominatim.country_codes', '   ');
    config()->set('maps.nominatim.email');
    config()->set('maps.nominatim.limit', 0);

    $guard = nominatimGuard();

    nominatimSearch()->assertOk();
    expect($guard->asked[0])->toBe('https://nominatim.openstreetmap.org/search?q=Little+Rock&format=jsonv2&addressdetails=1&limit=1');

    config()->set('maps.nominatim.limit', 'nonsense');
    RateLimiter::clear(NOMINATIM_RATE_LIMITER_KEY);
    nominatimSearch()->assertOk();
    expect($guard->asked[1])->toContain('limit=5');
});

it('falls back to the public endpoint when none is configured', function (): void {
    config()->set('maps.nominatim.endpoint', '  ');
    $guard = nominatimGuard();

    nominatimSearch()->assertOk();

    expect($guard->asked[0])->toStartWith(NominatimSearchController::DEFAULT_ENDPOINT.'?');
});

it('sends an identifying request with a bounded timeout', function (): void {
    config()->set('maps.nominatim.timeout', 9);
    config()->set('app.url', 'https://sites.example.test');

    $guard = nominatimGuard();

    nominatimSearch()->assertOk();

    $options = $guard->configured?->getOptions() ?? [];
    expect($options['timeout'])->toBe(9)
        ->and($options['connect_timeout'])->toBe(3)
        ->and($options['headers'])->toBe([
            'User-Agent' => 'Aether Test Agent',
            'Referer' => 'https://sites.example.test',
            'Accept' => 'application/json',
        ]);
});

it('clamps a timeout below one to one and defaults a non-number to five', function (): void {
    config()->set('maps.nominatim.timeout', 0);
    $guard = nominatimGuard();

    nominatimSearch()->assertOk();
    expect($guard->configured?->getOptions()['timeout'])->toBe(1);

    config()->set('maps.nominatim.timeout', 'nonsense');
    RateLimiter::clear(NOMINATIM_RATE_LIMITER_KEY);
    nominatimSearch()->assertOk();
    expect($guard->configured?->getOptions()['timeout'])->toBe(5);
});

it('names the application as the user agent when none is configured', function (string $name, string $expected): void {
    config()->set('maps.nominatim.user_agent', '   ');
    config()->set('app.name', $name);
    config()->set('app.url', 'https://sites.example.test');

    $guard = nominatimGuard();

    nominatimSearch()->assertOk();

    expect($guard->header('User-Agent'))->toBe($expected);
})->with([
    'a named app' => ['Campus Sites', 'Campus Sites (https://sites.example.test)'],
    'an unnamed app' => ['', 'Statamic (https://sites.example.test)'],
]);

it('treats a non-scalar config value as empty', function (): void {
    config()->set('maps.nominatim.user_agent', ['not', 'a', 'string']);
    config()->set('app.name', 'Campus Sites');
    config()->set('app.url', 'https://sites.example.test');

    $guard = nominatimGuard();

    nominatimSearch()->assertOk();

    expect($guard->header('User-Agent'))->toBe('Campus Sites (https://sites.example.test)');
});

it('fetches through the default guard, refusing an endpoint that targets an internal host', function (): void {
    config()->set('maps.nominatim.endpoint', 'http://169.254.169.254/search');
    app()->instance(EgressGuard::class, new DefaultEgressGuard(fn (string $host): array => ['8.8.8.8']));
    nominatimActAsUser();
    Http::fake();

    nominatimSearch()->assertStatus(502)->assertExactJson(['results' => []]);

    Http::assertNothingSent();
});

it('fetches through the default guard with an identifying agent', function (): void {
    app()->instance(EgressGuard::class, new DefaultEgressGuard(fn (string $host): array => ['8.8.8.8']));
    nominatimActAsUser();
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([['lat' => '34.7', 'lon' => '-92.3', 'display_name' => 'Little Rock']], 200)]);

    nominatimSearch()->assertOk()->assertExactJson([
        'results' => [['lat' => 34.7, 'lng' => -92.3, 'label' => 'Little Rock']],
    ]);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://nominatim.openstreetmap.org/search')
        && $request->header('User-Agent') === ['Aether Test Agent']);
});

it('rejects a query that is not a string', function (): void {
    $guard = nominatimGuard();

    /** @var string $base */
    $base = cp_route('gps.nominatim-search');
    test()->getJson($base.'?q[]=a&q[]=b')->assertStatus(422);

    expect($guard->asked)->toBe([]);
});

it('keeps rows that follow an unusable one', function (): void {
    nominatimGuard([
        'not even an object',
        ['lat' => 'x', 'lon' => '2', 'display_name' => 'Bad latitude'],
        ['lat' => '1', 'lon' => 'x', 'display_name' => 'Bad longitude'],
        ['lat' => '3', 'lon' => '4', 'display_name' => 'Good after bad'],
    ]);

    nominatimSearch()->assertExactJson([
        'results' => [
            ['lat' => 3.0, 'lng' => 4.0, 'label' => 'Good after bad'],
        ],
    ]);
});

it('tells the picker to retry after the one-second window', function (): void {
    nominatimGuard();

    $second = test()->freezeTime(function (): TestResponse {
        nominatimSearch()->assertOk();

        return nominatimSearch(['q' => 'Conway']);
    });

    $second->assertStatus(429);

    expect($second->headers->get('Retry-After'))->toBe('1');
});

it('falls back to the defaults for every non-numeric setting', function (): void {
    config()->set('maps.nominatim.min_query_length', 'nonsense');
    config()->set('maps.nominatim.max_requests_per_second', 'nonsense');

    $guard = nominatimGuard();

    // A query of exactly the default length of three is sent, and the default rate of one is enforced
    test()->freezeTime(function (): void {
        nominatimSearch(['q' => 'abc'])->assertOk();
        nominatimSearch(['q' => 'abc'])->assertStatus(429);
    });

    expect($guard->fetched)->toHaveCount(1);
});

it('reads a numeric string setting as a number', function (): void {
    config()->set('maps.nominatim.limit', '7');
    config()->set('maps.nominatim.timeout', '9');

    $guard = nominatimGuard();

    nominatimSearch()->assertOk();

    expect($guard->asked[0])->toContain('limit=7')
        ->and($guard->configured?->getOptions()['timeout'])->toBe(9);
});

it('reads a non-string scalar setting as its string form', function (): void {
    config()->set('maps.nominatim.email', 123);
    $guard = nominatimGuard();

    nominatimSearch()->assertOk();

    expect($guard->asked[0])->toContain('email=123');
});
