<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Statamic\Facades\Addon;
use Statamic\Facades\Antlers;

/**
 * A host other than the site installs the add-on, imports its public Alpine entry, and renders
 * a form partial that marks the elements a test-only engine binds to. The binding itself is
 * pinned in `resources/js/alpine.test.js`; this pins the host half.
 *
 * The engines move in with the fieldtype families; the choice family adds the criterion that a
 * partial names an engine the add-on ships, pinned in the last test below.
 *
 * @command  vendor/bin/pest tests/Integration/HostInstallTest.php
 */

/**
 * The add-on's package root, with a trailing separator, as the host resolves it.
 *
 * @return string The directory of the booted add-on.
 */
function hostInstallAddonDirectory(): string
{
    // Find this add-on among those the host booted
    $addon = Addon::all()->first(fn (mixed $candidate): bool => $candidate instanceof Statamic\Addons\Addon && $candidate->package() === 'uams-web/aether-fieldtypes');
    expect($addon)->toBeInstanceOf(Statamic\Addons\Addon::class);

    /** @var Statamic\Addons\Addon $addon */
    return $addon->directory();
}

it('is discovered by the host, which resolves the public Alpine entry inside the add-on', function (): void {
    // Resolve the entry the way a host's build imports it, from the installed package root
    $entry = hostInstallAddonDirectory().'resources/js/alpine.js';

    // Expect the file, exporting the registration helper
    expect($entry)->toBeFile()
        ->and((string) file_get_contents($entry))->toContain('export { registerAlpineEngine }');
});

it('renders a form partial that marks the elements a test-only engine binds to', function (): void {
    // Render a partial from a host's own views, which names the engine by attribute
    $html = View::file(__DIR__.'/../Support/views/engine-partial.blade.php', ['engine' => 'quantity'])->render();

    // Expect the marker the test-only engine's selector matches
    expect($html)->toContain('data-test-engine="quantity"');
});

/**
 * Render the add-on's checkboxes partial the way a host's form renderer hands it a field that
 * opts into a calculation and carries the `required` rule.
 *
 * @return string The rendered markup.
 */
function hostInstallRenderedChoicePartial(): string
{
    // Render the partial from the add-on's own views, with no site code loaded
    return (string) Antlers::parse(
        (string) file_get_contents(hostInstallAddonDirectory().'resources/views/forms/fields/checkboxes.antlers.html'),
        [
            'handle' => 'pick',
            'id' => 'form-pick',
            'visibility' => 'visible',
            'value' => [],
            'validate' => ['required'],
            'error' => null,
            'options' => ['a' => 'A', 'b' => 'B'],
            'calculation_query' => true,
            'calculation_formula' => 'a, b',
        ],
        true,
    );
}

it('renders a choice partial whose calculation engine and checkbox group the add-on ships', function (): void {
    // Render the partial and read the add-on's public Alpine entry and the two files it exports from
    $html = hostInstallRenderedChoicePartial();
    $directory = hostInstallAddonDirectory().'resources/js/';
    $entry = (string) file_get_contents($directory.'alpine.js');
    $engine = (string) file_get_contents($directory.'lib/calculation-engine.js');
    $group = (string) file_get_contents($directory.'lib/checkbox-required-group.js');

    // Expect the partial to name the calculation selector and the group component by their markers
    expect($html)->toContain('data-calculation-type="text"')
        ->toContain('data-calculation-formula="a, b"')
        ->toContain('x-data="checkboxRequiredGroup"')
        // Expect the engine to bind that same selector, the group to register that same name, and the entry to export both
        ->and($engine)->toContain("const CALCULATED_INPUT_SELECTOR = '[data-calculation-type]'")
        ->and($group)->toContain("Alpine.data('checkboxRequiredGroup'")
        ->and($entry)->toContain('registerCalculationEngine')
        ->toContain('registerCheckboxRequiredGroup');
});

/**
 * Render one of the add-on's pricing partials the way a host's form renderer hands it a field.
 *
 * @param  string  $partial  The partial's file name without its extension.
 * @param  array<string, mixed>  $context  The field data the partial reads.
 * @return string The rendered markup.
 */
function hostInstallRenderedPricingPartial(string $partial, array $context): string
{
    // Render the partial from the add-on's own views, with no site code loaded
    return (string) Antlers::parse(
        (string) file_get_contents(hostInstallAddonDirectory().'resources/views/forms/fields/'.$partial.'.antlers.html'),
        ['handle' => 'pick', 'id' => 'form-pick', 'visibility' => 'visible', 'value' => '', 'error' => null, ...$context],
        true,
    );
}

it('renders each pricing partial with the selector its engine binds to, and the entry exports the engine', function (string $partial, array $context, string $marker, string $engine, string $selector, string $register): void {
    // Render the partial and read the add-on's public Alpine entry and the engine file it exports from
    $html = hostInstallRenderedPricingPartial($partial, mustBeStringKeyed($context, 'the field context'));
    $directory = hostInstallAddonDirectory().'resources/js/';
    $source = (string) file_get_contents($directory.'lib/'.$engine.'.js');
    $entry = (string) file_get_contents($directory.'alpine.js');

    // Expect the partial to carry the marker, the engine to bind that same selector, and the entry to export its registration
    expect($html)->toContain($marker)
        ->and($source)->toContain($selector)
        ->and($source)->toContain('export function '.$register.'()')
        ->and($entry)->toContain($register);
})->with([
    'option' => ['option', ['option_mode' => 'select', 'input_type' => 'select', 'options' => []], 'data-option-field', 'option-pricing-engine', "'[data-option-field]'", 'registerOptionPricingEngine'],
    'product' => ['product', ['selection_type' => 'single', 'formatted_price' => '$10.00', 'price' => 10, 'product_options' => []], 'data-product-field', 'product-pricing-engine', "'[data-product-field]'", 'registerProductPricingEngine'],
    'total' => ['total', ['prefix' => 'Total'], 'data-total-field', 'total-pricing-engine', "'[data-total-field]'", 'registerTotalPricingEngine'],
]);
