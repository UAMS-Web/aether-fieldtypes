<?php

declare(strict_types=1);

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use UAMSWeb\Aether\Fieldtypes\Support\DefaultEgressGuard;

/**
 * The guard a host gets when it binds none: it allows only the configured endpoint's host, over
 * http or https, and only when every address that host stands for is public.
 *
 * @command  vendor/bin/pest tests/Integration/Support/DefaultEgressGuardTest.php
 */
covers(DefaultEgressGuard::class);

beforeEach(function (): void {
    config()->set('maps.nominatim.endpoint', 'https://nominatim.openstreetmap.org/search');
});

/**
 * A guard whose resolver answers with the given addresses for any name.
 *
 * @param  list<string>  $addresses  The addresses every name resolves to
 */
function egressGuardResolving(array $addresses): DefaultEgressGuard
{
    return new DefaultEgressGuard(fn (string $host): array => $addresses);
}

it('allows the configured endpoint host over http and https when it resolves to a public address', function (string $url): void {
    config()->set('maps.nominatim.endpoint', 'https://nominatim.openstreetmap.org/search');

    expect(egressGuardResolving(['8.8.8.8'])->isAllowedUrl($url))->toBeTrue();
})->with([
    'https' => ['https://nominatim.openstreetmap.org/search?q=x'],
    'http' => ['http://nominatim.openstreetmap.org/search?q=x'],
    'upper case' => ['HTTPS://Nominatim.OpenStreetMap.org/search?q=x'],
]);

it('refuses a scheme other than http and https, and a URL with no host', function (string $url): void {
    expect(egressGuardResolving(['8.8.8.8'])->isAllowedUrl($url))->toBeFalse();
})->with([
    'ftp' => ['ftp://nominatim.openstreetmap.org/search'],
    'file' => ['file:///etc/passwd'],
    'no scheme' => ['//nominatim.openstreetmap.org/search'],
    'no host' => ['https:///search'],
    'not a URL' => ['http:///'],
    'empty' => [''],
]);

it('refuses a host other than the configured endpoint host', function (): void {
    expect(egressGuardResolving(['8.8.8.8'])->isAllowedUrl('https://evil.example.test/search'))->toBeFalse();
});

it('uses the public endpoint as the configured host when none is configured', function (): void {
    config()->set('maps.nominatim.endpoint', '   ');
    $guard = egressGuardResolving(['8.8.8.8']);

    expect($guard->isAllowedUrl('https://nominatim.openstreetmap.org/search'))->toBeTrue()
        ->and($guard->isAllowedUrl('https://other.example.test/search'))->toBeFalse();

    config()->set('maps.nominatim.endpoint');

    expect($guard->isAllowedUrl('https://nominatim.openstreetmap.org/search'))->toBeTrue();
});

it('refuses everything when the configured endpoint has no host', function (): void {
    config()->set('maps.nominatim.endpoint', 'not a url');

    expect(egressGuardResolving(['8.8.8.8'])->isAllowedUrl('https://nominatim.openstreetmap.org/search'))->toBeFalse();
});

it('refuses a host that resolves to a private or reserved address, however many it has', function (array $addresses): void {
    expect(egressGuardResolving(array_values(array_filter($addresses, is_string(...))))->isAllowedUrl('https://nominatim.openstreetmap.org/search'))->toBeFalse();
})->with([
    'loopback' => [['127.0.0.1']],
    'private 10/8' => [['10.1.2.3']],
    'private 172.16/12' => [['172.16.0.9']],
    'private 192.168/16' => [['192.168.1.1']],
    'link-local' => [['169.254.169.254']],
    'unspecified' => [['0.0.0.0']],
    'IPv6 loopback' => [['::1']],
    'IPv6 unique local' => [['fd00::1']],
    'a public address beside a private one' => [['8.8.8.8', '10.0.0.1']],
    'a private address beside a public one' => [['10.0.0.1', '8.8.8.8']],
]);

it('allows a host whose every address is public', function (): void {
    expect(egressGuardResolving(['8.8.8.8', '1.1.1.1', '2606:4700:4700::1111'])->isAllowedUrl('https://nominatim.openstreetmap.org/search'))->toBeTrue();
});

it('refuses a host that resolves to nothing', function (): void {
    expect(egressGuardResolving([])->isAllowedUrl('https://nominatim.openstreetmap.org/search'))->toBeFalse();
});

