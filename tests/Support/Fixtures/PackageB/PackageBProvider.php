<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageB;

use Statamic\Fields\Fieldtype;
use Statamic\Providers\AddonServiceProvider;

/**
 * Fixture provider for a second package that registers the same handle and component name as
 * the first, and one name of its own.
 */
final class PackageBProvider extends AddonServiceProvider
{
    /** @var list<class-string<Fieldtype>> */
    protected $fieldtypes = [SameHandleFieldtype::class, OwnHandleFieldtype::class];

    /** @var array{input: list<string>, publicDirectory: string} */
    protected $vite = [
        'input' => ['scripts/package-b.js'],
        'publicDirectory' => 'dist',
    ];
}
