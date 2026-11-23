<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Support;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use UAMSWeb\Aether\Fieldtypes\Contracts\EgressGuard;
use UAMSWeb\Aether\Fieldtypes\Http\Controllers\NominatimSearchController;

/**
 * The guard a host gets when it binds none.
 *
 * It allows a request only to the one host of the configured `maps.nominatim.endpoint`, over `http`
 * or `https`, and only when that host is a public address: a literal or resolved loopback, private,
 * link-local or reserved address is refused. A redirect is never followed, so an allowed host cannot
 * send the request on to an internal one.
 *
 * It does not pin the resolved address for the connection, so it cannot stop a host that resolves
 * differently between the check and the request. A host that exposes the Control Panel to untrusted
 * networks binds a guard that pins the address.
 */
class DefaultEgressGuard implements EgressGuard
{
    /**
     * The schemes a request may use.
     *
     * @var list<string>
     */
    private const array SCHEMES = ['http', 'https'];

    /**
     * Create a guard.
     *
     * @param  (Closure(string): list<string>)|null  $resolver  Resolves a host name to its IP addresses; the system resolver when null
     */
    public function __construct(private readonly ?Closure $resolver = null) {}

    /**
     * Decide, before any connection, whether the URL may be fetched.
     *
     * @param  string  $url  The fully-qualified URL about to be fetched
     * @return bool True when the URL is `http` or `https`, names the configured endpoint host, and that host is public
     */
    #[\Override]
    public function isAllowedUrl(string $url): bool
    {
        // Split the URL into its scheme and host; a malformed URL yields neither
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        // Refuse a scheme outside http and https, and a URL with no host
        if (! is_string($scheme) || ! in_array(strtolower($scheme), self::SCHEMES, true) || ! is_string($host)) {
            return false;
        }

        // Refuse any host but the configured endpoint's
        $host = strtolower($host);
        if ($host !== $this->configuredHost()) {
            return false;
        }

        // Allow the host only when every address it stands for is public
        return $this->isPublicHost($host);
    }

    /**
     * Perform the request without following redirects.
     *
     * @param  string  $method  The HTTP verb
     * @param  string  $url  The fully-qualified URL
     * @param  (Closure(PendingRequest): PendingRequest)|null  $configure  Hook that adds headers and timeouts to the pending request
     * @return Response The upstream response
     *
     * @throws RuntimeException When the URL is not allowed
     */
    #[\Override]
    public function fetch(string $method, string $url, ?Closure $configure = null): Response
    {
        // Refuse a URL the guard does not allow
        if (! $this->isAllowedUrl($url)) {
            throw new RuntimeException('The egress guard refused the URL.');
        }

        // Start a request that never follows a redirect
        $request = Http::withoutRedirecting();

        // Let the caller add its headers and timeouts
        if ($configure instanceof Closure) {
            $request = $configure($request);
        }

        return $request->send($method, $url);
    }

    /**
     * The host of the configured endpoint, lower-cased.
     *
     * @return string|null The host, or null when the endpoint has none
     */
    private function configuredHost(): ?string
    {
        // Read the configured endpoint, falling back to the one the controller falls back to
        $endpoint = config('maps.nominatim.endpoint');
        $endpoint = is_string($endpoint) && trim($endpoint) !== '' ? trim($endpoint) : NominatimSearchController::DEFAULT_ENDPOINT;

        // Take its host
        $host = parse_url($endpoint, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : null;
    }

    /**
     * Whether every address the host stands for is public.
     *
     * @param  string  $host  The lower-cased host, an IP literal or a name
     * @return bool False for no address at all, or for any private or reserved one
     */
    private function isPublicHost(string $host): bool
    {
        // Take an IPv6 literal out of its brackets
        $literal = trim($host, '[]');

        // Judge an IP literal on its own
        if (filter_var($literal, FILTER_VALIDATE_IP) !== false) {
            return $this->isPublicAddress($literal);
        }

        // Resolve a name to every address it stands for
        $addresses = $this->resolver instanceof Closure ? ($this->resolver)($host) : (gethostbynamel($host) ?: []);

        // Refuse a name that resolves to nothing
        if ($addresses === []) {
            return false;
        }

        // Refuse the name when any address is not public
        foreach ($addresses as $address) {
            if (! $this->isPublicAddress($address)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the address is outside the private and reserved ranges.
     *
     * @param  string  $address  An IPv4 or IPv6 address
     * @return bool True for a public address
     */
    private function isPublicAddress(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
