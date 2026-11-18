<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use UAMSWeb\Aether\Fieldtypes\Traits\ConfigSectionWrapper;

/**
 * Exposes the ConfigSectionWrapper trait's helpers for testing, over the fixed
 * parent config in {@see ConfigSectionWrapperParent}.
 */
final class ConfigSectionWrapperHarness extends ConfigSectionWrapperParent
{
    use ConfigSectionWrapper {
        wrapConfigFieldsInSections as public;
        addFieldsToExistingSections as public;
        addFieldsToExistingSectionsWithMultiplePositions as public;
        modifyExistingFields as public;
    }
}
