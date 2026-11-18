<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Ownership;

/**
 * One package's claim on a name the host resolves to a single owner.
 *
 * Statamic keeps one class per fieldtype handle and one component per Control
 * Panel name, so a second registration silently replaces the first. A claim is
 * the declared registration, read before that replacement hides it.
 */
final readonly class Claim
{
    /** A fieldtype handle, such as `text`. */
    public const string FIELDTYPE = 'fieldtype handle';

    /** A Control Panel component name, such as `text-fieldtype`. */
    public const string COMPONENT = 'Control Panel component';

    /**
     * @param  string  $owner  The Composer package that registers the name.
     * @param  string  $kind  One of the kind constants on this class.
     * @param  string  $name  The handle or component name.
     */
    public function __construct(
        public string $owner,
        public string $kind,
        public string $name,
    ) {}
}
