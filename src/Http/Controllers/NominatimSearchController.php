<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Http\Controllers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Statamic\Http\Controllers\CP\CpController;
use Throwable;
use UAMSWeb\Aether\Fieldtypes\Contracts\EgressGuard;

/**
 * Server-side proxy for the GPS fieldtype's OpenStreetMap (Nominatim) address search.
 *
 * The picker's address-search box geocodes a typed address by calling this Control Panel endpoint
 * rather than the geocoding service from the editor's browser. Proxying it server-side lets the
 * request carry what the Nominatim usage policy asks for:
 *
 *   - a descriptive, identifying `User-Agent`, which a browser cannot set;
 *   - one application-wide rate limit at the policy's one-request-per-second ceiling, a best-effort
 *     throttle across editors and tabs; and
 *   - the outbound fetch made through the {@see EgressGuard} bound in the container, so a host
 *     decides what the configurable endpoint may reach.
 *
 * Authorization is inherited from {@see CpController}: only an authenticated Control Panel user
 * reaches the route.
 *
 * @see config/maps.php The `nominatim` group
 */
class NominatimSearchController extends CpController
{
    /**
     * The endpoint used when `maps.nominatim.endpoint` is empty.
     */
    public const string DEFAULT_ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    /**
     * The application-wide rate-limiter key. The check-then-hit is best-effort: a rare concurrent
     * burst may admit a second request before the first increments, which is acceptable for a
     * courtesy throttle to a public service.
     */
    private const string RATE_LIMITER_KEY = 'maps:nominatim:outbound';

    /**
     * Geocode an address query and return normalized results.
     *
     * Returns `{ results: [{ lat, lng, label }] }`. A query shorter than the configured minimum
     * yields an empty result set without an outbound request, an exhausted rate limit yields `429`,
     * and a guard refusal, a transport error or an unsuccessful upstream response yields `502` with
     * an empty result set so the picker degrades gracefully.
     *
     * @param  Request  $request  The Control Panel request carrying the `q` query
     * @param  EgressGuard  $egress  The guard the outbound fetch goes through
     * @return JsonResponse The normalized geocoding results
     */
    public function __invoke(Request $request, EgressGuard $egress): JsonResponse
    {
        // Validate the query is a present, bounded string before any work
        $request->validate([
            'q' => ['required', 'string', 'max:255'],
        ]);

        // Read it back through the typed string accessor
        $query = $request->string('q')->trim()->toString();

        // Skip short queries: they geocode poorly and waste a rate-limited slot
        if (mb_strlen($query) < $this->minQueryLength()) {
            return response()->json(['results' => []]);
        }

        // Throttle outbound requests toward the policy's ceiling; a blocked request answers 429
        if (RateLimiter::tooManyAttempts(self::RATE_LIMITER_KEY, $this->maxRequestsPerSecond())) {
            // The header setter coerces the integer to the string it requires, so the cast changes nothing sent.
            // @pest-mutate-ignore: RemoveStringCast
            return response()->json(
                ['results' => [], 'message' => __('aether-fieldtypes::fieldtypes.gps.field.address_search_error')],
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
            )->header('Retry-After', (string) RateLimiter::availableIn(self::RATE_LIMITER_KEY));
        }

        // Count this request against the window
        RateLimiter::hit(self::RATE_LIMITER_KEY, 1);

        $url = $this->buildSearchUrl($query);

        // Refuse before connecting when the guard does not allow the URL
        if (! $egress->isAllowedUrl($url)) {
            return response()->json(['results' => []], JsonResponse::HTTP_BAD_GATEWAY);
        }

        // Fetch through the guard with an identifying User-Agent and Referer and a bounded timeout
        try {
            $response = $egress->fetch(
                'GET',
                $url,
                fn (PendingRequest $pendingRequest): PendingRequest => $pendingRequest
                    ->timeout($this->timeout())
                    ->connectTimeout(3)
                    ->withHeaders([
                        'User-Agent' => $this->userAgent(),
                        'Referer' => $this->stringConfig('app.url'),
                        'Accept' => 'application/json',
                    ]),
            );
        } catch (Throwable) {
            return response()->json(['results' => []], JsonResponse::HTTP_BAD_GATEWAY);
        }

        // Degrade on an unsuccessful upstream response
        if (! $response->successful()) {
            return response()->json(['results' => []], JsonResponse::HTTP_BAD_GATEWAY);
        }

        return response()->json(['results' => $this->normalizeResults($response->json())]);
    }

