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
