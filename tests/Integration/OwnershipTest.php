<?php

declare(strict_types=1);

use Statamic\Facades\Addon;
use UAMSWeb\Aether\Fieldtypes\Ownership\Claim;
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnerMap;
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnershipConflict;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageA\PackageAProvider;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageB\PackageBProvider;

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
