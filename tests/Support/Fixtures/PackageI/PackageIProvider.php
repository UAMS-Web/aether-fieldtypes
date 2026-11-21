<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageI;

use Statamic\Providers\AddonServiceProvider;

/**
 * Fixture provider for a package whose entry registers two components under computed names long
 * enough that the owner check has to shorten the excerpt it reports.
 */
final class PackageIProvider extends AddonServiceProvider
{
    /** @var array{input: list<string>, publicDirectory: string} */
    protected $vite = [
        'input' => ['scripts/entry.js'],
        'publicDirectory' => 'dist',
    ];
}
