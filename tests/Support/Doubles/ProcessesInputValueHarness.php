<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;
use UAMSWeb\Aether\Fieldtypes\Traits\InputMaskValueStorage;
use UAMSWeb\Aether\Fieldtypes\Traits\ProcessesInputValue;

/**
 * A fieldtype using the ProcessesInputValue trait with the mask storage it relies
 * on. Its normalizeConfigWithDefaults() adds `defaulted`, and its
 * normalizeValue() tags the value with that `defaulted` flag, so a test can tell
 * which path ran and whether the config was normalized first. passThrough() reaches the trait's own
 * normalizeValue().
 */
class ProcessesInputValueHarness extends Fieldtype
{
    use InputMaskValueStorage;
    use ProcessesInputValue {
        normalizeValue as traitNormalizeValue;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function normalizeConfigWithDefaults(array $config): array
    {
        return [...$config, 'defaulted' => true];
    }

    /** @param  array<string, mixed>  $config */
    public function normalizeValue(mixed $value, array $config = []): mixed
    {
        return ['normalized' => $value, 'defaulted' => $config['defaulted'] ?? false];
    }

    /** @param  array<string, mixed>  $config */
    public function passThrough(mixed $value, array $config = []): mixed
    {
        return $this->traitNormalizeValue($value, $config);
    }
}
