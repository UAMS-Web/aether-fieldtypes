<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Ownership;

use ReflectionClass;
use Statamic\Addons\Addon as StatamicAddon;
use Statamic\Facades\Addon;
use Statamic\Fields\Fieldtype;
use Statamic\Providers\AddonServiceProvider;

/**
 * Who registers each fieldtype handle and Control Panel component name.
 *
 * The add-on always replaces the core handle, and other packages may register
 * handles of their own, so a host needs to know that no handle or component name
 * has two owners. Statamic cannot tell it: its registry keeps one class per
 * handle, so a duplicate silently wins or loses by boot order. This reads the
 * declared registrations of each package instead, and reports a name claimed by
 * more than one.
 *
 * A host application asserts it with `OwnerMap::fromInstalledAddons()->assertSingleOwners()`.
 */
final class OwnerMap
{
    /**
     * @param  list<Claim>  $claims  Every claim made by the packages under inspection.
     */
    public function __construct(private readonly array $claims) {}

    /**
     * The claims of every add-on the host booted.
     */
    public static function fromInstalledAddons(): self
    {
        return self::fromAddons(Addon::all());
    }

    /**
     * The claims of the given add-ons.
     *
     * @param  iterable<mixed>  $addons  The add-ons to read; anything that is not an add-on, or has no provider, is skipped.
     */
    public static function fromAddons(iterable $addons): self
    {
        // Resolve each add-on to its package root
        $directories = [];

        foreach ($addons as $addon) {
            if ($addon instanceof StatamicAddon && is_string($addon->directory())) {
                $directories[$addon->package()] = $addon->directory();
            }
        }

        return self::fromDirectories($directories);
    }

    /**
     * The claims of the packages at the given roots.
     *
     * @param  array<string, string>  $directories  Each package root, with a trailing separator, keyed by Composer package name.
     */
    public static function fromDirectories(array $directories): self
    {
        $claims = [];

        foreach ($directories as $package => $directory) {
            // Read the package's provider classes from its composer.json
            foreach (self::providersOf($directory) as $provider) {
                $claims = [...$claims, ...self::claimsFromProvider($package, $provider, $directory)];
            }
        }

        return new self($claims);
    }

    /**
     * The claims one provider declares: the fieldtype classes it lists or autoloads, and the
     * component names its Control Panel scripts register.
     *
     * @param  string  $package  The Composer package that owns the provider.
     * @param  class-string<AddonServiceProvider>  $provider  The provider class.
     * @param  string  $directory  The package root, with a trailing separator.
     * @return list<Claim>
     */
    public static function claimsFromProvider(string $package, string $provider, string $directory): array
    {
        $reflection = new ReflectionClass($provider);
        $defaults = $reflection->getDefaultProperties();
        $claims = [];

        // Collect the fieldtype classes: listed on the provider, or autoloaded from its Fieldtypes folder
        $listed = $defaults['fieldtypes'] ?? [];
        // The provider types `$fieldtypes` as a list of class names, so the filter only narrows the type for
        // static analysis, and no input reaches it with a non-string entry.
        // @pest-mutate-ignore: UnwrapArrayFilter,UnwrapArrayValues
        $classes = is_array($listed) ? array_values(array_filter($listed, is_string(...))) : [];
        // A class loaded from a file always has a file name, so the cast only narrows `string|false`.
        // @pest-mutate-ignore: RemoveStringCast
        $folder = dirname((string) $reflection->getFileName()).'/Fieldtypes';

        foreach (is_dir($folder) ? (glob($folder.'/*.php') ?: []) : [] as $file) {
            $classes[] = $reflection->getNamespaceName().'\\Fieldtypes\\'.basename($file, '.php');
        }

        foreach (array_unique($classes) as $class) {
            // Skip anything that is not a concrete fieldtype, as the provider does
            if (is_subclass_of($class, Fieldtype::class) && ! new ReflectionClass($class)->isAbstract()) {
                $handle = $class::handle();
                $claims[] = new Claim($package, Claim::FIELDTYPE, is_string($handle) ? $handle : $class);
            }
        }

        // Collect the component names the provider's scripts register
        foreach (self::scriptsOf($defaults, $directory) as $script) {
            foreach (self::componentNamesIn($script) as $name) {
                $claims[] = new Claim($package, Claim::COMPONENT, $name);
            }
        }

        return $claims;
    }

