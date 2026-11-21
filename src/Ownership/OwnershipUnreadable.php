<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Ownership;

use RuntimeException;

/**
 * The owner check could not read some registrations, so passing it would claim more than it saw.
 */
final class OwnershipUnreadable extends RuntimeException
{
    /**
     * @param  list<Unreadable>  $unreadable  Every registration the check could not read.
     */
    public function __construct(public readonly array $unreadable)
    {
        // Name every unreadable subject with its owner and reason in the message
        $lines = array_map(
            fn (Unreadable $item): string => sprintf('%s: %s (%s)', $item->owner, $item->subject, $item->reason),
            $unreadable,
        );

        parent::__construct(__('aether-fieldtypes::messages.ownership.unreadable', ['unreadable' => implode('; ', $lines)]));
    }
}
