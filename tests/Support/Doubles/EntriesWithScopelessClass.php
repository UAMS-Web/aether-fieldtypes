<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Entries;

/**
 * An Entries picker whose scope class has neither method the picker reads.
 */
final class EntriesWithScopelessClass extends Entries
{
    protected const string PEOPLE_EDITING_SCOPE = ScopelessEditingScope::class;
}
