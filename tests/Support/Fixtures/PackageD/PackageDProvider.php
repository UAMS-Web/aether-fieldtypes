<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageD;

use Statamic\Fields\Fieldtype;
use Statamic\Providers\AddonServiceProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageC\Fieldtypes\AutoloadedFieldtype;

/**
 * Fixture provider for a second package that lists the first package's fieldtype, and so takes
 * its handle, and registers the same component name.
 */
final class PackageDProvider extends AddonServiceProvider
{
    /** @var list<class-string<Fieldtype>> */
    protected $fieldtypes = [AutoloadedFieldtype::class];

    /** @var array{input: list<string>, publicDirectory: string} */
    protected $vite = [
        'input' => ['scripts/package-d.js'],
        'publicDirectory' => 'dist',
    ];
}
