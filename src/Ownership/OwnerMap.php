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
 * A registration the check cannot read is reported too, never skipped, because a clean
 * result would otherwise cover less than it reads as. `unreadable()` lists them and
 * `assertSingleOwners()` fails on them. Component names are read from each Control Panel
 * entry and from the files it reaches through relative imports. These are each reported: a
 * name that is not a string literal, an `import.meta.glob`, a dynamic import with a computed
 * path, a relative import the check cannot resolve inside the package, a manifest that is
 * missing or not valid JSON, and a listed class that cannot be autoloaded. Two gaps stay by
 * design: a declared entry file that is not on disk is skipped, because a package may ship
 * built assets without its sources, and an import through a bundler alias is not followed.
 *
 * A host application asserts it with `OwnerMap::fromInstalledAddons()->assertSingleOwners()`.
 */
final class OwnerMap
{
    /** Extensions of the files the scan reads as scripts, and resolves an import to as written. */
    private const array CODE_EXTENSIONS = ['js', 'mjs', 'cjs', 'jsx', 'ts', 'tsx', 'vue'];

    /** Extensions of the files an import names that hold no script, which the scan passes over. */
    private const array ASSET_EXTENSIONS = [
        'css', 'scss', 'sass', 'less', 'styl', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'ico', 'bmp',
        'json', 'woff', 'woff2', 'ttf', 'otf', 'eot', 'mp3', 'mp4', 'webm', 'ogg', 'wav', 'html', 'txt', 'md', 'wasm', 'map',
    ];

    /**
     * @param  list<Claim>  $claims  Every claim made by the packages under inspection.
     * @param  list<Unreadable>  $unreadable  Every registration the check could not read.
     */
    public function __construct(private readonly array $claims, private readonly array $unreadable = []) {}

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
        $unreadable = [];

        foreach ($directories as $package => $directory) {
            // Read the package's provider classes from its composer.json
            [$providers, $unreadableProviders] = self::providersOf($package, $directory);
            $unreadable = [...$unreadable, ...$unreadableProviders];

            foreach ($providers as $provider) {
                $found = self::fromProvider($package, $provider, $directory);
                $claims = [...$claims, ...$found->claims()];
                $unreadable = [...$unreadable, ...$found->unreadable()];
            }
        }

