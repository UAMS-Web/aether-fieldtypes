<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;
use UAMSWeb\Aether\Fieldtypes\Traits\Input;

/**
 * A representative `Input` consumer: a fieldtype that takes its config fields from the `Input`
 * trait, as the site's `Text` fieldtype does.
 */
class InputFieldtypeHarness extends Fieldtype
{
    use Input;

    /** @var string */
    protected static $handle = 'input_harness';

    /**
     * The preset definitions the trait offers in its selector, none by default: the trait
     * checks for this hook by name, as a consumer defines it.
     *
     * @return array<string, mixed>
     */
    protected function getPresets(): array
    {
        return [];
    }
}
