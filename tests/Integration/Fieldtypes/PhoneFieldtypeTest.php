<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Statamic\Fields\Field;
use UAMSWeb\Aether\Enums\Country;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Phone;
use UAMSWeb\Aether\Fieldtypes\Rules\InternationalPhone;
use UAMSWeb\Aether\Fieldtypes\Rules\NationalPhone;
use UAMSWeb\Aether\Fieldtypes\Rules\SmsShortCode;
use UAMSWeb\Aether\Fieldtypes\Services\PhoneUtil;

/**
 * @covers \UAMSWeb\Aether\Fieldtypes\Fieldtypes\Phone
 *
 * @param  array<string, mixed>  $config
 *
 * @command  vendor/bin/pest tests/Integration/Fieldtypes/PhoneFieldtypeTest.php
 */
function makePhone(array $config = []): Phone
{
    $fieldtype = new Phone;
    $fieldtype->setField(new Field('phone', array_merge(['type' => 'phone'], $config)));

    return $fieldtype;
}

it('selects the validation rule matching the configured preset', function (string $preset, string $ruleClass): void {
    // Compared by exact class rather than instanceof: the rule set is a closed union
    // PHPStan already knows, so an instanceof check over it cannot fail — and the
    // preset must select precisely this rule, not merely a subclass of it.
    expect(makePhone(['preset' => $preset])->rules()[0]::class)->toBe($ruleClass);
})->with([
    'international' => ['tel', InternationalPhone::class],
    'national' => ['tel-national', NationalPhone::class],
    'sms short code' => ['sms-short-code', SmsShortCode::class],
]);

it('defaults to the national phone rule when no preset is configured', function (): void {
    expect(makePhone()->rules()[0])->toBeInstanceOf(NationalPhone::class);
});

it('produces no rules for an unrecognized preset', function (): void {
    expect(makePhone(['preset' => 'nonsense'])->rules())->toBe([]);
});

it('normalizes empty-ish input to an empty string', function (mixed $input): void {
    expect((new Phone)->normalizeValue($input, ['preset' => 'tel-national', 'country' => 'US']))->toBe('');
})->with([
    'empty' => [''],
    'whitespace' => ['   '],
    'zero' => ['0'],
]);

it('normalizes a national number to E.164 using the configured country', function (): void {
    expect((new Phone)->normalizeValue('(479) 555-0100', ['preset' => 'tel-national', 'country' => 'US']))
        ->toBe('+14795550100');
});

it('normalizes an international number to E.164', function (): void {
    expect((new Phone)->normalizeValue('+1 479 555 0100', ['preset' => 'tel']))
        ->toBe('+14795550100');
});

it('cleans an sms short code without E.164 formatting', function (): void {
    expect((new Phone)->normalizeValue('12345', ['preset' => 'sms-short-code']))->toBe('12345');
});

it('returns the original value when a phone number cannot be parsed', function (): void {
    // A libphonenumber parse failure is caught and the raw input returned (logged,
    // never thrown), so an unparseable submission never 500s the save.
    $unparseable = 'zzzzzzzz';

    expect((new Phone)->normalizeValue($unparseable, ['preset' => 'tel']))->toBe($unparseable);
});

it('formats a value for display via the national format for tel-national', function (): void {
    // The fieldtype must pick the national display format for the national preset —
    // comparing against PhoneUtil with 'national' fails if the branch selects another.
    expect((new Phone)->formatValueForDisplay('+14795550100', ['preset' => 'tel-national']))
        ->toBe(PhoneUtil::formatForDisplay('+14795550100', 'national'));
});

it('formats a value for display via the international format for tel', function (): void {
    // The international preset must pick the international display format; paired with
    // the national test (the two formats differ), this pins the format-selection
    // branch — a mutation forcing one format unconditionally breaks one of the two.
    expect((new Phone)->formatValueForDisplay('+14795550100', ['preset' => 'tel']))
        ->toBe(PhoneUtil::formatForDisplay('+14795550100', 'international'));
});

it('passes an sms short code through display formatting unchanged', function (): void {
    expect((new Phone)->formatValueForDisplay('12345', ['preset' => 'sms-short-code']))->toBe('12345');
});

