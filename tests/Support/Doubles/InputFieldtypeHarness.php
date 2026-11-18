<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;
use UAMSWeb\Aether\Fieldtypes\Traits\Input;

/**
 * A representative `Input` consumer: a fieldtype that takes its config fields from the `Input`
 * trait, as the site's `Text` fieldtype does.
 */
final class InputFieldtypeHarness extends Fieldtype
{
    use Input;

    /** @var string */
    protected static $handle = 'input_harness';
}
