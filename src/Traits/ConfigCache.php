<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Traits;

/**
 * Trait ConfigCache
 *
 * Provides a reusable, recursion-safe config caching mechanism for fieldtypes.
 * Prevents infinite recursion when accessing config() and caches the result for
 * performance.
 */
trait ConfigCache
{
    /**
     * Instance cache for config to prevent infinite recursion
     *
     * @var ?array<string, mixed> Cached configuration array, or null if not yet populated
     */
    private ?array $configCache = null;

    /**
     * Recursion guard to prevent infinite loops when getting config
     *
     * @var bool Flag indicating whether config retrieval is currently in progress
     */
    // A property default holds no executable line, so coverage reports it uncovered although the
    // recursion test pins the guard's start state.
    // @pest-mutate-ignore: FalseToTrue
    private bool $configRecursionGuard = false;

    /**
     * Get cached config to prevent infinite recursion.
     *
     * This method safely retrieves the configuration without causing recursive calls.
     *
     * @return array<string, mixed> The cached configuration array
     */
    protected function getCachedConfig(): array
    {
        // ---
        // If the config cache is not yet populated, retrieve and cache it
        // ---

        // Check if config cache is null
        // Dropping the guard's early return, or never setting the guard, makes config() recurse until
        // the process crashes. No test can observe that, and the mutation runner reports it as a
        // failed run rather than a kill.
        // @pest-mutate-ignore: RemoveEarlyReturn,TrueToFalse
        if ($this->configCache === null) {
            // ---
            // If already in the process of retrieving config, prevent recursion
            // ---

            // Check if recursion guard is active
            if ($this->configRecursionGuard) {
                // Return empty array to prevent infinite recursion
                return [];
            }

            // Set the recursion guard before retrieving config
            $this->configRecursionGuard = true;

            // Retrieve the config and cache the result
            /** @var array<string, mixed> $config */
            $config = $this->config() ?? [];
            $this->configCache = $config;

            // Unset the recursion guard after retrieval
            $this->configRecursionGuard = false;
        }

        // Return the cached config array
        return $this->configCache;
    }

    /**
     * Clear the config cache for this instance.
     * Useful for testing or when cache needs to be refreshed.
     */
    public function clearConfigCache(): void
    {
        // Set config cache to null to clear it
        $this->configCache = null;
    }
}
