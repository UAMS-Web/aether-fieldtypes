<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageB;

use Statamic\Fields\Fieldtype;

/**
 * Fixture fieldtype with a handle no other fixture package takes.
 */
final class OwnHandleFieldtype extends Fieldtype
{
    /** @var string */
    protected static $handle = 'fixture_own';
}
