Written against v0.3.0.

The GPS fieldtype and its Control Panel map picker read their starting position, tile source and aerial layer from a `maps` configuration group, which the add-on ships in `config/maps.php`. This page lists every key, the environment variable that sets it, its default and what reads it. It also states what the add-on's own `config/aether-fieldtypes.php` holds at this version.

- [How the settings are loaded](#how-the-settings-are-loaded)
- [Default map position](#default-map-position)
- [Map tiles](#map-tiles)
- [Aerial layer](#aerial-layer)
- [Address search](#address-search)
- [Per-field settings that override the defaults](#per-field-settings-that-override-the-defaults)
- [The add-on config file](#the-add-on-config-file)

## How the settings are loaded

`UAMSWeb\Aether\Fieldtypes\ServiceProvider::register()` calls `mergeConfigFrom(__DIR__.'/../config/maps.php', 'maps')`, so the add-on's defaults appear under the `maps` config key (`config('maps.tiles.url')`, and so on). The merge is Laravel's `mergeConfigFrom`, which works on top-level keys: a host's own `config/maps.php` wins for each top-level group it defines (`default`, `tiles`, `aerial`, `nominatim`), and the add-on fills in the groups the host leaves out. A host that defines `tiles` therefore replaces the whole `tiles` group, not individual keys within it; a test in the add-on pins this behavior (a host `tiles` group with only `url` leaves `maps.aerial` and `maps.default` at the add-on defaults).

Every value is read in `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Gps::preload()`, which passes the resolved values to the Vue picker component (`resources/js/components/fieldtypes/GpsFieldtype.vue`) as its `meta`. The picker reads them there; nothing in `resources/js` reads the config directly.

Values are cast before they are passed on: numeric settings that are not numeric become `0` (latitude, longitude, zoom and max zoom keys), and non-scalar URL or attribution values become an empty string.

## Default map position

Group `default`. The center and zoom the picker opens at before a marker is placed. The config comment says the built-in fallbacks center on central Arkansas.

| Key | Environment variable | Type | Default | Effect |
| --- | --- | --- | --- | --- |
| `maps.default.latitude` | `MAP_DEFAULT_LATITUDE` | float | `34.74828329990028` | starting latitude; `Gps::preload()` sends it as `defaultLatitude` |
| `maps.default.longitude` | `MAP_DEFAULT_LONGITUDE` | float | `-92.32145208472011` | starting longitude; sent as `defaultLongitude` |
| `maps.default.zoom` | `MAP_DEFAULT_ZOOM` | int | `14` | starting zoom; sent as `defaultZoom` |

The picker centers on the stored marker when there is one (`value.lat`, `value.lng`, `value.zoom`) and on these defaults otherwise. The picker's view-reset control re-frames the map on them without touching the stored marker. A field-level default wins over the config value (see [Per-field settings that override the defaults](#per-field-settings-that-override-the-defaults)).

## Map tiles

Group `tiles`. The street layer, shown as the base map.

| Key | Environment variable | Type | Default | Effect |
| --- | --- | --- | --- | --- |
| `maps.tiles.url` | `MAP_TILE_URL` | string | `https://tile.openstreetmap.org/{z}/{x}/{y}.png` | tile URL template for the street layer; sent as `tileUrl` and used for the Leaflet tile layer |
| `maps.tiles.attribution` | `MAP_TILE_ATTRIBUTION` | string (HTML) | `&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors` | attribution text of the street layer; sent as `tileAttribution` |
| `maps.tiles.max_zoom` | `MAP_TILE_MAX_ZOOM` | int | `19` | highest zoom of the street layer; sent as `tileMaxZoom` |

The config comment states the OpenStreetMap Tile Usage Policy as follows: attribution must always be shown, the tile URL should not be hard-coded so the provider can be switched without a code change, and bulk or pre-emptive tile fetching is prohibited. The public OpenStreetMap tile server used by default is described there as appropriate for the low-volume, interactive Control Panel picker, and a high-traffic public-facing map should point these values at a dedicated tile provider or a self-hosted tile server by setting the environment variables, with no code change. The single-host URL (no `{s}` subdomain placeholder) is the form the comment says the policy now requires, and the legacy `a`, `b` and `c` subdomain hosts are described there as deprecated.

## Aerial layer

Group `aerial`. An optional imagery layer offered as a "Street / Aerial" toggle in the picker. The config comment says OpenStreetMap has no imagery, so the default is Esri World Imagery, which needs no API key, and that Esri attribution is shown whenever the aerial layer is active.

| Key | Environment variable | Type | Default | Effect |
| --- | --- | --- | --- | --- |
| `maps.aerial.url` | `MAP_AERIAL_TILE_URL` | string | `https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}` | tile URL template; an empty string disables the toggle and leaves only the street layer; sent as `aerialUrl` |
| `maps.aerial.attribution` | `MAP_AERIAL_TILE_ATTRIBUTION` | string (HTML) | `Tiles &copy; <a href="https://www.esri.com/">Esri</a>, Vantor, Earthstar Geographics, and the GIS User Community` | attribution shown while the aerial layer is active; sent as `aerialAttribution` |
| `maps.aerial.max_zoom` | `MAP_AERIAL_TILE_MAX_ZOOM` | int | `19` | how far the picker lets the user zoom on this layer; sent as `aerialMaxZoom` |
| `maps.aerial.max_native_zoom` | `MAP_AERIAL_TILE_MAX_NATIVE_ZOOM` | int | `19` | deepest level with real tiles; past it Leaflet stretches its own tiles instead of fetching more; sent as `aerialMaxNativeZoom` |

Placeholder order matters: the config comment states that Esri World Imagery uses ArcGIS REST tile order (`{z}/{y}/{x}`), not the XYZ order (`{z}/{x}/{y}`), and that the placeholders must be kept in that order when overriding the URL. The comment also notes the ArcGIS scheme defines levels 0 to 23 but Esri captures imagery only to a region-dependent resolution (about level 19 across the US), which is why `max_native_zoom` exists. On terms, the comment says World Imagery is licensed under the Esri Master License Agreement and intended for use in ArcGIS apps, offline or tile export is prohibited, low-volume interactive Control Panel use is fine, and a high-traffic public-facing map should use a properly licensed provider.

In the picker, the aerial layer and its button are created only when `aerialUrl` is non-empty.

## Address search

Group `nominatim`.

| Key | Environment variable | Type | Default | Effect |
| --- | --- | --- | --- | --- |
| `maps.nominatim.min_query_length` | `MAP_NOMINATIM_MIN_QUERY_LENGTH` | int | `3` | shortest query the picker's address-search box sends; `Gps::preload()` sends `max(1, value)` as `addressSearchMinLength`, and the picker compares the trimmed query length against it |

The config comment says the search itself is answered by an endpoint the host provides in the Control Panel, with its own settings under this key. The add-on's service provider registers no route, so the search box needs that host endpoint to return results. The box is shown only when a field turns on `enable_address_search` (see below); the setting is false by default.

## Per-field settings that override the defaults

The GPS field's blueprint config (defined in `Gps.php`) carries these options. They are read in `Gps::preload()` together with the config above.

| Field setting | Type | Default | Effect |
| --- | --- | --- | --- |
| `default_latitude` | text | unset | overrides `maps.default.latitude` for this field |
| `default_longitude` | text | unset | overrides `maps.default.longitude` for this field |
| `default_zoom` | integer | unset | overrides `maps.default.zoom` for this field |
| `enable_address_search` | toggle | `false` | shows the address-search box in the picker |

A value that is not numeric after resolution is sent as `0`.

## The add-on config file

`config/aether-fieldtypes.php` holds no settings at v0.3.0. Its return value is an empty array (the only content is the commented placeholder `// 'some_key' => 'default',`), and its header comment describes how Statamic merges an add-on config under the add-on handle and how a host would publish it:

```
php artisan vendor:publish --tag=aether-fieldtypes-config
```

The service provider contains no explicit publish call. The add-on's integration test (`tests/Integration/ServiceProviderTest.php`) asserts that the tag `aether-fieldtypes-config` publishes `config/aether-fieldtypes.php` to the host's `config/aether-fieldtypes.php`, so the publish comes from Statamic's add-on service provider base class. Because the file is empty, publishing it changes nothing. The map settings above are not in this file; they live in `config/maps.php` under the separate `maps` key.
