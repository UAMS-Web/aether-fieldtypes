<?php

declare(strict_types=1);

/**
 * The README's stated requirements and license must follow `composer.json`,
 * which is what a Packagist or Marketplace visitor and Composer itself read.
 *
 * @command  vendor/bin/pest tests/Unit/ReadmeTest.php
 */

/**
 * The parsed `composer.json`.
 *
 * @return array{name: string, license?: string, require: array<string, string>} The manifest's name, license and requirements.
 */
function readmeComposerManifest(): array
{
    /** @var array{name: string, license?: string, require: array<string, string>} */
    return json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * The README's text.
 *
 * @return string The full contents of `README.md`.
 */
function readmeText(): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
}

it('states the PHP floor that composer.json requires', function (): void {
    // Strip the constraint operators from the required PHP version
    $floor = ltrim(readmeComposerManifest()['require']['php'], '^>=~ ');

    // Expect the README to state that floor
    expect(readmeText())->toContain('PHP '.$floor.' or later');
});

it('states the statamic/cms floor that composer.json requires', function (): void {
    // Strip the constraint operators from the required Statamic version
    $floor = ltrim(readmeComposerManifest()['require']['statamic/cms'], '^>=~ ');

    // Expect the README to state that floor
    expect(readmeText())->toContain('Statamic '.$floor.' or later');
});

it('names the license that composer.json declares, once it declares one', function (): void {
    // Read the declared license, which the template leaves out until UAMS legal decides (uams-statamic#261)
    $license = readmeComposerManifest()['license'] ?? null;

    // Expect the README to name the license and point at the file, or no license to be declared
    expect($license === null || str_contains(readmeText(), $license.'. See [`LICENSE`](LICENSE)'))->toBeTrue();
});

it('requires the package by the name composer.json gives it', function (): void {
    expect(readmeText())->toContain('composer require '.readmeComposerManifest()['name']);
});

it('points every README anchor at a heading that exists', function (): void {
    // Collect the README's headings as GitHub anchors
    preg_match_all('/^#{2,4} (.+)$/m', readmeText(), $headings);
    $anchors = array_map(
        fn (string $heading): string => trim((string) preg_replace('/[^a-z0-9 -]/', '', strtolower($heading))),
        $headings[1],
    );
    $anchors = array_map(fn (string $anchor): string => str_replace(' ', '-', $anchor), $anchors);

    // Check each in-page link
    preg_match_all('/\]\(#([a-z0-9-]+)\)/', readmeText(), $links);

    foreach ($links[1] as $link) {
        // Expect a heading to carry that anchor
        expect($anchors)->toContain($link);
    }
});

it('links the files it points readers to, and they exist', function (): void {
    // Check each file the README points readers to
    foreach (['docs/DEVELOPMENT.md', 'docs/SUBMISSION.md'] as $path) {
        // Expect a link to the file, and the file on disk
        expect(readmeText())->toContain('('.$path.')')
            ->and(dirname(__DIR__, 2).'/'.$path)->toBeFile();
    }
});
