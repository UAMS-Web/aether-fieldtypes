<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;

/**
 * Stands in for the site's `subsection` fieldtype, which `HasSubfields` names by handle in
 * the config it builds. Statamic core has no such handle, so a host that does not register one
 * fails to resolve the field.
 */
final class SubsectionStandIn extends Fieldtype
{
    /** @var string */
    protected static $handle = 'subsection';
}
