<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

/**
 * {@see BuildsPreloadConfigHarness} with a getPresets() returning $presets, and a
 * modifyPreloadConfig() that merges $preloadModifications into the config.
 */
final class BuildsPreloadConfigPresetHarness extends BuildsPreloadConfigHarness
{
    /** @return array<array-key, mixed> */
    private function getPresets(): array
    {
        return $this->presets;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function modifyPreloadConfig(array $config): array
    {
        return [...$config, ...$this->preloadModifications];
    }
}
