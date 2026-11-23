<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Control Panel labels for the form composite fieldtypes
|--------------------------------------------------------------------------
|
| Read as `aether-fieldtypes::fieldtypes.<key>`: the GPS fieldtype under `gps.*`
| and the partial date fieldtype under `partial_date.*`. A host overrides a
| line by publishing this file to `lang/vendor/aether-fieldtypes/en/fieldtypes.php`.
|
*/

return [

    'partial_date.title' => 'Partial Date',
    'partial_date.config.section_parts' => 'Date Parts',
    'partial_date.config.included_parts_display' => 'Included Parts',
    'partial_date.config.included_parts_instructions' => 'Which parts editors see and which are persisted as one canonical partial-date string in YAML.',
    'partial_date.config.included.year_only' => 'Year only',
    'partial_date.config.included.year_month' => 'Year and month',
    'partial_date.config.included.year_month_day' => 'Year, month, and day',
    'partial_date.config.included.month_only' => 'Month only',
    'partial_date.config.included.month_day' => 'Month and day',
    'partial_date.config.required_parts_display' => 'Required Parts',
    'partial_date.config.required_parts_instructions_ymd' => 'When this field is marked required in the blueprint, which saved parts must be filled. Only applies to modes that include year, month, and day.',
    'partial_date.config.required_parts_instructions_ym' => 'When this field is marked required, which saved parts must be filled. Only applies when including year and month (no day).',
    'partial_date.config.required_parts_instructions_md' => 'When this field is marked required, which saved parts must be filled. Only applies when including month and day (no year).',
    'partial_date.config.require.year_only' => 'Year only',
    'partial_date.config.require.year_month' => 'Year and month',
    'partial_date.config.require.year_month_day' => 'Year, month, and day',
    'partial_date.config.require.month_only' => 'Month only',
    'partial_date.config.require.month_day' => 'Month and day',
    'partial_date.config.year_default_display' => 'Default Year',
    'partial_date.config.year_default_instructions' => 'Pre-filled year for new entries when the blueprint has no default for this field. Ignored when included parts do not include year.',
    'partial_date.config.year_min_display' => 'Minimum Year',
    'partial_date.config.year_min_instructions' => 'Optional minimum year when year is included.',
    'partial_date.config.year_max_display' => 'Maximum Year',
    'partial_date.config.year_max_instructions' => 'Optional maximum year when year is included.',
    'partial_date.config.month_default_display' => 'Default Month',
    'partial_date.config.month_default_instructions' => 'Pre-filled month for new entries when the blueprint has no default for this field. Ignored when included parts do not include month.',
    'partial_date.config.day_default_display' => 'Default Day',
    'partial_date.config.day_default_instructions' => 'Pre-filled day of month for new entries when the blueprint has no default for this field. Ignored when included parts do not include day.',
    'partial_date.config.section_display' => 'Display',
    'partial_date.config.subfields_layout_display' => 'Layout',
    'partial_date.config.subfields_layout_instructions' => 'Inline shows year, month, and day inputs in a row (wrapping when needed). Stack shows each input full width, one per line.',
    'partial_date.config.subfields_layout_inline' => 'Inline',
    'partial_date.config.subfields_layout_stack' => 'Stack',
    'partial_date.add' => 'Add Date',
    'partial_date.field.year' => 'Year',
    'partial_date.field.month' => 'Month',
    'partial_date.field.day' => 'Day',
    'partial_date.validation.month_range' => 'The month must be between 1 and 12.',
    'partial_date.validation.day_requires_month' => 'Select a month before choosing a day.',
    'partial_date.validation.day_requires_year' => 'Select a year before choosing a day.',
    'partial_date.validation.month_requires_year' => 'Select a year before choosing a month.',
    'partial_date.validation.day_range' => 'The day must be between 1 and :max for the selected month.',
    'partial_date.validation.invalid_shape' => 'The partial date value must be structured data from the publisher.',
    'partial_date.validation.invalid_storage_format' => 'The partial date string is not valid for this field.',
    'partial_date.hierarchy.month_locked_tooltip' => 'Enter a year first, or populate month or day to continue editing.',
    'partial_date.hierarchy.day_locked_tooltip' => 'Pick a month, enter the year alone to set day without month, or keep an existing day value.',
    'gps.title' => 'GPS / Map',
    'gps.config.section_heading' => 'Map Defaults',
    'gps.config.default_latitude_display' => 'Default Latitude',
    'gps.config.default_latitude_instructions' => 'Latitude the picker centers on before a marker is placed.',
    'gps.config.default_longitude_display' => 'Default Longitude',
    'gps.config.default_longitude_instructions' => 'Longitude the picker centers on before a marker is placed.',
    'gps.config.default_zoom_display' => 'Default Zoom',
    'gps.config.default_zoom_instructions' => 'Initial zoom level (0 world – 19 building).',
    'gps.config.enable_address_search_display' => 'Enable Address Search',
    'gps.config.enable_address_search_instructions' => 'Show an OpenStreetMap (Nominatim) address-search box in the picker that geocodes a typed address and drops the pin on the chosen result.',
    'gps.field.address_search_label' => 'Search Address',
    'gps.field.address_search_instructions' => 'Type a street address, then choose a result to drop the pin. You can fine-tune it afterward by dragging the marker.',
    'gps.field.address_search_placeholder' => 'e.g., 4301 W Markham St, Little Rock',
    'gps.field.address_search_searching' => 'Searching…',
    'gps.field.address_search_no_results' => 'No matching addresses found.',
    'gps.field.address_search_error' => 'Address search is unavailable right now. Try again shortly.',
    'gps.field.address_search_attribution' => 'Address search © OpenStreetMap contributors (Nominatim)',
    'gps.field.paste_label' => 'Paste Coordinates',
    'gps.field.paste_instructions' => 'Paste a coordinate to drop a pin on the map below. Decimal, DMS, geo: URI, and UTM formats are accepted.',
    'gps.field.paste_error' => 'Could not read those coordinates. Try decimal, DMS, a geo: URI, or UTM.',
    'gps.field.set' => 'Set',
    'gps.field.map_label' => 'Map',
    'gps.field.map_instructions' => 'Click the map to set the pin. Once placed, drag the pin to move it to a new location.',
    'gps.field.generated_heading' => 'Generated Coordinates',
    'gps.field.generated_description' => 'Set automatically by the map or the paste box above. Read-only.',
    'gps.field.latitude' => 'Latitude',
    'gps.field.longitude' => 'Longitude',
    'gps.field.not_set' => 'Not set',
    'gps.field.reset_view' => 'Reset View',
    'gps.field.center_on_pin' => 'Center on Pin',
    'gps.field.clear' => 'Clear',
    'gps.field.street' => 'Street',
    'gps.field.aerial' => 'Aerial',

];
