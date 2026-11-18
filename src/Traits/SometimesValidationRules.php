<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Traits;

/**
 * Helpers for Laravel `sometimes` with Statamic blueprint validation (conditional
 * `if` fields that omit values in the CP, and nested `extraRules()` dotted keys).
 *
 * Composed by FieldtypeValidationHelpers; uses its `explodeStatamicValidationRules()`
 * and `explodedValidateRulesContain()` on the consuming class.
 *
 * @see  FieldtypeValidationHelpers
 */
trait SometimesValidationRules
{
    /**
     * Reorder blueprint `validate` rules so `sometimes` runs first when present.
     *
     * Laravel expects `sometimes` before `required` on the same attribute so a
     * missing key skips the rest of the rules.
     *
     * @param  mixed  $validate  Raw `validate` config (list, pipe string, empty, or null)
     * @return array<int, string|object>|list<string|object>|string|null
     */
    protected function normalizeBlueprintValidateForSometimes(mixed $validate): mixed
    {
        // The guard is a shortcut: Statamic's explodeRules() turns every empty value into [], and the
        // branches below then hand back the input (null and the empty string) or that empty list.
        // @pest-mutate-ignore: RemoveArrayItem,EmptyStringToNotEmpty
        if (in_array($validate, [null, '', []], true)) {
            // Without this return the same value comes back from the branches below.
            // @pest-mutate-ignore: RemoveEarlyReturn
            return $validate === null || $validate === '' ? $validate : [];
        }

        $rules = $this->explodeStatamicValidationRules($validate);

        if (! $this->explodedValidateRulesContain($rules, 'sometimes')) {
            if (\is_array($validate)) {
                // explodeRules() returns a list unchanged, so $validate and $rules are the same list here.
                // @pest-mutate-ignore: RemoveEarlyReturn
                return $rules;
            }

            /** @var array<int, object|string>|string|null $validate */
            return $validate;
        }

        // array_merge renumbers integer keys, so dropping array_values changes nothing observable.
        // @pest-mutate-ignore: UnwrapArrayValues
        $rest = array_values(array_filter(
            $rules,
            static fn (mixed $rule): bool => $rule !== 'sometimes'
        ));

        return array_merge(['sometimes'], $rest);
    }

    /**
     * Lead each rule set with `sometimes` so dotted keys are skipped when absent.
     *
     * @param  array<string, array<int, string|object>>  $keyedRules
     * @return array<string, array<int, string|object>>
     */
    protected function prependSometimesToKeyedRuleSets(array $keyedRules): array
    {
        $out = [];

        foreach ($keyedRules as $key => $rules) {
            $exploded = $this->explodeStatamicValidationRules($rules);
            $out[$key] = array_merge(
                ['sometimes'],
                // array_merge renumbers integer keys, so dropping array_values changes nothing observable.
                // @pest-mutate-ignore: UnwrapArrayValues
                array_values(array_filter(
                    $exploded,
                    static fn (mixed $rule): bool => $rule !== 'sometimes'
                )),
            );
        }

        return $out;
    }

    /**
     * When blueprint validate includes `sometimes`, prepend it to each keyed rule set.
     *
     * @param  array<string, array<int, string|object>>  $keyedRules
     * @param  array<int, string|object>  $explodedValidateRules  From `explodedBlueprintValidateRulesFromConfig()`
     * @return array<string, array<int, string|object>>
     */
    protected function conditionallyPrependSometimesToKeyedRuleSets(
        array $keyedRules,
        array $explodedValidateRules
    ): array {
        if (! $this->explodedValidateRulesContain($explodedValidateRules, 'sometimes')) {
            return $keyedRules;
        }

        return $this->prependSometimesToKeyedRuleSets($keyedRules);
    }
}
