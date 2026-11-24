<?php

declare(strict_types=1);

use Statamic\Fields\Field;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Text;

/**
 * Tests for UAMSWeb\Aether\Fieldtypes\Fieldtypes\Text: its Control Panel identity, the config lists it hands the Input
 * trait, and its (empty) own validation rules.
 *
 * @command  vendor/bin/pest tests/Integration/Fieldtypes/TextFieldtypeTest.php
 */
covers(Text::class);

/**
 * Build a Text fieldtype bound to a Field with the given config.
 *
 * @param  array<string, mixed>  $config  Field config merged over the type handle
 */
function textFieldtype(array $config = []): Text
{
    // Build the fieldtype under test
    $fieldtype = new Text;

    // Bind a Field with the given config over the type handle
    $fieldtype->setField(new Field('text', array_merge(['type' => 'text'], $config)));

    return $fieldtype;
}

/**
 * Call a protected Text method via reflection.
 *
 * @param  string  $method  Protected method name to invoke
 * @return mixed Reflected method return value
 */
function textCall(string $method): mixed
{
    // Reflect the protected method and invoke it on a fresh fieldtype
    return new ReflectionMethod(Text::class, $method)->invoke(textFieldtype());
}

it('identifies itself in the Control Panel with the parent text icon', function (): void {
    $fieldtype = textFieldtype();

    expect($fieldtype->component())->toBe('custom_text')
        ->and($fieldtype->icon())->toBe((new Statamic\Fieldtypes\Text)->icon());
});

it('allows its autocomplete keys exactly', function (): void {
    expect(textCall('getAllowedAutocompleteKeys'))->toBe([
        'name',
        'honorific-prefix',
        'given-name',
        'additional-name',
        'family-name',
        'honorific-suffix',
        'nickname',
        'bday',
        'bday-day',
        'bday-month',
        'bday-year',
        'sex',
        'street-address',
        'address-line1',
        'address-line2',
        'address-line3',
        'address-level1',
        'address-level2',
        'address-level3',
        'address-level4',
        'country',
        'country-name',
        'organization',
        'organization-title',
        'cc-name',
        'cc-given-name',
        'cc-additional-name',
        'cc-family-name',
        'cc-number',
        'cc-exp',
        'cc-exp-month',
        'cc-exp-year',
        'cc-csc',
        'cc-type',
        'username',
        'one-time-code',
        'webauthn',
        'language',
        'transaction-currency',
    ]);
});

it('pins every config field override exactly', function (): void {
    expect(textCall('getConfigFieldOverrides'))->toBe([
        'input_type' => [
            'default' => 'text',
        ],
        'calculation_type' => [
            'default' => 'text',
        ],
    ]);
});

it('pins every hidden config field handle exactly', function (): void {
    expect(textCall('getHiddenConfigFields'))->toBe([
        'accept',
        'accesskey',
        'alt',
        'anchor',
        'calculation_type',
        'capture',
        'checked',
        'class',
        'contenteditable',
        'dir',
        'dirname',
        'draggable',
        'enterkeyhint',
        'exportparts',
        'focus',
        'form',
        'formaction',
        'formenctype',
        'formmethod',
        'formnovalidate',
        'formtarget',
        'height',
        'id',
        'inert',
        'is',
        'itemid',
        'itemprop',
        'itemref',
        'itemscope',
        'itemtype',
        'lang',
        'list',
        'max',
        'min',
        'multiple',
        'name',
        'nonce',
        'part',
        'pattern',
        'popover',
        'popovertarget',
        'popovertargetaction',
        'role',
        'size',
        'slot',
        'src',
        'step',
        'style',
        'tabindex',
        'title',
        'width',
    ]);
});

it('adds no validation rules of its own', function (): void {
    expect(textFieldtype()->rules())->toBe([]);
});
