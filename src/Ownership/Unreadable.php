<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Ownership;

/**
 * Something the owner check could not read, so a clean result does not cover it.
 *
 * A listed provider that cannot be autoloaded, a manifest that is missing or not valid JSON, a
 * component name that is not a string literal, and an import the check cannot follow each leave
 * the owner map without claims it may need. Each is recorded here instead of being skipped.
 */
final readonly class Unreadable
{
    /**
     * @param  string  $owner  The Composer package whose registration could not be read.
     * @param  string  $subject  What could not be read: a file, a class name or a source excerpt.
     * @param  string  $reason  Why it could not be read.
     */
    public function __construct(
        public string $owner,
        public string $subject,
        public string $reason,
    ) {}
}
