<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageC;

use Statamic\Fields\Fieldtype;
use Statamic\Providers\AddonServiceProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageC\Fieldtypes\ListedFieldtype;

/**
 * Fixture provider for a package that autoloads its fieldtypes from a `Fieldtypes` folder and also
 * lists one of them, and declares a script, a stylesheet and a script that does not exist.
 */
final class PackageCProvider extends AddonServiceProvider
{
    /** @var list<class-string<Fieldtype>> */
    protected $fieldtypes = [ListedFieldtype::class];

    /** @var array{input: list<string>, publicDirectory: string} */
    protected $vite = [
        'input' => ['scripts/package-c.js', 'scripts/package-c.css', 'scripts/missing.js'],
        'publicDirectory' => 'dist',
    ];
}