/**
 * Call a protected Phone method via reflection.
 *
 * @param  string  $method  Protected method name to invoke
 * @param  mixed  ...$arguments  Arguments handed to the method
 * @return mixed Reflected method return value
 */
function phoneCall(string $method, mixed ...$arguments): mixed
{
    // Reflect the protected method and invoke it on a fresh fieldtype
    return new ReflectionMethod(Phone::class, $method)->invoke(makePhone(), ...$arguments);
}

it('identifies itself in the Control Panel', function (): void {
    $fieldtype = makePhone();

    expect(Phone::title())->toBe('Phone')
        ->and($fieldtype->component())->toBe('phone_input')
        ->and($fieldtype->icon())->toBe('phone-telephone-call')
        ->and($fieldtype->selectable())->toBeTrue()
        ->and($fieldtype->selectableInForms())->toBeTrue();
});

it('exposes its search keywords exactly', function (): void {
    expect(makePhone()->keywords())->toBe([
        'call',
        'contact',
        'dial',
        'number',
        'phone',
        'tel',
        'telephone',
    ]);
});

it('belongs to its fieldtype categories exactly', function (): void {
    expect(makePhone()->categories())->toBe([
        'number',
        'text',
        'special',
    ]);
});

it('allows its autocomplete keys exactly', function (): void {
    expect(phoneCall('getAllowedAutocompleteKeys'))->toBe([
        'tel',
        'tel-national',
    ]);
});

it('pins every config field override exactly', function (): void {
    expect(phoneCall('getConfigFieldOverrides'))->toBe([
        'input_type' => [
            'default' => 'tel',
        ],
        'inputmode' => [
            'default' => 'tel',
        ],
        'spellcheck' => [
            'default' => 'false',
        ],
        'calculation_type' => [
            'default' => 'text',
        ],
    ]);
});

it('pins every hidden config field handle exactly', function (): void {
    expect(phoneCall('getHiddenConfigFields'))->toBe([
        'accesskey',
        'anchor',
        'antlers',
        'append',
        'autocapitalize',
        'autocomplete_query',
        'autocorrect',
        'calculation_type',
        'class',
        'dir',
        'draggable',
        'enterkeyhint',
        'debounce',
        'exportparts',
        'focus',
        'form',
        'id',
        'inert',
        'input_mask',
        'input_mask_append_text',
        'input_mask_append_text_enabled',
        'input_mask_autofix',
        'input_mask_blocks',
        'input_mask_class',
        'input_mask_compiledMasks',
        'input_mask_currentMask',
        'input_mask_definitions',
        'input_mask_displayChar',
        'input_mask_eager',
        'input_mask_enum',
        'input_mask_exposeMask',
        'input_mask_from',
        'input_mask_lazy',
        'input_mask_mapToRadix',
        'input_mask_mask',
        'input_mask_mask_MaskedDynamic',
        'input_mask_mask_MaskedFunction',
        'input_mask_mask_MaskedPattern',
        'input_mask_mask_MaskedRegExp',
        'input_mask_max',
        'input_mask_maxLength',
        'input_mask_min',
        'input_mask_normalizeZeros',
        'input_mask_overwrite',
        'input_mask_padFractionalZeros',
        'input_mask_parent',
        'input_mask_pattern',
        'input_mask_placeholderChar',
        'input_mask_prepare_case',
        'input_mask_prepend_text',
        'input_mask_prepend_text_enabled',
        'input_mask_radix',
        'input_mask_repeat',
        'input_mask_scale',
        'input_mask_skipInvalid',
        'input_mask_store_unmasked',
        'input_mask_thousandsSeparator',
        'input_mask_to',
        'inputmode',
        'is',
        'itemid',
        'itemprop',
        'itemref',
        'itemscope',
        'itemtype',
        'lang',
        'list',
        'maxlength',
        'minlength',
        'name',
        'nonce',
        'part',
        'pattern',
        'popover',
        'prepend',
        'role',
        'size',
        'slot',
        'spellcheck',
        'style',
        'tabindex',
        'title',
        'transform_case',
        'translate',
        'virtualkeyboardpolicy',
        'writingsuggestions',
    ]);
});

