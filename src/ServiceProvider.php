<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes;

use Illuminate\Support\Facades\Route;
use Statamic\Events\FormSubmitted;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;
use UAMSWeb\Aether\Fieldtypes\Contracts\EgressGuard;
use UAMSWeb\Aether\Fieldtypes\Http\Controllers\NominatimSearchController;
use UAMSWeb\Aether\Fieldtypes\Listeners\EnforceChoiceSubmissionLimits;
use UAMSWeb\Aether\Fieldtypes\Support\DefaultEgressGuard;

/**
 * Service provider for the add-on.
 *
 * The host application discovers it through `extra.laravel.providers` in
 * `composer.json`. Register the add-on's tags, fieldtypes, listeners, routes and
 * the like here, using the properties `AddonServiceProvider` documents, or in
 * `bootAddon()` for anything that needs Statamic booted first.
 */
class ServiceProvider extends AddonServiceProvider
{
    /**
     * The Vite build of the Control Panel assets. `input` and `publicDirectory`
     * must match `vite.config.js`; the built files are committed under
     * `resources/dist`.
     *
     * @var array{input: list<string>, publicDirectory: string}
     */
    // A property default holds no executable line, so coverage reports each item as uncovered
    // although tests/Unit/ViteBuildTest.php pins the whole array against vite.config.js.
    // @pest-mutate-ignore: RemoveArrayItem
    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    /**
     * Refuse a form submission its choice limits reject.
     *
     * @var array<class-string, list<class-string>>
     */
    // A property default holds no executable line, so coverage reports it uncovered although
    // tests/Integration/Forms/ChoiceSubmissionLimitsEnforcementTest.php posts through the registered listener.
    // @pest-mutate-ignore: RemoveArrayItem
    protected $listen = [
        FormSubmitted::class => [
            EnforceChoiceSubmissionLimits::class,
        ],
    ];

    /**
     * Register the add-on's map defaults under the `maps` config key.
     *
     * A host's own `config/maps.php` wins for each top-level group it defines, so a site that already
     * configures the picker is not changed. It also offers the default egress guard, which a host
     * replaces by binding its own implementation of the contract.
     */
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/maps.php', 'maps');

        // Offer the default guard unless the host has already bound one
        $this->app->bindIf(EgressGuard::class, DefaultEgressGuard::class);
    }

    /**
     * Boot the add-on once Statamic has booted.
     */
    public function bootAddon(): void
    {
        // Offer the public form partials under Statamic's own view namespace. Statamic looks a field's
        // view up as `statamic::forms.fields.<handle>`, and the host's `vendor/statamic` override
        // directory is searched before this one, so a host partial of the same name still wins.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'statamic');

        // Answer the GPS picker's address search, which calls `cp_url('gps/nominatim-search')`
        Statamic::pushCpRoutes(function (): void {
            Route::get('gps/nominatim-search', NominatimSearchController::class)->name('gps.nominatim-search');
        });
    }
}
