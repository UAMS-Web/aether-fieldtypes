<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use UAMSWeb\Aether\Fieldtypes\Traits\FieldtypeValidationHelpers;

/**
 * Exposes protected trait helpers for unit testing.
 */
final class FieldtypeValidationHelpersHarness
{
    use FieldtypeValidationHelpers {
        explodeStatamicValidationRules as public;
        explodedBlueprintValidateRulesFromConfig as public;
        blueprintConfigMarksFieldRequired as public;
        explodedValidateRulesContain as public;
        conditionallyPrependSometimesToKeyedRuleSets as public;
        normalizeBlueprintValidateForSometimes as public;
        prependSometimesToKeyedRuleSets as public;
    }
}
