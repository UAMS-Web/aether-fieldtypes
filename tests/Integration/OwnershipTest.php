<?php

declare(strict_types=1);

use Statamic\Facades\Addon;
use UAMSWeb\Aether\Fieldtypes\Ownership\Claim;
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnerMap;
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnershipConflict;
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnershipUnreadable;
use UAMSWeb\Aether\Fieldtypes\Ownership\Unreadable;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageA\PackageAProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageB\PackageBProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageC\PackageCProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageE\PackageEProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageG\PackageGProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageH\PackageHProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageI\PackageIProvider;

covers(Claim::class, OwnerMap::class, OwnershipConflict::class, OwnershipUnreadable::class, Unreadable::class);

/**
 * Owner test: each fieldtype handle and each Control Panel component name has exactly one
 * owner among the add-ons the host booted, and the check fails when two packages register one.
 *
 * @command  vendor/bin/pest tests/Integration/OwnershipTest.php
 */

/**
 * The claims of the two fixture packages, which register one handle and one component name
 * between them.
 *
 * @return list<Claim>
 */
function ownershipFixtureClaims(): array
{
    $scripts = __DIR__.'/../Support/Fixtures/';

    return [
        ...OwnerMap::claimsFromProvider('fixture/a', PackageAProvider::class, $scripts),
        ...OwnerMap::claimsFromProvider('fixture/b', PackageBProvider::class, $scripts),
    ];
}

it('passes for the add-ons the test application booted', function (): void {
    // Read every booted add-on's claims
    $map = OwnerMap::fromInstalledAddons();

    // Expect no name with two owners, nothing unreadable, and no exception from the assertion
    expect($map->conflicts())->toBe([])
        ->and($map->unreadable())->toBe([]);
    $map->assertSingleOwners();
});

it("finds this add-on's own claims, so an empty result is not blindness", function (): void {
    // Read this add-on's claims through the add-ons the test application booted
    $own = array_filter(
        OwnerMap::fromInstalledAddons()->claims(),
        fn (Claim $claim): bool => $claim->owner === 'uams-web/aether-fieldtypes',
    );

    // Expect the subsection fieldtype and the Control Panel component this add-on registers
    expect(ownershipDescribe(array_values($own)))->toContain(
        'uams-web/aether-fieldtypes|fieldtype handle|subsection',
        'uams-web/aether-fieldtypes|Control Panel component|subsection-fieldtype',
    );
});

it('finds the fixture packages and lists this add-on among those Statamic booted', function (): void {
    // Read the fixture packages' claims
    $owners = array_map(fn (Claim $claim): string => $claim->owner, ownershipFixtureClaims());

    // Expect both fixture packages seen, and the booted add-on listed by Statamic
    expect($owners)->toContain('fixture/a', 'fixture/b')
        ->and(Addon::all()->map->package()->all())->toContain('uams-web/aether-fieldtypes');
});

it('reads handles and component names from each provider', function (): void {
    // Read the fixture claims
    $claims = array_map(fn (Claim $claim): string => $claim->owner.'|'.$claim->kind.'|'.$claim->name, ownershipFixtureClaims());

    // Expect each fixture's handles, and every component name its script registers, in either call form
    expect($claims)->toContain('fixture/a|fieldtype handle|fixture_shared')
        ->and($claims)->toContain('fixture/b|fieldtype handle|fixture_shared')
        ->and($claims)->toContain('fixture/b|fieldtype handle|fixture_own')
        ->and($claims)->toContain('fixture/a|Control Panel component|shared-fieldtype')
        ->and($claims)->toContain('fixture/a|Control Panel component|a-only-fieldtype')
        ->and($claims)->toContain('fixture/b|Control Panel component|shared-fieldtype')
        ->and($claims)->toContain('fixture/b|Control Panel component|b-only-fieldtype');
});

it('fails when two packages register one handle or one component name', function (): void {
    // Combine the two fixture packages
    $map = new OwnerMap(ownershipFixtureClaims());

    // Expect exactly the shared handle and the shared component name reported
    expect($map->conflicts())->toBe([
        ['kind' => Claim::FIELDTYPE, 'name' => 'fixture_shared', 'owners' => ['fixture/a', 'fixture/b']],
        ['kind' => Claim::COMPONENT, 'name' => 'shared-fieldtype', 'owners' => ['fixture/a', 'fixture/b']],
    ]);

    // Expect the assertion to throw, naming both names and both owners
    expect(fn () => $map->assertSingleOwners())->toThrow(
        OwnershipConflict::class,
        'fieldtype handle "fixture_shared": fixture/a, fixture/b',
    );
});

