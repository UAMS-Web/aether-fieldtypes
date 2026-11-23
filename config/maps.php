<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default map position
    |--------------------------------------------------------------------------
    |
    | The center and zoom the GPS / map picker opens at before a marker is
    | placed, used whenever a field sets no explicit `default_latitude` /
    | `default_longitude` / `default_zoom`. Override per environment with the
    | variables below; the built-in fallbacks center on central Arkansas.
    |
    */

    'default' => [

        'latitude' => (float) env('MAP_DEFAULT_LATITUDE', 34.74828329990028),

        'longitude' => (float) env('MAP_DEFAULT_LONGITUDE', -92.32145208472011),

        'zoom' => (int) env('MAP_DEFAULT_ZOOM', 14),

    ],

    /*
    |--------------------------------------------------------------------------
    | Map tiles
    |--------------------------------------------------------------------------
    |
    | Tile source for the GPS / map picker (and any future map rendering).
    |
    | The OpenStreetMap Tile Usage Policy
    | (https://operations.osmfoundation.org/policies/tiles/) requires that
    | attribution is always shown, asks that the tile URL is not hard-coded so
    | the provider can be switched without a code change, and prohibits bulk /
    | pre-emptive tile fetching. The public OpenStreetMap tile server used here
    | by default is appropriate for the low-volume, interactive Control Panel
    | picker. A high-traffic public-facing map should point these values at a
    | dedicated tile provider or a self-hosted tile server (see switch2osm.org)
    | by setting the environment variables below — no code change required.
    |
    | Note: the single-host URL (no `{s}` subdomains) is the form the policy now
    | requires; the legacy `a/b/c.tile.openstreetmap.org` subdomains are
    | deprecated.
    |
    */

    'tiles' => [

        'url' => env('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),

        'attribution' => env(
            'MAP_TILE_ATTRIBUTION',
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        ),

        'max_zoom' => (int) env('MAP_TILE_MAX_ZOOM', 19),

    ],

    /*
    |--------------------------------------------------------------------------
    | Aerial / satellite layer
    |--------------------------------------------------------------------------
    |
    | Optional imagery layer offered as a "Street / Aerial" toggle in the
    | picker. OpenStreetMap has no imagery, so this defaults to Esri World
    | Imagery, which needs no API key. Esri attribution is shown whenever the
    | aerial layer is active, as their terms require. Set the URL to an empty
    | string to disable the toggle and show only the street layer.
    |
    | Esri World Imagery uses ArcGIS REST tile order ({z}/{y}/{x}), not the XYZ
    | order ({z}/{x}/{y}) — keep the placeholders in that order when overriding.
    |
    | Zoom: the ArcGIS tiling scheme defines levels 0–23, but Esri only captures
    | imagery to a region-dependent resolution (~30 cm / level 19 across the US,
    | coarser elsewhere) and upsamples beyond that — so levels past `max_native`
    | add no detail. `max_native` is the deepest level with real tiles (Leaflet
    | stretches its own tiles past it instead of re-fetching identical upsampled
    | ones); `max_zoom` caps how far the picker lets you zoom on this layer.
    |
    | Terms: World Imagery is licensed under the Esri Master License Agreement
    | and is intended for use in ArcGIS apps; offline/tile export is prohibited.
    | Low-volume interactive Control Panel use is fine, but a high-traffic
    | public-facing map should use a properly licensed provider.
    |
    */

    'aerial' => [

        'url' => env(
            'MAP_AERIAL_TILE_URL',
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        ),

        'attribution' => env(
            'MAP_AERIAL_TILE_ATTRIBUTION',
            'Tiles &copy; <a href="https://www.esri.com/">Esri</a>, Vantor, Earthstar Geographics, and the GIS User Community',
        ),

        'max_zoom' => (int) env('MAP_AERIAL_TILE_MAX_ZOOM', 19),

        'max_native_zoom' => (int) env('MAP_AERIAL_TILE_MAX_NATIVE_ZOOM', 19),

    ],

    /*
    |--------------------------------------------------------------------------
    | Address search
    |--------------------------------------------------------------------------
    |
    | The GPS picker's address-search box geocodes a typed address through
    | OpenStreetMap's Nominatim service. The add-on answers the box at the Control
    | Panel route `gps/nominatim-search`, which fetches the endpoint below through
    | the egress guard bound to `UAMSWeb\Aether\Fieldtypes\Contracts\EgressGuard`.
    |
    | The Nominatim Usage Policy (https://operations.osmfoundation.org/policies/nominatim/)
    | requires an identifying `User-Agent`, at most one request per second, and
    | result attribution. The defaults honor that; a high-volume deployment should
    | point `MAP_NOMINATIM_ENDPOINT` at a dedicated or self-hosted instance.
    |
    */

    'nominatim' => [

        // The one host the default egress guard allows the controller to call.
        'endpoint' => env('MAP_NOMINATIM_ENDPOINT', 'https://nominatim.openstreetmap.org/search'),

        // A descriptive identifier sent as the request User-Agent, per policy.
        // Falls back to the app name and URL when left empty.
        'user_agent' => env('MAP_NOMINATIM_USER_AGENT', ''),

        // An optional contact email appended to each request, as the policy suggests.
        'email' => env('MAP_NOMINATIM_EMAIL', ''),

        // The application-wide ceiling on outbound requests per second.
        'max_requests_per_second' => (int) env('MAP_NOMINATIM_MAX_RPS', 1),

        // The most results requested per search.
        'limit' => (int) env('MAP_NOMINATIM_LIMIT', 5),

        // The shortest query the picker sends.
        'min_query_length' => (int) env('MAP_NOMINATIM_MIN_QUERY_LENGTH', 3),

        // A comma-separated list of ISO 3166-1 alpha-2 codes that bias results.
        'country_codes' => env('MAP_NOMINATIM_COUNTRY_CODES', 'us'),

        // The outbound request timeout in seconds.
        'timeout' => (int) env('MAP_NOMINATIM_TIMEOUT', 5),

    ],

];
