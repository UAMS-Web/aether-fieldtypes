<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Entries;

/**
 * An Entries picker in a host whose people editing scope class does not exist.
 */
final class EntriesWithMissingScope extends Entries
{
    protected const string PEOPLE_EDITING_SCOPE = 'No'.'\\Such\\EditingScope';
}
