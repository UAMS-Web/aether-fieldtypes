<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageF;

use Statamic\Providers\AddonServiceProvider;

/**
 * Fixture provider for a package whose entry script hides registrations from the owner check:
 * names that are not string literals, a glob, a computed dynamic import, an import that does not
 * exist and one that leaves the package.
 */
final class PackageFProvider extends AddonServiceProvider
{
    /** @var array{input: list<string>, publicDirectory: string} */
    protected $vite = [
        'input' => ['scripts/entry.js'],
        'publicDirectory' => 'dist',
    ];
}
