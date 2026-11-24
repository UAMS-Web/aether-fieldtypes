<?php

declare(strict_types=1);

use UAMSWeb\Aether\Fieldtypes\Rules\BaseValidationRule;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\RecordingValidationRule;

/**
 * Tests for UAMSWeb\Aether\Fieldtypes\Rules\BaseValidationRule: the shared value preparation and the
 * `isValid()` check that runs a rule without a failure callback.
 *
 * @command  vendor/bin/pest tests/Integration/Rules/BaseValidationRuleTest.php
 */
covers(BaseValidationRule::class);

/**
 * A rule that accepts only the string `ok`, and records what it was asked to check.
 */
function baseRuleProbe(): RecordingValidationRule
{
    return new RecordingValidationRule;
}

/**
 * Run `validate()` and collect the failure messages.
 *
 * @return list<string> The messages passed to the failure callback
 */
function baseRuleMessages(BaseValidationRule $rule, mixed $value): array
{
    $messages = [];
    $rule->validate('field', $value, function (string $message) use (&$messages): void {
        $messages[] = $message;
    });

    return $messages;
}

it('accepts a value the rule accepts', function (): void {
    $rule = baseRuleProbe();

    expect($rule->isValid('ok'))->toBeTrue()
        ->and(baseRuleMessages($rule, 'ok'))->toBe([]);
});

it('rejects a value the rule rejects', function (): void {
    $rule = baseRuleProbe();

    expect($rule->isValid('nope'))->toBeFalse()
        ->and(baseRuleMessages($rule, 'nope'))->toBe(['not ok']);
});

it('leaves an empty value to other rules without validating it', function (mixed $empty): void {
    $rule = baseRuleProbe();

    expect($rule->isValid($empty))->toBeTrue()
        ->and(baseRuleMessages($rule, $empty))->toBe([])
        ->and($rule->seen)->toBe([]);
})->with([
    'null' => [null],
    'empty string' => [''],
    'zero' => [0],
    'false' => [false],
]);

it('fails an array with the shared message, in validate and in isValid, without validating it', function (): void {
    $rule = baseRuleProbe();

    expect(baseRuleMessages($rule, ['ok']))->toBe(['The value must be a string, not an array.'])
        ->and($rule->isValid(['ok']))->toBeFalse()
        ->and($rule->seen)->toBe([]);
});

it('casts a non-string scalar to a string before validating', function (): void {
    $rule = baseRuleProbe();

    expect($rule->isValid(42))->toBeFalse()
        ->and($rule->seen)->toBe(['=42']);
});

it('names the attribute in validate, and an empty attribute in isValid', function (): void {
    $rule = baseRuleProbe();

    baseRuleMessages($rule, 'x');
    $rule->isValid('y');

    expect($rule->seen)->toBe(['field=x', '=y']);
});