it('offers a country select over every country, shown for the national preset', function (): void {
    /** @var array<string, array<string, mixed>> $fields */
    $fields = phoneCall('getAdditionalConfigFields');

    // Build the options the way the select expects them: alpha-2 code to country name
    $options = [];
    foreach (Country::cases() as $country) {
        $options[$country->alpha2()] = $country->country_name();
    }

    expect(array_keys($fields))->toBe(['country'])
        ->and($fields['country'])->toBe([
            'display' => 'Country',
            'instructions' => 'Select the country for phone number validation and formatting.',
            'type' => 'select',
            'options' => $options,
            'default' => 'US',
            'if' => [
                'preset' => 'tel-national',
            ],
        ])
        ->and($options)->not->toBeEmpty()
        ->and($options['US'])->toBe(Country::from('US')->country_name());
});

it('pins every preset, with example numbers from the phone library', function (): void {
    expect(phoneCall('getPresets'))->toBe([
        'display' => 'Phone Format',
        'instructions' => 'Choose the phone number format for this field.',
        'default' => 'tel-national',
        'options' => [
            'tel' => [
                'label' => 'International (with country code)',
                'config' => [
                    'autocomplete_query' => 'on',
                    'autocomplete' => 'tel',
                    'pattern' => '^\\+?\\d{1,3}[- ]?\\(?\\d{3}\\)?[- ]?\\d{3}[- ]?\\d{4}$',
                    'placeholder' => 'e.g., +1 201-555-0123',
                    'parse_as_phone' => true,
                ],
            ],
            'tel-national' => [
                'label' => 'National (no country code)',
                'config' => [
                    'autocomplete_query' => 'on',
                    'autocomplete' => 'tel-national',
                    'pattern' => '^\\(?\\d{3}\\)?[- ]?\\d{3}[- ]?\\d{4}$',
                    'placeholder' => 'e.g., (201) 555-0123',
                    'parse_as_phone' => true,
                ],
            ],
            'sms-short-code' => [
                'label' => 'U.S. SMS Short Code',
                'config' => [
                    'autocomplete_query' => 'off',
                    'placeholder' => 'e.g., 01234 or 234567',
                    'maxlength' => '6',
                    'minlength' => '5',
                    'parse_as_phone' => false,
                ],
            ],
        ],
    ]);
});

it('sets the national placeholder from the configured country on preload', function (array $config, string $placeholder): void {
    /** @var array<string, mixed> $result */
    $result = phoneCall('modifyPreloadConfig', $config);

    expect($result['placeholder'])->toBe($placeholder);
})->with([
    'no preset, no country' => [[], 'e.g., (201) 555-0123'],
    'the national preset with a country' => [['preset' => 'tel-national', 'country' => 'GB'], 'e.g., 0121 234 5678'],
    'a country that is not a string' => [['preset' => 'tel-national', 'country' => 5], 'e.g., (201) 555-0123'],
    'a country with no example number' => [['preset' => 'tel-national', 'country' => 'ZZ'], 'e.g., 855-555-6502'],
]);

it('leaves the config alone on preload for any other preset', function (string $preset): void {
    $config = ['preset' => $preset, 'country' => 'GB', 'placeholder' => 'kept'];

    expect(phoneCall('modifyPreloadConfig', $config))->toBe($config);
})->with(['tel', 'sms-short-code', 'nonsense']);

it('hands the configured country to the national rule, and defaults it to the U.S.', function (): void {
    /** @var NationalPhone $gb */
    $gb = makePhone(['preset' => 'tel-national', 'country' => 'GB'])->rules()[0];
    /** @var NationalPhone $default */
    $default = makePhone()->rules()[0];

    // A U.K. number passes only the rule built for the U.K.
    expect($gb->passes('phone', '020 7946 0958'))->toBeTrue()
        ->and($default->passes('phone', '020 7946 0958'))->toBeFalse()
        ->and($default->passes('phone', '501-686-7000'))->toBeTrue();
});

