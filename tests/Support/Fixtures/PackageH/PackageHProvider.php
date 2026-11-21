<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageH;

use Statamic\Providers\AddonServiceProvider;

/**
 * Fixture provider for a package whose entry imports a file in a sibling directory whose name
 * begins with the package's own, which a path-prefix test without a separator would take as inside.
 */
final class PackageHProvider extends AddonServiceProvider
{
    /** @var array{input: list<string>, publicDirectory: string} */
    protected $vite = [
        'input' => ['scripts/entry.js'],
        'publicDirectory' => 'dist',
    ];
}