        return new self($claims, $unreadable);
    }

    /**
     * The claims one provider declares: the fieldtype classes it lists or autoloads, and the
     * component names its Control Panel scripts register. A registration that cannot be read is
     * dropped here, so a caller that needs to know uses `fromProvider()`.
     *
     * @param  string  $package  The Composer package that owns the provider.
     * @param  class-string<AddonServiceProvider>  $provider  The provider class.
     * @param  string  $directory  The package root, with a trailing separator.
     * @return list<Claim>
     */
    public static function claimsFromProvider(string $package, string $provider, string $directory): array
    {
        return self::fromProvider($package, $provider, $directory)->claims();
    }

    /**
     * The claims one provider declares, with every registration of it that could not be read.
     *
     * @param  string  $package  The Composer package that owns the provider.
     * @param  class-string<AddonServiceProvider>  $provider  The provider class.
     * @param  string  $directory  The package root, with a trailing separator.
     */
    public static function fromProvider(string $package, string $provider, string $directory): self
    {
        $reflection = new ReflectionClass($provider);
        $defaults = $reflection->getDefaultProperties();
        $claims = [];
        $unreadable = [];

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
            // Statamic fails on a listed class it cannot load, so the check reports it
            if (! class_exists($class)) {
                $unreadable[] = new Unreadable($package, $class, 'the fieldtype class cannot be autoloaded');

                continue;
            }

            // Skip anything that is not a concrete fieldtype, as the provider does
            if (is_subclass_of($class, Fieldtype::class) && ! new ReflectionClass($class)->isAbstract()) {
                $handle = $class::handle();
                $claims[] = new Claim($package, Claim::FIELDTYPE, is_string($handle) ? $handle : $class);
            }
        }

        // Collect the component names the provider's scripts register, following relative imports
        $names = [];
        $visited = [];

        foreach (self::entriesOf($defaults, $directory) as $entry) {
            foreach (self::namesInFile($package, $entry, $directory, $visited, $unreadable) as $name) {
                $names[$name] = $name;
            }
        }

        // The names are the array values, so a name of digits stays a string even though its key becomes an integer
        foreach ($names as $name) {
            $claims[] = new Claim($package, Claim::COMPONENT, $name);
        }

        return new self($claims, $unreadable);
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
     * The registrations the check could not read.
     *
     * @return list<Unreadable>
     */
    public function unreadable(): array
    {
        return $this->unreadable;
    }

    /**
     * Fail when any handle or component name has two owners, or when a registration could not be
     * read. A conflict is reported first, because it is a defect already proven, and the
     * unreadable list stays available from `unreadable()`.
     *
     * @throws OwnershipConflict When a name is claimed by more than one package.
     * @throws OwnershipUnreadable When a registration could not be read, so a pass would claim more than it saw.
     */
    public function assertSingleOwners(): void
    {
        $conflicts = $this->conflicts();

        if ($conflicts !== []) {
            throw new OwnershipConflict($conflicts);
        }

        if ($this->unreadable !== []) {
            throw new OwnershipUnreadable($this->unreadable);
        }
    }

    /**
     * The service provider classes a package's composer.json lists for Laravel discovery, and what
     * could not be read from it.
     *
     * @return array{0: list<class-string<AddonServiceProvider>>, 1: list<Unreadable>}
     */
    private static function providersOf(string $package, string $directory): array
    {
        $file = $directory.'composer.json';

        if (! is_file($file)) {
            return [[], [new Unreadable($package, $file, 'the manifest does not exist')]];
        }

        $contents = file_get_contents($file);

        // A manifest that exists fails to read only for a permission or locking reason, which a test cannot raise on every platform.
        // @pest-mutate-ignore: FalseToTrue,RemoveEarlyReturn
        if ($contents === false) {
            return [[], [new Unreadable($package, $file, 'the manifest could not be read')]];
        }

        $manifest = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [[], [new Unreadable($package, $file, 'the manifest is not valid JSON: '.json_last_error_msg())]];
        }

        if (! is_array($manifest)) {
            return [[], [new Unreadable($package, $file, 'the manifest is not a JSON object')]];
        }

        $extra = $manifest['extra'] ?? null;
        $laravel = is_array($extra) ? ($extra['laravel'] ?? null) : null;
        // Laravel casts `providers` to an array, so a single class name written as a string is a provider too
        $providers = is_array($laravel) ? (array) ($laravel['providers'] ?? []) : [];

        // Keep the listed classes that are add-on providers, and report one that cannot be read
        $found = [];
        $unreadable = [];

        foreach ($providers as $provider) {
            if (! is_string($provider)) {
                $unreadable[] = new Unreadable($package, $file, 'a listed provider is not a class name');
            } elseif (! class_exists($provider)) {
                $unreadable[] = new Unreadable($package, $provider, 'the provider class cannot be autoloaded');
            } elseif (is_subclass_of($provider, AddonServiceProvider::class)) {
                $found[] = $provider;
            }
        }

        return [$found, $unreadable];
    }

    /**
     * The Control Panel entry files a provider declares through `$vite` that exist on disk. A
     * declared entry that is missing is skipped, because a package may ship built assets without
     * its sources.
     *
     * @param  array<string, mixed>  $defaults  The provider's default property values.
     * @return list<string>
     */
    private static function entriesOf(array $defaults, string $directory): array
    {
        // Gather the declared paths, relative to the package root
        $paths = [];
        $vite = $defaults['vite'] ?? null;

        // A provider's `$vite` is typed to hold `input` whenever it is an array, so either test alone decides.
        // The test is its own statement so this annotation reaches only it, not the filter below.
        // @pest-mutate-ignore: BooleanAndToBooleanOr
        $declaresInput = is_array($vite) && is_array($vite['input'] ?? null);

        if ($declaresInput) {
            $paths = array_filter($vite['input'], fn (mixed $path): bool => is_string($path) && in_array(pathinfo($path, PATHINFO_EXTENSION), self::CODE_EXTENSIONS, true));
        }

        // Keep each file that exists
        $entries = [];

        foreach ($paths as $path) {
            if (is_file($directory.$path)) {
                $entries[] = $directory.$path;
            }
        }

        return $entries;
    }

    /**
     * The component names a script registers, and those of the files it reaches through relative
     * imports. What cannot be read is added to `$unreadable`.
     *
     * @param  array<string, true>  $visited  The files already read, by real path, so an import cycle ends.
     * @param  list<Unreadable>  $unreadable  Collects each registration or import that could not be read.
     * @return list<string>
     */
    private static function namesInFile(string $package, string $file, string $root, array &$visited, array &$unreadable): array
    {
        // Read each file once
        $real = realpath($file);
        $base = realpath($root);

        // A file is read only after it was found on disk, so realpath fails only for one removed in between.
        // @pest-mutate-ignore: BooleanOrToBooleanAnd,FalseToTrue,RemoveEarlyReturn
        if ($real === false || $base === false) {
            return [];
        }

        // Without this guard an import cycle recurses without bound, so its mutants time out rather than fail a test.
        // @pest-mutate-ignore: RemoveEarlyReturn
        if (isset($visited[$real])) {
            return [];
        }

        // Only the key is read, so the stored value makes no difference.
        // @pest-mutate-ignore: TrueToFalse
        $visited[$real] = true;
        $label = ltrim(str_replace([$base, '\\'], ['', '/'], $real), '/');
        $contents = file_get_contents($real);

        // A file that was just resolved fails to read only for a permission or locking reason, which a test cannot raise on every platform.
        // @pest-mutate-ignore: FalseToTrue,RemoveEarlyReturn
        if ($contents === false) {
            $unreadable[] = new Unreadable($package, $label, 'the file could not be read');

            return [];
        }

        $source = self::withoutComments($contents);
        $names = [];

        // Collect the names registered with a string literal, and report any other first argument
        preg_match_all('/Statamic\.(?:\$components\.register|component)\(\s*/', $source, $calls, PREG_OFFSET_CAPTURE);

        foreach ($calls[0] as [$call, $offset]) {
            $argument = substr($source, $offset + strlen($call));

            if (preg_match('/^(?:([\'"])([^\'"\\\\\r\n]+)\1|`([^`$\\\\\r\n]+)`)\s*[,)]/', $argument, $literal) === 1) {
                $names[] = $literal[3] ?? $literal[2];

                continue;
            }

            $unreadable[] = new Unreadable($package, $label.': '.self::excerpt($call.$argument), 'the component name is not a string literal');
        }

        // Report what hides registrations from this scan
        foreach (self::unfollowable($source) as $excerpt => $reason) {
            $unreadable[] = new Unreadable($package, $label.': '.$excerpt, $reason);
        }

        // Follow each relative import into the files it names
        foreach (self::relativeImports($source) as $specifier) {
            $target = self::resolveImport(dirname($real), $specifier);

            if ($target === null) {
                $unreadable[] = new Unreadable($package, $label.': '.$specifier, 'the imported file was not found');

                continue;
            }

            if (! str_starts_with($target, $base.DIRECTORY_SEPARATOR)) {
                $unreadable[] = new Unreadable($package, $label.': '.$specifier, 'the import resolves outside the package');

                continue;
            }

            $names = [...$names, ...self::namesInFile($package, $target, $root, $visited, $unreadable)];
        }

        return $names;
    }

    /**
     * The source with block comments and line comments removed, so a commented-out call is neither
     * claimed nor reported. A comment marker inside a string literal is kept with the string. A
     * regular expression literal that holds a quote is not understood and can pair that quote with
     * a later one on the same line.
     */
    private static function withoutComments(string $source): string
    {
        // A string literal is matched first at each position, so a `/*` or `//` inside one is not a comment
        $pattern = '~("(?:[^"\\\\\r\n]|\\\\.)*"|\'(?:[^\'\\\\\r\n]|\\\\.)*\'|`(?:[^`\\\\]|\\\\.)*`)|/\*.*?\*/|//[^\r\n]*~s';

        return preg_replace_callback($pattern, fn (array $match): string => isset($match[1]) ? $match[0] : ' ', $source) ?? $source;
    }

    /**
     * A short single-line excerpt of source, for a report.
     */
    private static function excerpt(string $source): string
    {
        // Every source passed here holds a call or an import, so the first line is never empty and the fallback only narrows `string|false`.
        // @pest-mutate-ignore: EmptyStringToNotEmpty
        $line = strtok($source, "\r\n") ?: '';

        return trim(substr($line, 0, 60));
    }

    /**
     * Constructs that register components where this scan cannot see them: `import.meta.glob` and a
     * dynamic `import()` whose path is not a literal.
     *
     * @return array<string, string> The reason for each, keyed by an excerpt of the source.
     */
    private static function unfollowable(string $source): array
    {
        $found = [];

        preg_match_all('/\bimport\.meta\.glob(?:Eager)?\([^\r\n]*/', $source, $globs);

        foreach ($globs[0] as $glob) {
            $found[self::excerpt($glob)] = 'import.meta.glob cannot be followed';
        }

        // The possessive `\s*+` keeps the lookahead from passing by giving back the space before a quote.
        preg_match_all('/\bimport\(\s*+(?![\'"])[^\r\n]*/', $source, $dynamic);

        foreach ($dynamic[0] as $import) {
            $found[self::excerpt($import)] = 'a dynamic import with a computed path cannot be followed';
        }

        return $found;
    }

    /**
     * The relative paths a script imports: static `import` and `export ... from`, and a dynamic
     * `import()` with a string literal.
     *
     * @return list<string>
     */
    private static function relativeImports(string $source): array
    {
        preg_match_all('/\b(?:import|export)\b(?:[^\'";()]*?\bfrom)?\s*[\'"]([^\'"]+)[\'"]/', $source, $static);
        preg_match_all('/\bimport\(\s*+([\'"])([^\'"]+)\1/', $source, $dynamic);

        // Keep the relative paths, except those that name a stylesheet, an image or another asset.
        // The result is iterated and never indexed, so the gaps the filters leave make no difference.
        // @pest-mutate-ignore: UnwrapArrayValues
        return array_values(array_unique(array_filter(
            [...$static[1], ...$dynamic[2]],
            fn (string $specifier): bool => preg_match('~^\.{1,2}/~', $specifier) === 1
                && ! in_array(strtolower(pathinfo($specifier, PATHINFO_EXTENSION)), self::ASSET_EXTENSIONS, true),
        )));
    }

    /**
     * The file a relative import names, trying the extensions and index file a bundler would.
     */
    private static function resolveImport(string $from, string $specifier): ?string
    {
        $path = $from.'/'.$specifier;
        // A name with a code extension is that file; any other, such as `./date.utils`, is tried with each extension
        $candidates = in_array(pathinfo($specifier, PATHINFO_EXTENSION), self::CODE_EXTENSIONS, true)
            ? [$path]
            : [$path.'.js', $path.'.mjs', $path.'.ts', $path.'/index.js'];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return realpath($candidate) ?: null;
            }
        }

        return null;
    }
}