/**
 * Claims as `owner|kind|name` strings, for readable comparison.
 *
 * @param  list<Claim>  $claims  The claims to describe.
 * @return list<string>
 */
function ownershipDescribe(array $claims): array
{
    return array_map(fn (Claim $claim): string => $claim->owner.'|'.$claim->kind.'|'.$claim->name, $claims);
}

it("reads the provider named in a package's composer.json, autoloaded fieldtypes and scripts included", function (): void {
    // Read a fixture package whose manifest lists a real provider, a class that is not one, a missing class and a number
    $map = OwnerMap::fromDirectories(['fixture/c' => __DIR__.'/../Support/Fixtures/PackageC/']);

    // Expect the autoloaded concrete fieldtype and each script component once, and nothing from the abstract class, the stylesheet or the missing script
    expect(ownershipDescribe($map->claims()))->toBe([
        'fixture/c|fieldtype handle|listed',
        'fixture/c|fieldtype handle|autoloaded',
        'fixture/c|Control Panel component|c-only-fieldtype',
    ]);
});

it('reads no claims from a manifest that names no provider', function (string $manifest): void {
    // Read a fixture package whose composer.json cannot hold a provider
    $map = OwnerMap::fromDirectories(['fixture/'.$manifest => __DIR__.'/../Support/Fixtures/Manifests/'.$manifest.'/']);

    // Expect no claims and no error
    expect($map->claims())->toBe([]);
})->with(['scalar', 'extra-scalar', 'laravel-scalar', 'providers-scalar', 'no-extra']);

it('reads no claims from a package directory without a composer.json', function (): void {
    // Read a fixture directory that has no manifest
    $map = OwnerMap::fromDirectories(['fixture/none' => __DIR__.'/../Support/Fixtures/scripts/']);

    // Expect no claims
    expect($map->claims())->toBe([]);
});

it('resolves add-ons to their roots, skipping values that are not add-ons and add-ons without a provider', function (): void {
    // Build a real add-on for the fixture package, one with no provider, and a value that is no add-on
    $withProvider = Statamic\Addons\Addon::makeFromPackage(['id' => 'fixture/c', 'provider' => PackageCProvider::class, 'autoload' => '']);
    $withoutProvider = Statamic\Addons\Addon::make('fixture/no-provider');

    // Read them
    $map = OwnerMap::fromAddons(['not an addon', $withoutProvider, $withProvider]);

    // Expect only the add-on with a provider, read from its own directory
    expect(ownershipDescribe($map->claims()))->toBe([
        'fixture/c|fieldtype handle|listed',
        'fixture/c|fieldtype handle|autoloaded',
        'fixture/c|Control Panel component|c-only-fieldtype',
    ]);
});

it('reads the add-ons the host booted, adding any the host lists', function (): void {
    // List a fixture add-on beside this one in the host's add-on repository
    $addons = Addon::all();
    $addons->put('fixture/c', Statamic\Addons\Addon::makeFromPackage(['id' => 'fixture/c', 'provider' => PackageCProvider::class, 'autoload' => '']));

    try {
        // Read the booted add-ons
        $map = OwnerMap::fromInstalledAddons();
    } finally {
        $addons->forget('fixture/c');
    }

    // Expect the fixture's claims among those read
    expect(ownershipDescribe($map->claims()))->toContain('fixture/c|fieldtype handle|autoloaded');
});

it('throws a conflict that carries the conflicts and names every owner', function (): void {
    // Combine the two fixture packages and catch the exception
    $map = new OwnerMap(ownershipFixtureClaims());

    $thrown = null;

    try {
        $map->assertSingleOwners();
    } catch (OwnershipConflict $ownershipConflict) {
        $thrown = $ownershipConflict;
    }

    // Expect the exception to carry the same conflicts the map reports, and the message to name each owner and name
    expect($thrown)->toBeInstanceOf(OwnershipConflict::class)
        ->and($thrown?->conflicts)->toBe($map->conflicts())
        ->and($thrown?->getMessage())->toContain('fieldtype handle "fixture_shared": fixture/a, fixture/b')
        ->and($thrown?->getMessage())->toContain('Control Panel component "shared-fieldtype": fixture/a, fixture/b');
});

it('does not throw where every name has one owner', function (): void {
    // Assert on a map with one owner per name
    $map = new OwnerMap([new Claim('fixture/a', Claim::FIELDTYPE, 'only')]);

    // Expect no exception
    $map->assertSingleOwners();

    expect($map->conflicts())->toBe([]);
});

