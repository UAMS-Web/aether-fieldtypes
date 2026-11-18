<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageB;

use Statamic\Fields\Fieldtype;

/**
 * Fixture fieldtype that takes the same handle as the first package's fieldtype.
 */
final class SameHandleFieldtype extends Fieldtype
{
    /** @var string */
    protected static $handle = 'fixture_shared';
}
