<?php

declare(strict_types=1);

use Statamic\Facades\Addon;
use UAMSWeb\Aether\Fieldtypes\Ownership\Claim;
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnerMap;
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnershipConflict;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageA\PackageAProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageB\PackageBProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageC\PackageCProvider;

covers(Claim::class, OwnerMap::class, OwnershipConflict::class);

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

    // Expect no name with two owners, and no exception from the assertion
    expect($map->conflicts())->toBe([]);
    $map->assertSingleOwners();
});

it('finds this add-on among the add-ons it inspects, so an empty result is not blindness', function (): void {
    // The add-on claims no fieldtype yet, so prove the walk reached it through a package that does
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
