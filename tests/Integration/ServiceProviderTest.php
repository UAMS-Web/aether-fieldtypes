<?php

declare(strict_types=1);

use Statamic\Facades\Addon;
use UAMSWeb\Aether\Fieldtypes\Contracts\EgressGuard;
use UAMSWeb\Aether\Fieldtypes\Http\Controllers\NominatimSearchController;
use UAMSWeb\Aether\Fieldtypes\ServiceProvider;
use UAMSWeb\Aether\Fieldtypes\Support\DefaultEgressGuard;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\RecordingEgressGuard;

/**
 * The add-on boots inside a minimal Statamic.
 *
 * @command  vendor/bin/pest tests/Integration/ServiceProviderTest.php
 */
it('is discovered by Statamic as an add-on', function (): void {
    // Expect the add-on among those Statamic booted
    expect(Addon::all()->map->package()->all())->toContain('uams-web/aether-fieldtypes');
});

it('publishes its config file under the tag the README names', function (): void {
    // Read the paths published under the config tag
    $paths = ServiceProvider::pathsToPublish(null, 'aether-fieldtypes-config');

    // Expect the package config to publish to the app's config directory
    expect(array_map(realpath(...), array_keys($paths)))->toBe([realpath(__DIR__.'/../../config/aether-fieldtypes.php')])
        ->and(array_values($paths))->toBe([config_path('aether-fieldtypes.php')]);
});

it('offers the map defaults the GPS picker reads under the maps config key', function (): void {
    // Expect each group the picker reads, with the values the add-on ships
    expect(config('maps.default.zoom'))->toBe(14)
        ->and(config('maps.tiles.max_zoom'))->toBe(19)
        ->and(config('maps.tiles.url'))->toBe('https://tile.openstreetmap.org/{z}/{x}/{y}.png')
        ->and(config('maps.aerial.max_native_zoom'))->toBe(19)
        ->and(config('maps.nominatim.min_query_length'))->toBe(3);
});

it('keeps a host group of the maps config whole, and fills in the groups the host leaves out', function (): void {
    // A host that sets its own tile group but no aerial group, then the provider registers again
    config(['maps' => ['tiles' => ['url' => 'https://tiles.example.test/{z}/{x}/{y}.png']]]);
    new ServiceProvider(app())->register();

    // Expect the host's group untouched and the add-on's other groups filled in
    expect(config('maps.tiles'))->toBe(['url' => 'https://tiles.example.test/{z}/{x}/{y}.png'])
        ->and(config('maps.aerial.max_zoom'))->toBe(19)
        ->and(config('maps.default.zoom'))->toBe(14);
});

it('pins the address-search settings the controller reads', function (): void {
    // Expect every key of the nominatim group, with the values the add-on ships
    expect(config('maps.nominatim'))->toBe([
        'endpoint' => 'https://nominatim.openstreetmap.org/search',
        'user_agent' => '',
        'email' => '',
        'max_requests_per_second' => 1,
        'limit' => 5,
        'min_query_length' => 3,
        'country_codes' => 'us',
        'timeout' => 5,
    ]);
});

it('offers the default egress guard, and leaves a guard the host already bound alone', function (): void {
    // Expect the default through the container
    expect(app(EgressGuard::class))->toBeInstanceOf(DefaultEgressGuard::class);

    // A host binding of its own, then the provider registering again
    $host = new RecordingEgressGuard;
    app()->instance(EgressGuard::class, $host);
    new ServiceProvider(app())->register();

    // Expect the host's guard untouched
    expect(app(EgressGuard::class))->toBe($host);
});

it('answers the address-search route in the Control Panel', function (): void {
    // Expect the route under the Control Panel prefix, served by the add-on's controller
    $route = app('router')->getRoutes()->getByName('statamic.cp.gps.nominatim-search');

    expect($route)->not->toBeNull()
        ->and($route?->uri())->toEndWith('/gps/nominatim-search')
        ->and($route?->methods())->toContain('GET')
        ->and($route?->getActionName())->toBe(NominatimSearchController::class);
});
