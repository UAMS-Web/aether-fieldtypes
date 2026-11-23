<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use UAMSWeb\Aether\Fieldtypes\Rules\FieldRequiredHelper;

/**
 * A required check in a host without the form settings add-on, so its evaluator class does not exist.
 */
final class FieldRequiredHelperWithoutEvaluator extends FieldRequiredHelper
{
    protected const string CONDITION_EVALUATOR = 'No'.'\\Such\\Evaluator';
}
