<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;
use UAMSWeb\Aether\Fieldtypes\Traits\RandomizesOptions;

/**
 * A fieldtype using the RandomizesOptions trait whose preset options are the
 * letters a to j in order.
 */
class RandomizesOptionsHarness extends Fieldtype
{
    use RandomizesOptions;

    /** @return array<array-key, mixed> */
    protected function getOptionsWithPreset(): array
    {
        return range('a', 'j');
    }

    /** @return array<array-key, mixed> */
    public function options(): array
    {
        return $this->getOptions();
    }
}
