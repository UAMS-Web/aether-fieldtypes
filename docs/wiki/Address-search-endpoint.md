Not yet released: ships with the first release after #47 merges.

The GPS fieldtype's address-search box finds a place by name and moves the marker to it. The box asks a Control Panel endpoint, `gps/nominatim-search`, which the add-on provides. The endpoint fetches results from a Nominatim service, and the fetch goes through an egress guard that the host can replace. This page covers the endpoint, its settings and the guard seam.

## The endpoint

| Item | Value |
| --- | --- |
| Route | `gps/nominatim-search` in the Control Panel (`cp_url('gps/nominatim-search')`), named `gps.nominatim-search` |
| Access | An authenticated Control Panel user; a guest is refused before any outbound request |
| Method | `GET` with a `q` query string, required, a string of at most 255 characters (otherwise `422`) |
| Response | `{ "results": [{ "lat", "lng", "label" }] }` |
| Short query | A query shorter than `maps.nominatim.min_query_length` returns an empty `results` list without a request |
| Rate limit | One outbound request per second for the whole application by default; a blocked request answers `429` with a `Retry-After` header and an `Address search is unavailable right now` message |
| Failure | A URL the guard refuses, a failed fetch or an unsuccessful upstream response answers `502` with an empty `results` list |

Rows the service returns without a numeric latitude and longitude are dropped. A result's label is its `display_name`, then its `name`, then an empty string.

The box shows only on a field that turns on `enable_address_search`, which defaults to off. The editor's input reaches only the query string. The host and path come from the configured endpoint.

## Settings

The add-on merges `config/maps.php` under the `maps` key, with these keys in the `nominatim` group. A host's own `maps` group of the same top-level name wins whole, so a host that sets a `nominatim` group sets every key it needs.

| Key | Environment variable | Default | Effect |
| --- | --- | --- | --- |
| `maps.nominatim.endpoint` | `MAP_NOMINATIM_ENDPOINT` | `https://nominatim.openstreetmap.org/search` | The search URL; the one host the default guard allows. An empty value uses the default |
| `maps.nominatim.user_agent` | `MAP_NOMINATIM_USER_AGENT` | Empty | The `User-Agent` sent to the service. Empty sends the application name and URL. The Nominatim usage policy requires an identifying agent, so set it |
| `maps.nominatim.email` | `MAP_NOMINATIM_EMAIL` | Empty | A contact email added to each request when set |
| `maps.nominatim.max_requests_per_second` | `MAP_NOMINATIM_MAX_RPS` | `1` | The application-wide ceiling on outbound requests; at least 1 |
| `maps.nominatim.limit` | `MAP_NOMINATIM_LIMIT` | `5` | The most results requested; at least 1 |
| `maps.nominatim.min_query_length` | `MAP_NOMINATIM_MIN_QUERY_LENGTH` | `3` | The shortest query that is sent |
| `maps.nominatim.country_codes` | `MAP_NOMINATIM_COUNTRY_CODES` | `us` | Comma-separated ISO 3166-1 alpha-2 codes that bias results; empty sends none |
| `maps.nominatim.timeout` | `MAP_NOMINATIM_TIMEOUT` | `5` | The request timeout in seconds, with a 3-second connect timeout; at least 1 |

A non-numeric value for a numeric key is read as that key's default.

## The egress guard seam

The controller never calls the network itself. It resolves `UAMSWeb\Aether\Fieldtypes\Contracts\EgressGuard` from the container and asks it two things: whether the URL is allowed, and to perform the request. A host replaces the guard by binding its own implementation.

| Method | Purpose |
| --- | --- |
| `isAllowedUrl(string $url): bool` | Refuse a URL before any connection |
| `fetch(string $method, string $url, ?Closure $configure = null): Response` | Perform the request; `$configure` receives the pending HTTP request to add headers and timeouts and returns it. Throw a `RuntimeException` to refuse |

Bind it in a service provider's `register()`:

```php
$this->app->bind(EgressGuard::class, SiteEgressGuard::class);
```

The add-on registers its default with `bindIf`, so a binding the host made earlier is left alone and one it makes later replaces the default. A host that already has an egress guard binds a thin adapter over it. The site binds an adapter over its `OutboundUrlGuard`.

**Default guard.** A host that binds nothing gets `DefaultEgressGuard`. It allows a request only when all of these hold:

- the scheme is `http` or `https`;
- the host is the host of `maps.nominatim.endpoint`;
- the host is a public address: an IP literal is judged as written, and a name is resolved, with every address required to be outside the loopback, private, link-local and reserved ranges, and a name that resolves to nothing refused.

It never follows a redirect. It does not pin the resolved address for the connection, so it cannot stop a host that resolves differently between the check and the request, and it does not cover ranges such as carrier-grade NAT. A host that exposes the Control Panel to untrusted networks should bind a guard that pins the address.

## Troubleshooting

- **The address-search box shows and returns nothing.** Check the status of the request to `gps/nominatim-search`. A `502` means the guard refused the URL, the fetch failed or the service answered with an error, and a `429` means the one-per-second limit was hit.
- **Every search answers `502` on a development machine.** The endpoint setting points at a private or loopback address and the default guard refuses it. Bind a guard that allows it for local work.
- **Every search answers `502` after changing the endpoint.** The default guard allows only the host of the configured endpoint over `http` or `https`; check the scheme and that the name resolves to a public address from the server.

See the decision in [`docs/DECISIONS.md`](https://github.com/UAMS-Web/aether-fieldtypes/blob/main/docs/DECISIONS.md) and the ticket [#49](https://github.com/UAMS-Web/aether-fieldtypes/issues/49).
