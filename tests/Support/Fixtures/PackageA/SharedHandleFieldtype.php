<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageA;

use Statamic\Fields\Fieldtype;

/**
 * Fixture fieldtype that takes the `fixture_shared` handle in the first package.
 */
final class SharedHandleFieldtype extends Fieldtype
{
    /** @var string */
    protected static $handle = 'fixture_shared';
}
