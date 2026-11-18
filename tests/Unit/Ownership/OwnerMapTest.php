<?php

declare(strict_types=1);

use UAMSWeb\Aether\Fieldtypes\Ownership\Claim;
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnerMap;

/**
 * Owner map: a name claimed by two packages is reported, and one claimed by a single package
 * is not.
 *
 * @command  vendor/bin/pest tests/Unit/Ownership/OwnerMapTest.php
 */
it('reports a handle claimed by two packages, with both owners', function (): void {
    // Two packages claim one handle, and each also claims a handle of its own
    $map = new OwnerMap([
        new Claim('vendor/one', Claim::FIELDTYPE, 'text'),
        new Claim('vendor/two', Claim::FIELDTYPE, 'text'),
        new Claim('vendor/one', Claim::FIELDTYPE, 'only_one'),
        new Claim('vendor/two', Claim::FIELDTYPE, 'only_two'),
    ]);

    // Expect only the shared handle, naming both owners
    expect($map->conflicts())->toBe([
        ['kind' => Claim::FIELDTYPE, 'name' => 'text', 'owners' => ['vendor/one', 'vendor/two']],
    ]);
});

it('reports a component name claimed by two packages', function (): void {
    // Two packages claim one component name
    $map = new OwnerMap([
        new Claim('vendor/one', Claim::COMPONENT, 'text-fieldtype'),
        new Claim('vendor/two', Claim::COMPONENT, 'text-fieldtype'),
    ]);

    // Expect the component name reported
    expect($map->conflicts())->toBe([
        ['kind' => Claim::COMPONENT, 'name' => 'text-fieldtype', 'owners' => ['vendor/one', 'vendor/two']],
    ]);
});

it('reports no conflict where every name has one owner', function (): void {
    // One handle and one component name, each with a single owner
    $map = new OwnerMap([
        new Claim('vendor/one', Claim::FIELDTYPE, 'text'),
        new Claim('vendor/two', Claim::COMPONENT, 'text-fieldtype'),
    ]);

    // Expect nothing reported
    expect($map->conflicts())->toBe([]);
});

it('does not count one package registering a name twice as two owners', function (): void {
    // One package claims one handle twice
    $map = new OwnerMap([
        new Claim('vendor/one', Claim::FIELDTYPE, 'text'),
        new Claim('vendor/one', Claim::FIELDTYPE, 'text'),
    ]);

    // Expect no conflict
    expect($map->conflicts())->toBe([]);
});

it('keeps a handle and a component name of the same spelling apart', function (): void {
    // Two packages, one claiming the spelling as a handle and the other as a component name
    $map = new OwnerMap([
        new Claim('vendor/one', Claim::FIELDTYPE, 'shared'),
        new Claim('vendor/two', Claim::COMPONENT, 'shared'),
    ]);

    // Expect no conflict, since the two kinds are separate namespaces
    expect($map->conflicts())->toBe([]);
});