it('reads several packages from their manifests and finds the name two of them register', function (): void {
    // Read two fixture packages whose providers take one handle and one component name between them
    $fixtures = __DIR__.'/../Support/Fixtures/';
    $map = OwnerMap::fromDirectories(['fixture/c' => $fixtures.'PackageC/', 'fixture/d' => $fixtures.'PackageD/']);

    // Expect the claims of both packages, in order, and exactly the shared names as conflicts
    expect(ownershipDescribe($map->claims()))->toBe([
        'fixture/c|fieldtype handle|listed',
        'fixture/c|fieldtype handle|autoloaded',
        'fixture/c|Control Panel component|c-only-fieldtype',
        'fixture/d|fieldtype handle|autoloaded',
        'fixture/d|Control Panel component|c-only-fieldtype',
    ])->and($map->conflicts())->toBe([
        ['kind' => Claim::FIELDTYPE, 'name' => 'autoloaded', 'owners' => ['fixture/c', 'fixture/d']],
        ['kind' => Claim::COMPONENT, 'name' => 'c-only-fieldtype', 'owners' => ['fixture/c', 'fixture/d']],
    ]);
});

/**
 * Unreadable findings as `owner|subject|reason` strings, for readable comparison.
 *
 * @param  list<Unreadable>  $unreadable  The findings to describe.
 * @return list<string>
 */
function unreadableDescribe(array $unreadable): array
{
    return array_map(fn (Unreadable $item): string => $item->owner.'|'.$item->subject.'|'.$item->reason, $unreadable);
}

it('claims component names registered in modules the entry imports, by every import form it follows', function (): void {
    // Read a package whose entry registers two names itself and imports the rest
    $map = OwnerMap::fromDirectories(['fixture/e' => __DIR__.'/../Support/Fixtures/PackageE/']);

    // Expect the entry's own names, then each module's: a static import, a directory index, a re-export, a Vue file,
    // an import cycle (once), extensionless imports of a .js and a .mjs file, a .ts file and a dotted name, dynamic imports with a literal
    // path (written with spaces, across lines, and with a second argument; one name all digits), a call after a string holding a
    // comment marker and before a trailing comment, and nothing from a comment, a stylesheet or a bare package
    expect(ownershipDescribe($map->claims()))->toBe([
        'fixture/e|Control Panel component|entry-own',
        'fixture/e|Control Panel component|tick-name',
        'fixture/e|Control Panel component|after-string-marker',
        'fixture/e|Control Panel component|imported-a',
        'fixture/e|Control Panel component|nested-index',
        'fixture/e|Control Panel component|reexported',
        'fixture/e|Control Panel component|in-vue',
        'fixture/e|Control Panel component|cycle-a',
        'fixture/e|Control Panel component|plain-js',
        'fixture/e|Control Panel component|modern-mjs',
        'fixture/e|Control Panel component|typed-ts',
        'fixture/e|Control Panel component|legacy-cjs',
        'fixture/e|Control Panel component|widget-jsx',
        'fixture/e|Control Panel component|panel-tsx',
        'fixture/e|Control Panel component|ts-only',
        'fixture/e|Control Panel component|date-utils',
        'fixture/e|Control Panel component|lazy-loaded',
        'fixture/e|Control Panel component|2026',
        'fixture/e|Control Panel component|spaced',
        'fixture/e|Control Panel component|multi',
        'fixture/e|Control Panel component|options',
    ])->and($map->unreadable())->toBe([]);
});

it('reports what hides a registration instead of skipping it', function (): void {
    // Read a package whose entry registers with a variable, an interpolated template, a concatenation, a glob, a computed import,
    // an import that is missing and one that leaves the package
    $map = OwnerMap::fromDirectories(['fixture/f' => __DIR__.'/../Support/Fixtures/PackageF/']);

    // Expect the readable names claimed, including one imported after the bad imports, and each of the others reported once
    // with its file and an excerpt
    expect(ownershipDescribe($map->claims()))->toBe([
        'fixture/f|Control Panel component|good-name',
        'fixture/f|Control Panel component|later-name',
    ])
        ->and(unreadableDescribe($map->unreadable()))->toBe([
            'fixture/f|scripts/entry.js: Statamic.component(name, {})|the component name is not a string literal',
            'fixture/f|scripts/entry.js: Statamic.$components.register(`a-${id}`, {})|the component name is not a string literal',
            "fixture/f|scripts/entry.js: Statamic.component('a-' + suffix, {})|the component name is not a string literal",
            "fixture/f|scripts/entry.js: import.meta.glob('./components/*.js')|import.meta.glob cannot be followed",
            'fixture/f|scripts/entry.js: import(path)|a dynamic import with a computed path cannot be followed',
            'fixture/f|scripts/entry.js: ./missing.js|the imported file was not found',
            'fixture/f|scripts/entry.js: ../../outside.js|the import resolves outside the package',
        ]);
});