it('judges an IP literal host on its own, without resolving it', function (string $endpoint, bool $allowed): void {
    config()->set('maps.nominatim.endpoint', $endpoint);
    $resolved = false;
    $guard = new DefaultEgressGuard(function (string $host) use (&$resolved): array {
        $resolved = true;

        return ['8.8.8.8'];
    });

    expect($guard->isAllowedUrl($endpoint))->toBe($allowed)
        ->and($resolved)->toBeFalse();
})->with([
    'a public IPv4' => ['https://8.8.8.8/search', true],
    'the metadata address' => ['http://169.254.169.254/search', false],
    'loopback' => ['http://127.0.0.1/search', false],
    'a private IPv4' => ['http://192.168.0.10/search', false],
    'a public IPv6' => ['https://[2606:4700:4700::1111]/search', true],
    'IPv6 loopback' => ['http://[::1]/search', false],
]);

it('resolves a name through the system resolver by default, refusing a name that does not resolve', function (): void {
    config()->set('maps.nominatim.endpoint', 'http://localhost/search');
    $guard = new DefaultEgressGuard;

    // localhost resolves to a loopback address everywhere
    expect($guard->isAllowedUrl('http://localhost/search'))->toBeFalse();

    config()->set('maps.nominatim.endpoint', 'http://no-such-host.invalid/search');

    expect($guard->isAllowedUrl('http://no-such-host.invalid/search'))->toBeFalse();
});

it('passes the host name to the resolver, lower-cased', function (): void {
    $seen = [];
    $guard = new DefaultEgressGuard(function (string $host) use (&$seen): array {
        $seen[] = $host;

        return ['8.8.8.8'];
    });

    $guard->isAllowedUrl('https://Nominatim.OpenStreetMap.org/search');

    expect($seen)->toBe(['nominatim.openstreetmap.org']);
});

it('fetches an allowed URL with the verb it was given', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(['ok' => true], 200)]);

    $response = egressGuardResolving(['8.8.8.8'])->fetch('GET', 'https://nominatim.openstreetmap.org/search?q=x');

    expect($response->json())->toBe(['ok' => true]);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request->url() === 'https://nominatim.openstreetmap.org/search?q=x');
});

it('sends the verb it was given', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 200)]);

    egressGuardResolving(['8.8.8.8'])->fetch('POST', 'https://nominatim.openstreetmap.org/search');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST');
});

it('applies the configure hook to the request it sends', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 200)]);

    egressGuardResolving(['8.8.8.8'])->fetch(
        'GET',
        'https://nominatim.openstreetmap.org/search',
        fn (PendingRequest $request): PendingRequest => $request->withHeaders(['X-Probe' => 'set']),
    );

    Http::assertSent(fn (Request $request): bool => $request->header('X-Probe') === ['set']);
});

it('does not follow a redirect', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest']),
        '169.254.169.254/*' => Http::response('secret', 200),
    ]);

    $response = egressGuardResolving(['8.8.8.8'])->fetch('GET', 'https://nominatim.openstreetmap.org/search');

    expect($response->status())->toBe(302);
    Http::assertSentCount(1);
});

it('refuses to fetch a URL it does not allow, without sending anything', function (): void {
    Http::fake();

    expect(fn (): Response => egressGuardResolving(['10.0.0.1'])->fetch('GET', 'https://nominatim.openstreetmap.org/search'))
        ->toThrow(RuntimeException::class, 'The egress guard refused the URL.');

    Http::assertNothingSent();
});

it('reads the configured endpoint without its surrounding whitespace, and its host in any case', function (): void {
    config()->set('maps.nominatim.endpoint', "  https://NOMINATIM.openstreetmap.org/search \n");

    expect(egressGuardResolving(['8.8.8.8'])->isAllowedUrl('https://nominatim.openstreetmap.org/search'))->toBeTrue();
});

it('refuses a URL with no host even when the configured endpoint has none either', function (): void {
    config()->set('maps.nominatim.endpoint', 'not a url');

    expect(egressGuardResolving(['8.8.8.8'])->isAllowedUrl('not a url'))->toBeFalse()
        ->and(egressGuardResolving(['8.8.8.8'])->isAllowedUrl('mailto:someone@example.test'))->toBeFalse();
});
