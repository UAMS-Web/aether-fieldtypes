<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageA;

use Statamic\Fields\Fieldtype;
use Statamic\Providers\AddonServiceProvider;

/**
 * Fixture provider for a first package that registers the `fixture_shared` handle and the
 * `shared-fieldtype` component.
 */
final class PackageAProvider extends AddonServiceProvider
{
    /** @var list<class-string<Fieldtype>> */
    protected $fieldtypes = [SharedHandleFieldtype::class];

    /** @var array{input: list<string>, publicDirectory: string} */
    protected $vite = [
        'input' => ['scripts/package-a.js'],
        'publicDirectory' => 'dist',
    ];
}
