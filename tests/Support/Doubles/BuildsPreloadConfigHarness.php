<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;
use UAMSWeb\Aether\Fieldtypes\Traits\BuildsPreloadConfig;
use UAMSWeb\Aether\Fieldtypes\Traits\ConfigCache;

/**
 * A fieldtype using the BuildsPreloadConfig trait, with the hooks the trait reads
 * (config overrides, additional config fields and config field items) set
 * through public properties. It has no getPresets(), so it never reads $presets; see
 * {@see BuildsPreloadConfigPresetHarness} for one that does.
 */
class BuildsPreloadConfigHarness extends Fieldtype
{
    use BuildsPreloadConfig {
        normalizeConfigWithDefaults as public;
        processPreloadConfig as public;
    }
    use ConfigCache;

    /** @var array<string, mixed> */
    public array $overrides = [];

    /** @var array<string, mixed> */
    public array $additional = [];

    /** @var list<array<string, mixed>> */
    public array $fieldItems = [];

    /**
     * Read only by {@see BuildsPreloadConfigPresetHarness}; declared here so a
     * test can set it on either harness.
     *
     * @var array<array-key, mixed>
     */
    public array $presets = [];

    /**
     * Read only by {@see BuildsPreloadConfigPresetHarness}.
     *
     * @var array<string, mixed>
     */
    public array $preloadModifications = [];

    /** @return array<string, mixed> */
    protected function getConfigFieldOverrides(): array
    {
        return $this->overrides;
    }

    /** @return array<string, mixed> */
    protected function getAdditionalConfigFields(): array
    {
        return $this->additional;
    }

    /** @return list<array<string, mixed>> */
    protected function configFieldItems(): array
    {
        return $this->fieldItems;
    }
}
