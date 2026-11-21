<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageG;

use Statamic\Providers\AddonServiceProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageC\Fieldtypes\ListedFieldtype;

/**
 * Fixture provider that lists a fieldtype class which does not exist, as a package whose
 * autoloader does not reach a listed class would, followed by one that does.
 */
final class PackageGProvider extends AddonServiceProvider
{
    /** @var list<string> */
    protected $fieldtypes = ['Not\\A\\Fieldtype', ListedFieldtype::class];
}
