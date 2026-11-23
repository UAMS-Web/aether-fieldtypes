<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageJ;

use Statamic\Providers\AddonServiceProvider;

/**
 * Fixture provider for a package whose entry calls a method named `import` on an object, which is
 * not a dynamic `import()`, before it registers a component.
 */
final class PackageJProvider extends AddonServiceProvider
{
    /** @var array{input: list<string>, publicDirectory: string} */
    protected $vite = [
        'input' => ['scripts/entry.js'],
        'publicDirectory' => 'dist',
    ];
}
