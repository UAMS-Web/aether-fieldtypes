<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;

/**
 * Stands in for the `date_field_select` fieldtype, which moves into the add-on with its own task.
 *
 * `Date` names it in its configuration fields, and Statamic resolves a field's type when it builds them, so
 * a host without it registered cannot build `Date`'s settings.
 */
final class StubDateFieldSelect extends Fieldtype
{
    /**
     * The handle `Date`'s configuration fields name.
     *
     * @var string
     */
    protected static $handle = 'date_field_select';
}