it('does not claim a name registered by a file outside the package', function (): void {
    // Read the package whose entry imports a file one level above its root
    $names = array_map(fn (Claim $claim): string => $claim->name, OwnerMap::fromDirectories(['fixture/f' => __DIR__.'/../Support/Fixtures/PackageF/'])->claims());

    // Expect the outside file's registration never read
    expect($names)->not->toContain('outside-name');
});

it('reports a manifest that is missing, not valid JSON, or not a JSON object', function (string $directory, string $reason): void {
    // Read a fixture package whose composer.json cannot be used
    $map = OwnerMap::fromDirectories(['fixture/m' => __DIR__.'/../Support/Fixtures/'.$directory.'/']);

    // Expect no claims and one finding for the manifest, with its reason
    expect($map->claims())->toBe([])
        ->and($map->unreadable())->toHaveCount(1)
        ->and($map->unreadable()[0]->owner)->toBe('fixture/m')
        ->and($map->unreadable()[0]->subject)->toEndWith('composer.json')
        ->and($map->unreadable()[0]->reason)->toBe($reason);
})->with([
    'missing' => ['scripts', 'the manifest does not exist'],
    'invalid JSON' => ['Manifests/invalid-json', 'the manifest is not valid JSON: Syntax error'],
    'scalar' => ['Manifests/scalar', 'the manifest is not a JSON object'],
    'null' => ['Manifests/json-null', 'the manifest is not a JSON object'],
]);

it('does not report a manifest that is valid but lists no provider', function (string $manifest): void {
    // Read a fixture package whose composer.json is an object that names no provider
    $map = OwnerMap::fromDirectories(['fixture/'.$manifest => __DIR__.'/../Support/Fixtures/Manifests/'.$manifest.'/']);

    // Expect nothing reported
    expect($map->unreadable())->toBe([]);
})->with(['extra-scalar', 'laravel-scalar', 'no-extra']);

it('reads a provider listed as a single string, as Laravel does, and reports one that cannot be autoloaded', function (): void {
    // Read a manifest whose `providers` is one real class name, and another whose is a string naming no class
    $fixtures = __DIR__.'/../Support/Fixtures/Manifests/';
    $real = OwnerMap::fromDirectories(['fixture/e' => $fixtures.'provider-string/']);
    $missing = OwnerMap::fromDirectories(['fixture/x' => $fixtures.'providers-scalar/']);

    // Expect the real provider's fieldtype claims read, and the other reported by its name
    expect(ownershipDescribe($real->claims()))->toBe(['fixture/e|fieldtype handle|listed', 'fixture/e|fieldtype handle|autoloaded'])
        ->and($real->unreadable())->toBe([])
        ->and(unreadableDescribe($missing->unreadable()))->toBe(['fixture/x|x|the provider class cannot be autoloaded']);
});

it('reports a listed provider that is not a class name or cannot be autoloaded, and skips a class that is no provider', function (): void {
    // Read a fixture whose manifest lists a real provider, a class that is not one, a missing class and a number
    $directory = __DIR__.'/../Support/Fixtures/PackageC/';
    $map = OwnerMap::fromDirectories(['fixture/c' => $directory]);

    // Expect the missing class and the number reported, and the class that is no provider passed over without a finding
    expect(unreadableDescribe($map->unreadable()))->toBe([
        'fixture/c|Not\\A\\Class|the provider class cannot be autoloaded',
        'fixture/c|'.$directory.'composer.json|a listed provider is not a class name',
    ]);
});

it('reports a listed fieldtype class that cannot be autoloaded', function (): void {
    // Read a provider that lists a class which does not exist, then one that does
    $map = OwnerMap::fromProvider('fixture/g', PackageGProvider::class, __DIR__.'/../Support/Fixtures/PackageG/');

    // Expect no claim for the missing class, the next listed class still claimed, and a finding that names the missing one
    expect(ownershipDescribe($map->claims()))->toBe(['fixture/g|fieldtype handle|listed'])
        ->and(unreadableDescribe($map->unreadable()))->toBe(['fixture/g|Not\\A\\Fieldtype|the fieldtype class cannot be autoloaded']);
});

it('keeps the claims a provider yields through the claims-only entry point', function (): void {
    // Read the same provider through both entry points
    $directory = __DIR__.'/../Support/Fixtures/PackageE/';

    // Expect the same claims, since the one wraps the other
    expect(OwnerMap::claimsFromProvider('fixture/e', PackageEProvider::class, $directory))
        ->toEqual(OwnerMap::fromProvider('fixture/e', PackageEProvider::class, $directory)->claims());
});

