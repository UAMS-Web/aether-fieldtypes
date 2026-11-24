Written against v0.3.0.

The owner check finds fieldtype handles and Control Panel component names that more than one installed package registers. Statamic keeps one class per fieldtype handle and one component per Control Panel name, so a second registration silently replaces the first, and which one wins depends on boot order. The check reads each package's declared registrations before that replacement hides them. This page is a reference for developers of packages that register fieldtypes, and for host applications that assert the result. The classes live in `src/Ownership/` (namespace `UAMSWeb\Aether\Fieldtypes\Ownership`). Nothing in the add-on's own source calls the check, so a host or a package calls it.

- [Calling the check](#calling-the-check)
- [What it reads](#what-it-reads)
- [OwnerMap](#ownermap)
- [Return shapes](#return-shapes)
- [Exceptions and messages](#exceptions-and-messages)
- [Reading a result](#reading-a-result)
- [Gaps that remain by design](#gaps-that-remain-by-design)

## Calling the check

A host application asserts that every name has one owner with:

```php
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnerMap;

OwnerMap::fromInstalledAddons()->assertSingleOwners();
```

`assertSingleOwners()` returns nothing when the check is clean and throws otherwise. It is intended for a test or a boot-time guard in the host, not for request handling.

To inspect rather than assert, use the data methods: `conflicts()`, `unreadable()` and `claims()`. To check packages that are not booted as Statamic add-ons, build the map from package roots with `fromDirectories()`.

## What it reads

For each package, the check reads:

1. The package's `composer.json`, for the service providers listed under `extra.laravel.providers`. A single string is accepted as well as a list. Only classes that extend `Statamic\Providers\AddonServiceProvider` are kept.
2. For each such provider, the fieldtype classes it lists in its `$fieldtypes` property, plus every `*.php` file in a `Fieldtypes` folder beside the provider class (the same autoload convention the provider uses). Abstract classes and classes that do not extend `Statamic\Fields\Fieldtype` are skipped. A concrete fieldtype becomes a claim on its handle (`handle()`).
3. The Control Panel entry files the provider declares in its `$vite['input']` property, limited to script extensions (`js`, `mjs`, `cjs`, `jsx`, `ts`, `tsx`, `vue`) and to files that exist. In those files, and in the files they reach through relative imports, it looks for `Statamic.$components.register('<name>'` and `Statamic.component('<name>'` calls with a string literal name. Comments are removed first, so a commented-out call is not claimed.

## OwnerMap

`OwnerMap` is a `final` class. It holds a list of claims and a list of unreadable items, both fixed at construction.

| Method | Returns | Purpose |
| --- | --- | --- |
| `__construct(list<Claim> $claims, list<Unreadable> $unreadable = [])` | `OwnerMap` | Build a map directly from known claims. |
| `OwnerMap::fromInstalledAddons()` | `OwnerMap` | Read every add-on the host booted (`Statamic\Facades\Addon::all()`). |
| `OwnerMap::fromAddons(iterable $addons)` | `OwnerMap` | Read the given add-ons. An item that is not a Statamic add-on, or whose directory is not a string, is skipped. Each add-on is keyed by its Composer package name (`$addon->package()`). |
| `OwnerMap::fromDirectories(array $directories)` | `OwnerMap` | Read packages at the given roots. The array maps a Composer package name to its root path, and each path must end with a directory separator. |
| `OwnerMap::fromProvider(string $package, string $provider, string $directory)` | `OwnerMap` | Read one provider class, keeping both claims and unreadable items. |
| `OwnerMap::claimsFromProvider(string $package, string $provider, string $directory)` | `list<Claim>` | Read one provider's claims only. Unreadable items are dropped, so use `fromProvider()` when they matter. |
| `conflicts()` | `list<array{kind, name, owners}>` | Every name with more than one distinct owner. |
| `claims()` | `list<Claim>` | Every claim under inspection. |
| `unreadable()` | `list<Unreadable>` | Every registration the check could not read. |
| `assertSingleOwners()` | `void` | Throw when there is a conflict or an unreadable item. |

`$directory` in the provider methods is the package root, with a trailing separator.

## Return shapes

`Claim` is a `final readonly` value with three public properties:

| Property | Type | Meaning |
| --- | --- | --- |
| `owner` | `string` | The Composer package that registers the name. |
| `kind` | `string` | `Claim::FIELDTYPE` (`'fieldtype handle'`) or `Claim::COMPONENT` (`'Control Panel component'`). |
| `name` | `string` | The handle or component name. |

A fieldtype class whose `handle()` is not a string is recorded under its class name instead.

`conflicts()` returns one entry per kind and name that has more than one distinct owner:

```php
[
    ['kind' => 'fieldtype handle', 'name' => 'text', 'owners' => ['vendor/package-a', 'vendor/package-b']],
]
```

The same package claiming a name twice is not a conflict. Owners are distinct package names.

`Unreadable` is a `final readonly` value with three public string properties:

| Property | Meaning |
| --- | --- |
| `owner` | The Composer package whose registration could not be read. |
| `subject` | What could not be read: a file path, a class name, or a short source excerpt (up to 60 characters of one line). |
| `reason` | Why it could not be read. |

## Exceptions and messages

`assertSingleOwners()` checks conflicts first, then unreadable items, and throws one exception. A conflict is reported before an unreadable item because it is a defect already proven. The unreadable list is still available from `unreadable()` after an `OwnershipConflict`.

| Exception | Extends | Public property | Thrown when |
| --- | --- | --- | --- |
| `OwnershipConflict` | `RuntimeException` | `$conflicts`, the same shape `conflicts()` returns | A name has more than one owner. |
| `OwnershipUnreadable` | `RuntimeException` | `$unreadable`, a `list<Unreadable>` | No conflict, but a registration could not be read. |

Both classes are `final`. Their messages come from `resources/lang/en/messages.php`, read through the `aether-fieldtypes` namespace:

| Key | English text |
| --- | --- |
| `aether-fieldtypes::messages.ownership.conflict` | `More than one package registers the same name, and Statamic keeps only the last registration: :conflicts.` |
| `aether-fieldtypes::messages.ownership.unreadable` | `The owner check could not read every registration, so a clean result would not cover them: :unreadable.` |

The placeholder lines are built in PHP and joined with `; `:

- `:conflicts` holds one `<kind> "<name>": <owner>, <owner>` entry per conflict, for example `fieldtype handle "text": vendor/package-a, vendor/package-b`.
- `:unreadable` holds one `<owner>: <subject> (<reason>)` entry per unreadable item.

A host can reword either message by publishing the file to `lang/vendor/aether-fieldtypes/en/messages.php`, following the same Laravel convention the add-on's `validation.php` documents for itself.

## Reading a result

A conflict means two or more packages claim one name. Look at `owners` to see which packages. Remove the duplicate registration, or rename one handle or component, since Statamic would otherwise pick a winner by boot order.

An unreadable result means the check could not see something, so a clean outcome would cover less than it appears to. Each `reason` points at a fix:

| Reason | What it means |
| --- | --- |
| `the manifest does not exist` | The package root has no `composer.json`. |
| `the manifest could not be read` | The file exists but could not be read. |
| `the manifest is not valid JSON: <detail>` | `composer.json` failed to parse. |
| `the manifest is not a JSON object` | `composer.json` parsed to something other than an object. |
| `a listed provider is not a class name` | An `extra.laravel.providers` entry is not a string. |
| `the provider class cannot be autoloaded` | A listed provider class cannot be loaded. |
| `the fieldtype class cannot be autoloaded` | A fieldtype class the provider lists or autoloads cannot be loaded. |
| `the component name is not a string literal` | A `Statamic.component(` or `Statamic.$components.register(` call has a first argument that is not a plain quoted string or a template literal without interpolation. |
| `import.meta.glob cannot be followed` | An entry file registers components through `import.meta.glob` or `import.meta.globEager`. |
| `a dynamic import with a computed path cannot be followed` | An `import()` call has a path that is not a string literal. |
| `the imported file was not found` | A relative import did not resolve to a file. |
| `the import resolves outside the package` | A relative import resolves to a file outside the package root. |
| `the file could not be read` | A script file could not be read. |

An unreadable item is a registration the check never compared, so a clean result that ignores it covers less than it reads as. Writing the registration in a form the check can read (a literal name, a static import) removes the item.

## Gaps that remain by design

The class documentation names two gaps that stay open on purpose:

- A declared entry file that is not on disk is skipped, because a package may ship built assets without its sources.
- An import through a bundler alias is not followed.

Imports that name a stylesheet, image, font, JSON file or other asset are passed over without being reported. The comment stripper does not understand a regular expression literal that holds a quote, and can pair that quote with a later one on the same line.
