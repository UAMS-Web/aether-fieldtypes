Planned: not yet released. Ships with the release that closes [#47](https://github.com/UAMS-Web/aether-fieldtypes/issues/47), which brings this page to the names and defaults that ship.

The GPS fieldtype's address-search box finds a place by name and moves the marker to it. The box asks a Control Panel endpoint, `gps/nominatim-search`, which the add-on provides. The endpoint fetches results from a Nominatim service, and the fetch goes through an egress guard that the host binds. This page covers the endpoint, its settings and the guard seam.

## The endpoint

| Item | Value |
| --- | --- |
| Route | `gps/nominatim-search` in the Control Panel (`cp_url('gps/nominatim-search')`) |
| Method | `GET` with a `q` query string of at most 255 characters |
| Response | `{ "results": [{ "lat", "lng", "label" }] }` |
| Short query | Fewer characters than `maps.nominatim.min_query_length` returns an empty `results` list without a request |
| Rate limit | One outbound request per second for the whole application; a blocked request answers `429` with `Retry-After` |
| Failure | A refused or failed outbound request answers `502` with an empty `results` list |

The box shows only on a field that turns on `enable_address_search`, which defaults to off. The editor's input reaches only the query string. The host and path come from the configured endpoint.

## Settings

The add-on merges `config/maps.php` under the `maps` key. A host's own group of the same name wins whole.

| Key | Default | Effect |
| --- | --- | --- |
| `maps.nominatim.min_query_length` | `3` | Shortest query that is sent |
| `maps.nominatim.endpoint` | Public Nominatim search URL | The one host the controller may call |
| `maps.nominatim.user_agent` | Empty | Identifying `User-Agent`; the Nominatim usage policy requires one, so set it |

The remaining `maps.nominatim` keys (rate limit, result limit, country codes, timeout) are listed here when #47 ships them.

## The egress guard seam

The controller never calls the network itself. It resolves an egress-guard contract from the container and asks it two things: whether a URL is allowed, and to perform the request. A host replaces the guard by binding its own implementation.

Planned contract (names are final when #47 merges):

| Method | Purpose |
| --- | --- |
| `isAllowedUrl(string $url): bool` | Refuse a URL before any connection |
| `fetch(string $method, string $url, Closure $configure)` | Perform the request; `$configure` receives the pending HTTP request to add headers and timeouts |

Bind it in a service provider:

```php
$this->app->bind(EgressGuard::class, SiteEgressGuard::class);
```

A host that already has an egress guard binds a thin adapter over it. The site binds an adapter over its `OutboundUrlGuard`.

**Default guard.** A host that binds nothing gets a default that allows only `http` and `https` to the single configured endpoint host and refuses a literal or resolved loopback, private or link-local address. It protects against a misconfigured endpoint setting. It does not pin the resolved address the way a full guard does, so a host that exposes the Control Panel to untrusted networks should bind its own.

## Troubleshooting

- **The address-search box shows and returns nothing.** Check that the request to `gps/nominatim-search` answers `200`; a `502` means the guard refused the endpoint or the service failed, and a `429` means the one-per-second limit was hit.
- **Every search answers `502` on a development machine.** The endpoint setting points at a private or loopback address and the default guard refuses it; bind a guard that allows it for local work.

See the decision in [`docs/DECISIONS.md`](https://github.com/UAMS-Web/aether-fieldtypes/blob/main/docs/DECISIONS.md) and the ticket [#49](https://github.com/UAMS-Web/aether-fieldtypes/issues/49).
