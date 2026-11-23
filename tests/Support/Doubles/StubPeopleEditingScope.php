<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

/**
 * Stands in for the people add-on's editing scope, which this add-on cannot install (that add-on requires this one).
 *
 * Offers the two methods the Entries picker reads, with the answers a test sets.
 */
final class StubPeopleEditingScope
{
    /**
     * Whether the scope applies to the user.
     */
    public static bool $applies = true;

    /**
     * The entry ids outside the user's scope.
     *
     * @var list<string>
     */
    public static array $outOfScope = [];

    /**
     * How many times the picker asked whether the scope applies.
     */
    public static int $appliesCalls = 0;

    /**
     * Put the stub back to its defaults.
     */
    public static function reset(): void
    {
        self::$applies = true;
        self::$outOfScope = [];
        self::$appliesCalls = 0;
    }

    /**
     * Whether the scope applies to the user.
     */
    public function appliesTo(): bool
    {
        self::$appliesCalls++;

        return self::$applies;
    }

    /**
     * The entry ids outside the user's scope.
     *
     * @return list<string>
     */
    public function outOfScopeEntryIds(): array
    {
        return self::$outOfScope;
    }
}
