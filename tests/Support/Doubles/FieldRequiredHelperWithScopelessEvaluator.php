<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use UAMSWeb\Aether\Fieldtypes\Rules\FieldRequiredHelper;

/**
 * A required check whose evaluator class offers no passes() method.
 */
final class FieldRequiredHelperWithScopelessEvaluator extends FieldRequiredHelper
{
    protected const string CONDITION_EVALUATOR = ScopelessEditingScope::class;
}
