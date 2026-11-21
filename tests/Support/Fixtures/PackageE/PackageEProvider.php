<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageE;

use Statamic\Providers\AddonServiceProvider;

/**
 * Fixture provider for a package whose entry script registers some components itself and the
 * rest from modules it imports, by every import form the owner check follows.
 */
final class PackageEProvider extends AddonServiceProvider
{
    /** @var array{input: list<string>, publicDirectory: string} */
    protected $vite = [
        'input' => ['scripts/entry.js'],
        'publicDirectory' => 'dist',
    ];
}