    /**
     * Build the search URL with policy-compliant query parameters.
     *
     * @param  string  $query  The editor-supplied address query
     * @return string The fully-qualified search URL
     */
    private function buildSearchUrl(string $query): string
    {
        // Drop empty optional parameters so they are not sent as blanks
        $params = array_filter(
            [
                'q' => $query,
                'format' => 'jsonv2',
                'addressdetails' => '1',
                // array_filter hands the value to its callback in weak mode, which accepts the integer, so
                // the cast changes nothing the request carries.
                // @pest-mutate-ignore: RemoveStringCast
                'limit' => (string) $this->limit(),
                'countrycodes' => $this->stringConfig('maps.nominatim.country_codes'),
                'email' => $this->stringConfig('maps.nominatim.email'),
            ],
            static fn (string $value): bool => $value !== '',
        );

        return $this->endpoint().'?'.http_build_query($params);
    }

    /**
     * Normalize the response into the picker's `{ lat, lng, label }` shape.
     *
     * Rows without a numeric latitude and longitude are dropped so the picker never receives an
     * unplaceable result.
     *
     * @param  mixed  $json  The decoded response body
     * @return list<array{lat: float, lng: float, label: string}> The normalized results
     */
    private function normalizeResults(mixed $json): array
    {
        // Anything but a list of rows has no results
        if (! is_array($json)) {
            return [];
        }

        $results = [];

        foreach ($json as $row) {
            // Skip rows that are not objects or lack a usable coordinate pair
            if (! is_array($row) || ! is_numeric($row['lat'] ?? null) || ! is_numeric($row['lon'] ?? null)) {
                continue;
            }

            $label = $row['display_name'] ?? ($row['name'] ?? '');

            $results[] = [
                'lat' => (float) $row['lat'],
                'lng' => (float) $row['lon'],
                'label' => is_string($label) ? $label : '',
            ];
        }

        return $results;
    }

    /**
     * The descriptive User-Agent identifying this application to the service.
     *
     * Falls back to the app name and URL when none is configured, so a request is never sent
     * without an identifying agent.
     *
     * @return string The configured agent, or `<app name> (<app url>)`
     */
    private function userAgent(): string
    {
        $configured = $this->stringConfig('maps.nominatim.user_agent');

        if ($configured !== '') {
            return $configured;
        }

        $name = $this->stringConfig('app.name');

        return sprintf('%s (%s)', $name !== '' ? $name : 'Statamic', $this->stringConfig('app.url'));
    }

    /**
     * The configured search endpoint.
     *
     * @return string The endpoint, or the default when none is configured
     */
    private function endpoint(): string
    {
        $endpoint = $this->stringConfig('maps.nominatim.endpoint');

        return $endpoint !== '' ? $endpoint : self::DEFAULT_ENDPOINT;
    }

    /**
     * The minimum query length before a search is issued.
     *
     * @return int The configured length, at least 1
     */
    private function minQueryLength(): int
    {
        $length = $this->intConfig('maps.nominatim.min_query_length', 3);

        // A query is never empty (the `q` rule requires a value), so a floor of zero and a floor of one
        // admit the same queries.
        // @pest-mutate-ignore: DecrementInteger
        return max(1, $length);
    }

    /**
     * The maximum number of results requested.
     *
     * @return int The configured limit, at least 1
     */
    private function limit(): int
    {
        return max(1, $this->intConfig('maps.nominatim.limit', 5));
    }

    /**
     * The policy-capped maximum outbound requests per second.
     *
     * @return int The configured rate, at least 1
     */
    private function maxRequestsPerSecond(): int
    {
        // A default of zero is floored to one below, so it behaves as the default of one does.
        // @pest-mutate-ignore: DecrementInteger
        $rate = $this->intConfig('maps.nominatim.max_requests_per_second', 1);

        // The limiter admits the first request and blocks the next for the rest of the window at a ceiling
        // of zero just as it does at one, so the floor of one changes nothing a request can observe.
        // @pest-mutate-ignore: DecrementInteger
        return max(1, $rate);
    }

    /**
     * The outbound request timeout in seconds.
     *
     * @return int The configured timeout, at least 1
     */
    private function timeout(): int
    {
        return max(1, $this->intConfig('maps.nominatim.timeout', 5));
    }

    /**
     * Read a config value as an int, falling back to the default for non-numerics.
     *
     * @param  string  $key  The dotted config key
     * @param  int  $default  The value used when the config entry is non-numeric
     * @return int The narrowed integer value
     */
    private function intConfig(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Read a config value as a trimmed string, treating non-scalars as empty.
     *
     * @param  string  $key  The dotted config key
     * @return string The trimmed string value, or an empty string
     */
    private function stringConfig(string $key): string
    {
        $value = config($key);

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
