<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Contracts;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * The seam through which the add-on makes an outbound request on a host's behalf.
 *
 * The GPS address-search endpoint fetches a configured geocoding service and never calls the
 * network itself: it asks the guard bound to this contract whether a URL is allowed, then has
 * the guard perform the request. A host replaces the default guard by binding its own
 * implementation, for example an adapter over the egress guard it already has:
 *
 * ```php
 * $this->app->bind(EgressGuard::class, SiteEgressGuard::class);
 * ```
 */
interface EgressGuard
{
    /**
     * Decide, before any connection, whether the URL may be fetched.
     *
     * @param  string  $url  The fully-qualified URL about to be fetched
     * @return bool True when the fetch is allowed
     */
    public function isAllowedUrl(string $url): bool;

    /**
     * Perform the request.
     *
     * @param  string  $method  The HTTP verb
     * @param  string  $url  The fully-qualified URL
     * @param  (Closure(PendingRequest): PendingRequest)|null  $configure  Hook that adds headers and timeouts to the pending request
     * @return Response The upstream response
     *
     * @throws \RuntimeException When the guard refuses the URL or the request cannot be made
     */
    public function fetch(string $method, string $url, ?Closure $configure = null): Response;
}
