<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Ownership;

use RuntimeException;

/**
 * Two or more packages register one fieldtype handle or component name.
 */
final class OwnershipConflict extends RuntimeException
{
    /**
     * @param  list<array{kind: string, name: string, owners: list<string>}>  $conflicts  Every name with more than one owner.
     */
    public function __construct(public readonly array $conflicts)
    {
        // Name every conflicting name and its owners in the message
        $lines = array_map(
            fn (array $conflict): string => sprintf('%s "%s": %s', $conflict['kind'], $conflict['name'], implode(', ', $conflict['owners'])),
            $conflicts,
        );

        parent::__construct(__('aether-fieldtypes::messages.ownership.conflict', ['conflicts' => implode('; ', $lines)]));
    }
}