it('fails the assertion when a registration was unreadable, naming it', function (): void {
    // Read a package that hides registrations, with no conflicting names
    $map = OwnerMap::fromDirectories(['fixture/f' => __DIR__.'/../Support/Fixtures/PackageF/']);

    // Expect the assertion to throw, naming the owner, the excerpt and the reason, and to carry the findings
    $thrown = null;

    try {
        $map->assertSingleOwners();
    } catch (OwnershipUnreadable $ownershipUnreadable) {
        $thrown = $ownershipUnreadable;
    }

    expect($thrown)->toBeInstanceOf(OwnershipUnreadable::class)
        ->and($thrown?->unreadable)->toBe($map->unreadable())
        ->and($thrown?->getMessage())->toContain('fixture/f: scripts/entry.js: import(path) (a dynamic import with a computed path cannot be followed)')
        ->and($thrown?->getMessage())->toContain('; ');
});

it('reports a conflict before an unreadable registration, and keeps the unreadable list', function (): void {
    // A map with a conflict and an unreadable finding together
    $unreadable = new Unreadable('fixture/x', 'composer.json', 'the manifest does not exist');
    $map = new OwnerMap([
        new Claim('fixture/a', Claim::FIELDTYPE, 'same'),
        new Claim('fixture/b', Claim::FIELDTYPE, 'same'),
    ], [$unreadable]);

    // Expect the conflict thrown, and the finding still readable
    expect(fn () => $map->assertSingleOwners())->toThrow(OwnershipConflict::class)
        ->and($map->unreadable())->toBe([$unreadable]);
});

it('does not throw where nothing conflicts and nothing is unreadable', function (): void {
    // Read the package whose entry reaches every module by a readable import
    $map = OwnerMap::fromDirectories(['fixture/e' => __DIR__.'/../Support/Fixtures/PackageE/']);

    // Expect no exception
    $map->assertSingleOwners();

    expect($map->unreadable())->toBe([]);
});

it('keeps the unreadable findings of every package, in order', function (): void {
    // Read two packages whose manifests cannot be used, in a known order
    $fixtures = __DIR__.'/../Support/Fixtures/';
    $map = OwnerMap::fromDirectories(['fixture/one' => $fixtures.'scripts/', 'fixture/two' => $fixtures.'Manifests/invalid-json/']);

    // Expect a finding for each, the first package's first
    expect(array_map(fn (Unreadable $item): string => $item->owner, $map->unreadable()))->toBe(['fixture/one', 'fixture/two']);
});

it('keeps the unreadable findings of every provider of one package', function (): void {
    // Read a package whose manifest lists an unloadable provider, and another that lists an unloadable fieldtype
    $fixtures = __DIR__.'/../Support/Fixtures/';
    $map = OwnerMap::fromDirectories(['fixture/c' => $fixtures.'PackageC/', 'fixture/g' => $fixtures.'PackageG/']);

    // Expect the findings of the first package kept alongside those of the second
    expect(array_map(fn (Unreadable $item): string => $item->owner, $map->unreadable()))->toBe(['fixture/c', 'fixture/c', 'fixture/g']);
});

it('does not read an import from a sibling directory whose name begins with the package directory name', function (): void {
    // Read a package whose entry imports a file in a directory beside it, with a longer name that starts the same
    $map = OwnerMap::fromProvider(
        'fixture/h',
        PackageHProvider::class,
        __DIR__.'/../Support/Fixtures/PackageH/',
    );

    // Expect the sibling's registration not claimed, and the import reported as outside
    expect($map->claims())->toBe([])
        ->and(unreadableDescribe($map->unreadable()))->toBe(['fixture/h|scripts/entry.js: ../../PackageHOutside/x.js|the import resolves outside the package']);
});

it('shortens a long excerpt to sixty characters and trims the space it may end on', function (): void {
    // Read a package whose entry registers under two computed names, one with no space near the cut and one with a space at it
    $map = OwnerMap::fromProvider(
        'fixture/i',
        PackageIProvider::class,
        __DIR__.'/../Support/Fixtures/PackageI/',
    );

    // Expect exactly sixty characters of the first, and the second cut after its last non-space character
    expect(array_map(fn (Unreadable $item): string => $item->subject, $map->unreadable()))->toBe([
        'scripts/entry.js: Statamic.component('.str_repeat('n', 41),
        'scripts/entry.js: Statamic.component('.str_repeat('a', 40),
    ]);
});
