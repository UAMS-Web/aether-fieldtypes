<?php

declare(strict_types=1);

use Statamic\Facades\Antlers;
use Statamic\Fields\Fieldtype;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Checkboxes;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\CssSelector;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Currency;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Email;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Floatval;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Hidden;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Identifier;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Integer;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Month;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Password;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Phone;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Radio;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Select;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Text;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Time;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Url;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Username;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Week;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Xpath;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\ZipCode;
use UAMSWeb\Aether\Fieldtypes\Ownership\Claim;
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnerMap;

/**
 * The public form partials the input fieldtypes ship, and the handles the add-on serves.
 *
 * @command  vendor/bin/pest tests/Integration/FormFieldPartialsTest.php
 */
it('serves every input fieldtype under the handle it replaces', function (string $handle, Fieldtype $fieldtype): void {
    // Expect the host's fieldtype registry to resolve the handle to the add-on class, without a stand-in
    expect($fieldtype::handle())->toBe($handle)
        ->and(app('statamic.fieldtypes')->get($handle))->toBe($fieldtype::class);
})->with([
    'css_selector' => ['css_selector', new CssSelector],
    'currency' => ['currency', new Currency],
    'email' => ['email', new Email],
    'float' => ['float', new Floatval],
    'hidden' => ['hidden', new Hidden],
    'identifier' => ['identifier', new Identifier],
    'integer' => ['integer', new Integer],
    'month' => ['month', new Month],
    'password' => ['password', new Password],
    'phone' => ['phone', new Phone],
    'text' => ['text', new Text],
    'time' => ['time', new Time],
    'url' => ['url', new Url],
    'username' => ['username', new Username],
    'week' => ['week', new Week],
    'xpath' => ['xpath', new Xpath],
    'zip_code' => ['zip_code', new ZipCode],
]);

it('offers a public form partial for each handle that has one, and the default the rest render through', function (string $name): void {
    // Expect Statamic's own view namespace to find the add-on's partial
    expect(view()->exists('statamic::forms.fields.'.$name))->toBeTrue();
})->with(['default', 'email', 'float', 'hidden', 'integer', 'password', 'phone', 'text', 'time', 'url', 'username']);

it('resolves a fieldtype without a partial of its own to the default partial', function (): void {
    // Expect the default partial for a handle the add-on has no partial for, and the handle's partial where it has one
    expect((new Xpath)->view())->toBe('statamic::forms.fields.default')
        ->and((new Email)->view())->toBe('statamic::forms.fields.email');
});

it('renders the email partial through the default partial the add-on ships', function (): void {
    // Render a field the way Statamic's form renderer hands it to the partial
    $html = (string) Antlers::parse(
        file_get_contents(__DIR__.'/../../resources/views/forms/fields/email.antlers.html') ?: '',
        [
            'handle' => 'contact_email',
            'id' => 'form-contact_email',
            'type' => 'email',
            'input_type' => 'email',
            'visibility' => 'visible',
            'validate' => [],
            'value' => 'a@example.org',
        ],
        true,
    );

    // Expect one email input carrying the handle, the id and the value
    expect($html)->toContain('type="email"')
        ->toContain('name="contact_email"')
        ->toContain('id="form-contact_email"')
        ->toContain('value="a@example.org"');
});

it('renders a hidden input with no label markup, and honors the value', function (): void {
    // Render the hidden partial
    $html = (string) Antlers::parse(
        file_get_contents(__DIR__.'/../../resources/views/forms/fields/hidden.antlers.html') ?: '',
        ['handle' => 'token', 'id' => 'form-token', 'type' => 'hidden', 'value' => 'abc'],
        true,
    );

    // Expect a hidden input and nothing else
    expect($html)->toContain('type="hidden"')
        ->toContain('name="token"')
        ->toContain('value="abc"')
        ->not->toContain('form-control');
});

it('registers a Control Panel component for every input fieldtype that has one of its own', function (): void {
    // Read this add-on's claims through the add-ons the test application booted
    $own = array_map(
        fn (Claim $claim): string => $claim->kind.'|'.$claim->name,
        array_filter(
            OwnerMap::fromInstalledAddons()->claims(),
            fn (Claim $claim): bool => $claim->owner === 'uams-web/aether-fieldtypes',
        ),
    );

    // Hidden and Time render through Statamic's own components, so they register none
    $fieldtypes = [new CssSelector, new Currency, new Email, new Floatval, new Identifier, new Integer, new Month, new Password, new Phone, new Text, new Url, new Username, new Week, new Xpath, new ZipCode];
    $expected = array_map(fn (Fieldtype $fieldtype): string => Claim::COMPONENT.'|'.$fieldtype->component().'-fieldtype', $fieldtypes);

    // Expect each component name claimed by the add-on, which the owner test then holds to one owner
    expect($own)->toContain(...$expected)
        ->and(array_map(fn (Fieldtype $fieldtype): string => $fieldtype->component(), [new Hidden, new Time]))->toBe(['hidden', 'time'])
        ->and($own)->not->toContain(Claim::COMPONENT.'|hidden-fieldtype', Claim::COMPONENT.'|time-fieldtype');
});

it('serves the choice fieldtypes under the handles they replace', function (string $handle, Fieldtype $fieldtype): void {
    // Expect the host's fieldtype registry to resolve each handle to the add-on class
    expect($fieldtype::handle())->toBe($handle)
        ->and(app('statamic.fieldtypes')->get($handle))->toBe($fieldtype::class);
})->with([
    'checkboxes' => ['checkboxes', new Checkboxes],
    'radio' => ['radio', new Radio],
    'select' => ['select', new Select],
]);

it('offers a public form partial for each choice fieldtype', function (string $name): void {
    // Expect Statamic's own view namespace to find the add-on's partial
    expect(view()->exists('statamic::forms.fields.'.$name))->toBeTrue();
})->with(['checkboxes', 'radio', 'select']);

it('claims the Control Panel components of Checkboxes and Radio, and none for Select', function (): void {
    // Read this add-on's claims through the add-ons the test application booted
    $own = array_map(
        fn (Claim $claim): string => $claim->kind.'|'.$claim->name,
        array_filter(
            OwnerMap::fromInstalledAddons()->claims(),
            fn (Claim $claim): bool => $claim->owner === 'uams-web/aether-fieldtypes',
        ),
    );

    // Select renders through Statamic's own component, so it registers none
    expect($own)->toContain(Claim::COMPONENT.'|custom_checkboxes-fieldtype', Claim::COMPONENT.'|custom_radio-fieldtype')
        ->and($own)->not->toContain(Claim::COMPONENT.'|select-fieldtype', Claim::COMPONENT.'|custom_select-fieldtype');
});
