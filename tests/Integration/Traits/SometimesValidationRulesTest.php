<?php

declare(strict_types=1);

use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\FieldtypeValidationHelpersHarness;
use UAMSWeb\Aether\Fieldtypes\Traits\SometimesValidationRules;

covers(SometimesValidationRules::class);

/**
 * Tests for the SometimesValidationRules trait, reached through the harness that composes it with
 * FieldtypeValidationHelpers: `sometimes`-first ordering of blueprint `validate` rules and of
 * keyed rule sets.
 *
 * @command  vendor/bin/pest tests/Integration/Traits/SometimesValidationRulesTest.php
 */
it('returns an empty validate value unchanged in kind', function (mixed $validate, mixed $expected): void {
    expect((new FieldtypeValidationHelpersHarness)->normalizeBlueprintValidateForSometimes($validate))->toBe($expected);
})->with([
    'null stays null' => [null, null],
    'an empty string stays an empty string' => ['', ''],
    'an empty list stays an empty list' => [[], []],
]);

it('returns a validate string without sometimes as it came', function (): void {
    expect((new FieldtypeValidationHelpersHarness)->normalizeBlueprintValidateForSometimes('required|min:2'))
        ->toBe('required|min:2');
});

it('returns a validate list without sometimes as the list', function (): void {
    expect((new FieldtypeValidationHelpersHarness)->normalizeBlueprintValidateForSometimes(['required', 'min:2', 'string']))
        ->toBe(['required', 'min:2', 'string']);
});

it('moves sometimes to the front of a pipe string', function (): void {
    expect((new FieldtypeValidationHelpersHarness)->normalizeBlueprintValidateForSometimes('required|sometimes|min:1'))
        ->toBe(['sometimes', 'required', 'min:1']);
});

it('leads with one sometimes when the list names it twice', function (): void {
    expect((new FieldtypeValidationHelpersHarness)->normalizeBlueprintValidateForSometimes(['sometimes', 'required', 'sometimes']))
        ->toBe(['sometimes', 'required']);
});

it('keeps rule objects when it reorders', function (): void {
    $rule = new class {};

    $out = (new FieldtypeValidationHelpersHarness)->normalizeBlueprintValidateForSometimes([$rule, 'sometimes']);

    expect($out)->toHaveCount(2)
        ->and($out[0])->toBe('sometimes')
        ->and($out[1])->toBe($rule);
});

it('leads every keyed rule set with a single sometimes', function (): void {
    $out = (new FieldtypeValidationHelpersHarness)->prependSometimesToKeyedRuleSets([
        'a' => ['required', 'sometimes', 'string'],
        'b' => 'min:1|max:5',
    ]);

    expect($out)->toBe([
        'a' => ['sometimes', 'required', 'string'],
        'b' => ['sometimes', 'min:1', 'max:5'],
    ]);
});
