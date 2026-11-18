<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;
use UAMSWeb\Aether\Fieldtypes\Traits\ConfigCache;
use UAMSWeb\Aether\Fieldtypes\Traits\DefinesInputConfigFields;
use UAMSWeb\Aether\Fieldtypes\Traits\ProvidesAutocompleteOptions;

/**
 * A fieldtype that composes DefinesInputConfigFields and overrides none of its hooks except
 * `getPresets()`, which the trait looks for by name, so a test can read the hooks' own defaults.
 */
final class DefinesInputConfigFieldsDefaultsHarness extends Fieldtype
{
    use ConfigCache;
    use DefinesInputConfigFields {
        getConfigFieldOverrides as public;
        getUnsetConfigFields as public;
        getHiddenConfigFields as public;
        getAdditionalConfigFields as public;
        getInputType as public;
    }
    use ProvidesAutocompleteOptions;

    /** @var string */
    protected static $handle = 'defines_input_config_fields_defaults_harness';

    /** @return array<string, mixed> */
    private function getPresets(): array
    {
        return [];
    }
}
