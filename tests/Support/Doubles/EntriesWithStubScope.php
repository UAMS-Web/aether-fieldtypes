<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Entries;

/**
 * An Entries picker that reads the stub scope where the real one reads the people add-on's.
 */
final class EntriesWithStubScope extends Entries
{
    protected const string PEOPLE_EDITING_SCOPE = StubPeopleEditingScope::class;
}