    /**
     * Every name with more than one distinct owner.
     *
     * @return list<array{kind: string, name: string, owners: list<string>}>
     */
    public function conflicts(): array
    {
        // Group the owners of each name
        $owners = [];

        foreach ($this->claims as $claim) {
            $owners[$claim->kind][$claim->name][$claim->owner] = $claim->owner;
        }

        // Keep the names with more than one owner
        $conflicts = [];

        foreach ($owners as $kind => $names) {
            foreach ($names as $name => $byOwner) {
                if (count($byOwner) > 1) {
                    $conflicts[] = ['kind' => $kind, 'name' => (string) $name, 'owners' => array_map(strval(...), array_keys($byOwner))];
                }
            }
        }

        return $conflicts;
    }

    /**
     * The claims under inspection.
     *
     * @return list<Claim>
     */
    public function claims(): array
    {
        return $this->claims;
    }

    /**
     * Fail when any handle or component name has two owners.
     *
     * @throws OwnershipConflict When a name is claimed by more than one package.
     */
    public function assertSingleOwners(): void
    {
        $conflicts = $this->conflicts();

        if ($conflicts !== []) {
            throw new OwnershipConflict($conflicts);
        }
    }

    /**
     * The service provider classes a package's composer.json lists for Laravel discovery.
     *
     * @return list<class-string<AddonServiceProvider>>
     */
    private static function providersOf(string $directory): array
    {
        $file = $directory.'composer.json';
        // The file was just found, so the read returns text, and the cast only narrows `string|false`.
        // @pest-mutate-ignore: RemoveStringCast
        $manifest = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        $extra = is_array($manifest) ? ($manifest['extra'] ?? null) : null;
        $laravel = is_array($extra) ? ($extra['laravel'] ?? null) : null;
        $providers = is_array($laravel) ? ($laravel['providers'] ?? null) : null;

        // The result is iterated and never indexed, so the keys the filter leaves make no difference.
        // @pest-mutate-ignore: UnwrapArrayValues
        return array_values(array_filter(
            is_array($providers) ? $providers : [],
            fn (mixed $provider): bool => is_string($provider) && is_subclass_of($provider, AddonServiceProvider::class),
        ));
    }

    /**
     * The source of each Control Panel script a provider declares through `$vite` or `$scripts`.
     *
     * @param  array<string, mixed>  $defaults  The provider's default property values.
     * @return list<string>
     */
    private static function scriptsOf(array $defaults, string $directory): array
    {
        // Gather the declared paths, relative to the package root
        $paths = [];
        $vite = $defaults['vite'] ?? null;

        // A provider's `$vite` is typed to hold `input` whenever it is an array, so either test alone decides.
        // @pest-mutate-ignore: BooleanAndToBooleanOr
        if (is_array($vite) && is_array($vite['input'] ?? null)) {
            $paths = array_filter($vite['input'], fn (mixed $path): bool => is_string($path) && str_ends_with($path, '.js'));
        }

        // Read each file that exists
        $sources = [];

        foreach ($paths as $path) {
            $file = $directory.$path;

            if (is_file($file)) {
                // The file was just found, so the read returns text, and the cast only narrows `string|false`.
                // @pest-mutate-ignore: RemoveStringCast
                $sources[] = (string) file_get_contents($file);
            }
        }

        return $sources;
    }

    /**
     * The component names a Control Panel script registers.
     *
     * @return list<string>
     */
    private static function componentNamesIn(string $source): array
    {
        // Match `Statamic.$components.register('name', ...)` and `Statamic.component('name', ...)`
        preg_match_all('/Statamic\.(?:\$components\.register|component)\(\s*[\'"]([^\'"]+)[\'"]/', $source, $matches);

        // The result is iterated and never indexed, so the gaps `array_unique` leaves make no difference.
        // @pest-mutate-ignore: UnwrapArrayValues
        return array_values(array_unique($matches[1]));
    }
}
