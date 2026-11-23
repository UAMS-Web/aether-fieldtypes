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

    'month' => [
        'after_or_equal' => 'The month must be after or equal to :min.',
        'before_or_equal' => 'The month must be before or equal to :max.',
    ],

    'sms_short_code' => 'The value must be a valid U.S. SMS short code consisting of five or six digits, with the first digit being 0 or any digit from 2 to 9 (e.g., \'01234\', \'012345\', \'23456\', \'234567\').',

    'week' => [
        'after_or_equal' => 'The week must be after or equal to :min.',
        'before_or_equal' => 'The week must be before or equal to :max.',
    ],

    'zip_code' => 'The value must be a valid U.S. ZIP Code beginning with five digits, optionally followed by a hyphen and four digits (e.g., \'12345\' or \'12345-6789\').',

];