it('logs an unexpected preset by name, once, and builds no rule for it', function (): void {
    Log::shouldReceive('warning')->once()->with('Phone rules() - Unexpected preset', ['preset' => 'nonsense']);

    expect(makePhone(['preset' => 'nonsense'])->rules())->toBe([]);
});

it('normalizes a value of any scalar type, and anything else to an empty string', function (mixed $input, string $expected): void {
    expect((new Phone)->normalizeValue($input, ['preset' => 'tel-national', 'country' => 'US']))->toBe($expected);
})->with([
    'an integer' => [5016867000, '+15016867000'],
    'integer zero' => [0, ''],
    'an array' => [['501-686-7000'], ''],
    'null' => [null, ''],
    'a trimmed string' => ["  501-686-7000\n", '+15016867000'],
]);

it('normalizes with the preset of the config, not the national default', function (): void {
    // Under the national default these would parse against the U.S. and come back different
    expect((new Phone)->normalizeValue('442079460958', ['preset' => 'tel']))->toBe('+442079460958')
        ->and((new Phone)->normalizeValue('12345', ['preset' => 'sms-short-code']))->toBe('12345')
        ->and((new Phone)->normalizeValue('020 7946 0958', ['preset' => 'tel-national', 'country' => 'GB']))->toBe('+442079460958');
});

it('defaults to the national preset and the U.S. when the config names neither', function (): void {
    expect((new Phone)->normalizeValue('501-686-7000'))->toBe('+15016867000')
        ->and((new Phone)->normalizeValue('501-686-7000', ['preset' => 'tel-national', 'country' => 5]))->toBe('+15016867000');
});

it('adds the plus to an international number that lacks it', function (): void {
    expect((new Phone)->normalizeValue(' 15016867000 ', ['preset' => 'tel']))->toBe('+15016867000')
        ->and((new Phone)->normalizeValue(15016867000, ['preset' => 'tel']))->toBe('+15016867000');
});

it('trims an sms short code and keeps its digits', function (): void {
    expect((new Phone)->normalizeValue("  012345 \n", ['preset' => 'sms-short-code']))->toBe('012345')
        ->and((new Phone)->normalizeValue(12345, ['preset' => 'sms-short-code']))->toBe('12345');
});

it('returns the original value and logs the failure when parsing throws', function (string $preset, string $message, array $context): void {
    // A value no parser accepts, handed to a library call that throws on it
    $unparseable = new Phone;

    Log::shouldReceive('error')->once()->withArgs(
        fn (string $logged, array $logContext): bool => $logged === $message
            && ($logContext['input_value'] ?? null) === 'zzzz'
            && array_diff_key($logContext, ['input_value' => 1, 'exception' => 1]) === array_diff_key($context, ['input_value' => 1, 'exception' => 1])
            && is_string($logContext['exception'] ?? null)
            && $logContext['exception'] !== ''
    );

    expect($unparseable->normalizeValue('zzzz', ['preset' => $preset, 'country' => 'US']))->toBe('zzzz');
})->with([
    'international' => ['tel', 'Phone normalizeValue() exception (international)', []],
    'national' => ['tel-national', 'Phone normalizeValue() exception (national)', ['country' => 'US']],
]);

it('logs a warning and returns the value when the preset is not recognized', function (): void {
    Log::shouldReceive('warning')->once()->with('Phone normalizeValue() unrecognized preset', ['input_value' => '5016867000', 'preset' => 'nonsense']);

    expect((new Phone)->normalizeValue('5016867000', ['preset' => 'nonsense']))->toBe('5016867000');
});

it('formats a value of any scalar type for display, and anything else as the empty string', function (mixed $value, string $expected): void {
    expect((new Phone)->formatValueForDisplay($value, ['preset' => 'tel-national']))->toBe($expected);
})->with([
    'an integer, which has no plus to parse' => [15016867000, '15016867000'],
    'an array' => [['+15016867000'], ''],
    'null' => [null, ''],
]);

it('formats nationally when no preset is configured', function (): void {
    // The national format is the default; the international one differs
    expect((new Phone)->formatValueForDisplay('+15016867000'))->toBe('501-686-7000');
});
