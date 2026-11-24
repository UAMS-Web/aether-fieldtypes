<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Validation messages for the input fieldtypes
|--------------------------------------------------------------------------
|
| Read as `aether-fieldtypes::validation.<key>`. A host overrides a message
| by publishing this file to `lang/vendor/aether-fieldtypes/en/validation.php`.
|
*/

return [

    'asset_minimum_dimensions' => [
        'both' => ':name must be at least :min_widthx:min_height pixels; it is :widthx:height.',
        'height' => ':name must be at least :min_height pixels tall; it is :height.',
        'width' => ':name must be at least :min_width pixels wide; it is :width.',
    ],

    'date_boundary' => [
        'on_or_after' => 'This date must be on or after :date.',
        'on_or_before' => 'This date must be on or before :date.',
        'period_unavailable' => 'This date falls within a period that is not available.',
        'weekday_not_allowed' => 'This date falls on a day of the week that is not allowed.',
    ],

    'decimal_valid' => 'The value must be a valid decimal number.',

    'international_phone' => 'Enter a valid international phone number (e.g., `+1-555-555-5555`).',

    'latitude' => 'The value must be a decimal number between -90.0 and 90.0.',

    'longitude' => 'The value must be a decimal number between -180.0 and 180.0.',

    'month' => [
        'after_or_equal' => 'The month must be after or equal to :min.',
        'before_or_equal' => 'The month must be before or equal to :max.',
    ],

    'national_phone' => [
        'generic' => 'Enter a valid national phone number for :country.',
        'with_example' => 'Enter a valid national phone number for :country (e.g., :example).',
    ],

    'replicator' => [
        'at_least_one_enabled_set' => 'At least one set must be enabled.',
        'min_sets' => [
            'one' => 'At least one set is required.',
            'multiple' => 'At least :min sets are required.',
        ],
        'min_sets_instructions' => 'Set a minimum number of sets.',
    ],

    'sms_short_code' => 'The value must be a valid U.S. SMS short code consisting of five or six digits, with the first digit being 0 or any digit from 2 to 9 (e.g., \'01234\', \'012345\', \'23456\', \'234567\').',

    'week' => [
        'after_or_equal' => 'The week must be after or equal to :min.',
        'before_or_equal' => 'The week must be before or equal to :max.',
    ],

    'zip_code' => 'The value must be a valid U.S. ZIP Code beginning with five digits, optionally followed by a hyphen and four digits (e.g., \'12345\' or \'12345-6789\').',

];
