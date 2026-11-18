<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;
use UAMSWeb\Aether\Fieldtypes\Traits\ConfigCache;
use UAMSWeb\Aether\Fieldtypes\Traits\DefinesInputConfigFields;
use UAMSWeb\Aether\Fieldtypes\Traits\ProvidesAutocompleteOptions;

/**
 * A fieldtype that composes DefinesInputConfigFields on its own (with the config cache and
 * autocomplete options it reads) and takes every extension hook from its public properties. It exposes the trait's
 * protected builders so a test can reach each one.
 */
final class DefinesInputConfigFieldsHarness extends Fieldtype
{
    use ConfigCache;
    use DefinesInputConfigFields {
        htmlAttributeConfigFields as public;
        statamicConfigFields as public;
        inputMaskConfigFields as public;
        applyConfigFieldFilters as public;
        groupConfigFieldsIntoSections as public;
        injectAdditionalConfigFields as public;
        applyPresetToConfigFields as public;
    }
    use ProvidesAutocompleteOptions;

    /** @var string */
    protected static $handle = 'defines_input_config_fields_harness';

    /** @var array<string, array<string, mixed>> */
    public array $overrides = [];

    /** @var list<string> */
    public array $unset = [];

    /** @var list<string> */
    public array $hidden = [];

    /** @var array<string, mixed> */
    public array $additional = [];

    /** @var array<string, mixed> */
    public array $presets = [];

    public string $inputType = 'text';

    /** @return array<string, array<string, mixed>> */
    protected function getConfigFieldOverrides(): array
    {
        return $this->overrides;
    }

    /** @return list<string> */
    protected function getUnsetConfigFields(): array
    {
        return $this->unset;
    }

    /** @return list<string> */
    protected function getHiddenConfigFields(): array
    {
        return $this->hidden;
    }

    /** @return array<string, mixed> */
    protected function getAdditionalConfigFields(): array
    {
        return $this->additional;
    }

    /** @return array<string, mixed> */
    private function getPresets(): array
    {
        return $this->presets;
    }

    protected function getInputType(): string
    {
        return $this->inputType;
    }

    /**
     * Use this config as the cached config, as the fieldtype would read it from its field.
     *
     * @param  array<string, mixed>  $config
     */
    public function useConfig(array $config): void
    {
        $this->configCache = $config;
    }
}
