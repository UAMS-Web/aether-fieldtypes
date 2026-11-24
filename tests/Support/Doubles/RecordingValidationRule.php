<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Closure;
use UAMSWeb\Aether\Fieldtypes\Rules\BaseValidationRule;

/**
 * A rule that accepts only the string `ok`, and records every attribute and value it was asked to check.
 */
class RecordingValidationRule extends BaseValidationRule
{
    /**
     * Every `attribute=value` pair validateValue received.
     *
     * @var list<string>
     */
    public array $seen = [];

    #[\Override]
    protected function validateValue(string $attribute, string $value, Closure $fail): void
    {
        $this->seen[] = $attribute.'='.$value;

        if ($value !== 'ok') {
            $fail('not ok');
        }
    }
}
