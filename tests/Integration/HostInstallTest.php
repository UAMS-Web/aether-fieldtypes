<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Statamic\Facades\Addon;

/**
 * A host other than the site installs the add-on, imports its public Alpine entry, and renders
 * a form partial that marks the elements a test-only engine binds to. The binding itself is
 * pinned in `resources/js/alpine.test.js`; this pins the host half.
 *
 * The real engines move in with the fieldtype families (UAMS-Web/uams-statamic#997), which add
 * the criterion that a partial names an engine the add-on ships.
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
