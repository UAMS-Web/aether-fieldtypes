<?php

declare(strict_types=1);

use Statamic\Fields\Field;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Checkboxes;

/**
 * Tests for UAMSWeb\Aether\Fieldtypes\Fieldtypes\Checkboxes: its identity, the selection-limit config
 * fields it adds to Statamic's own, its render data and the selection-count rules it builds.
 *
 * @command  vendor/bin/pest tests/Integration/Fieldtypes/CheckboxesFieldtypeTest.php
 */
covers(Checkboxes::class);

/**
 * Build a Checkboxes fieldtype bound to a Field with the given config.
 *
 * @param  array<string, mixed>  $config  Field config merged over the type handle and two options
 */
function checkboxesFieldtype(array $config = []): Checkboxes
{
    // Build the fieldtype under test
    $fieldtype = new Checkboxes;

    // Bind a Field with the given config over the type handle
    $fieldtype->setField(new Field('choices', array_merge(['type' => 'checkboxes', 'options' => ['a' => 'A', 'b' => 'B']], $config)));

    return $fieldtype;
}

/**
 * The field definitions of one config section, keyed by handle.
 *
 * @return array<string, mixed> The section's fields
 */
function checkboxesSectionFields(string $display): array
{
    $sections = new ReflectionMethod(Checkboxes::class, 'configFieldItems')->invoke(new Checkboxes);

    if (! is_array($sections)) {
        throw new UnexpectedValueException('Checkboxes::configFieldItems() did not return an array.');
    }

    foreach ($sections as $section) {
        if (is_array($section) && ($section['display'] ?? null) === $display && is_array($section['fields'] ?? null)) {
            return $section['fields'];
        }
    }

    throw new UnexpectedValueException(sprintf('Checkboxes has no "%s" config section.', $display));
}

/**
 * Run a rule closure over a value and return the failure message it produced, if any.
 *
 * @param  array<string, mixed>  $config  The field config the rules are built from
 * @return list<string> Messages from every rule closure
 */
function checkboxesFailures(array $config, mixed $value): array
{
    $messages = [];

    foreach (checkboxesFieldtype()->getValidationRules(null, $config) as $rule) {
        if (! $rule instanceof Closure) {
            throw new UnexpectedValueException('Expected a closure rule.');
        }

        $rule('choices', $value, function (string $message) use (&$messages): void {
            $messages[] = $message;
        });
    }

    return $messages;
}

it('identifies itself with its own component and the parent icon', function (): void {
    $fieldtype = new Checkboxes;

    expect(new ReflectionProperty(Checkboxes::class, 'component')->getValue($fieldtype))->toBe('custom_checkboxes')
        ->and($fieldtype->icon())->toBe((new Statamic\Fieldtypes\Checkboxes)->icon());
});

it('places its selection fields between the preset fields and the options, and its randomize and limit fields after', function (): void {
    expect(array_keys(checkboxesSectionFields('Selection & Options')))->toBe([
        'preset',
        'language_scope',
        'language_standard',
        'state_types',
        'mime_type_categories',
        'enable_other',
        'other_label',
        'other_placeholder',
        'selection_limit',
        'min_selections',
        'max_selections',
        'exact_selections',
        'options',
        'randomize_order',
        'enable_choice_limits',
        'choice_limits',
        'choice_limit_message',
    ]);
});

it('pins the selection limit field', function (): void {
    expect(checkboxesSectionFields('Selection & Options')['selection_limit'])->toBe([
        'display' => 'Selection Limit',
        'instructions' => 'Limit how many options can be selected.',
        'type' => 'select',
        'options' => [
            'unlimited' => 'Unlimited Number of Selections',
            'exact' => 'Exact Number of Selections',
            'range' => 'Range of Selections',
        ],
        'default' => 'unlimited',
        'validate' => ['required'],
    ]);
});

it('pins the minimum, maximum and exact count fields', function (): void {
    $fields = checkboxesSectionFields('Selection & Options');

    expect($fields['min_selections'])->toBe([
        'display' => 'Minimum Number of Selections',
        'instructions' => 'The minimum number of options a user must select.',
        'type' => 'integer',
        'min' => 1,
        'step' => 1,
        'if' => ['selection_limit' => 'range'],
        'validate' => ['required_if:selection_limit,range', 'exclude_unless:selection_limit,range'],
    ])->and($fields['max_selections'])->toBe([
        'display' => 'Maximum Number of Selections',
        'instructions' => 'The maximum number of options a user can select.',
        'type' => 'integer',
        'min' => 1,
        'step' => 1,
        'if' => ['selection_limit' => 'range'],
        'validate' => ['required_if:selection_limit,range', 'exclude_unless:selection_limit,range'],
    ])->and($fields['exact_selections'])->toBe([
        'display' => 'Exact Number of Selections',
        'instructions' => 'The exact number of options a user must select.',
        'type' => 'integer',
        'min' => 1,
        'step' => 1,
        'if' => ['selection_limit' => 'exact'],
        'validate' => ['required_if:selection_limit,exact', 'exclude_unless:selection_limit,exact'],
    ]);
});

it('adds the dynamic default value field to the Data and Format section', function (): void {
    expect(array_keys(checkboxesSectionFields('Data & Format')))->toContain('calculation_query');
});

it('adds the choices the form template renders to the renderable field data', function (): void {
    $data = checkboxesFieldtype()->extraRenderableFieldData();

    expect($data)->toHaveKey('full_choices')
        ->and($data['full_choices'])->toBeArray()
        ->and(array_diff_key($data, ['full_choices' => true]))->toBe((new Statamic\Fieldtypes\Checkboxes)->setField(checkboxesFieldtype()->field())->extraRenderableFieldData())
        ->and($data)->not->toBe(['full_choices' => $data['full_choices']]);
});

it('adds no rules for an unlimited selection or a config with no limit', function (array $config): void {
    expect(checkboxesFieldtype()->getValidationRules(null, $config))->toBe([]);
})->with([
    'no config' => [[]],
    'unlimited' => [['selection_limit' => 'unlimited', 'min_selections' => 2, 'max_selections' => 3, 'exact_selections' => 2]],
]);

it('fails a range selection below the minimum, but not an empty one or one at the minimum', function (): void {
    $config = ['selection_limit' => 'range', 'min_selections' => 2];

    expect(checkboxesFailures($config, ['a']))->toBe(['You must select at least 2 options.'])
        ->and(checkboxesFailures($config, ['a', 'b']))->toBe([])
        ->and(checkboxesFailures($config, []))->toBe([])
        ->and(checkboxesFailures($config, 'a'))->toBe([]);
});

it('fails a range selection above the maximum, but not one at the maximum', function (): void {
    $config = ['selection_limit' => 'range', 'max_selections' => 2];

    expect(checkboxesFailures($config, ['a', 'b', 'c']))->toBe(['You may not select more than 2 options.'])
        ->and(checkboxesFailures($config, ['a', 'b']))->toBe([])
        ->and(checkboxesFailures($config, 'a'))->toBe([]);
});

it('builds both range rules when both bounds are set', function (): void {
    $config = ['selection_limit' => 'range', 'min_selections' => '1', 'max_selections' => '2'];

    expect(checkboxesFieldtype()->getValidationRules(null, $config))->toHaveCount(2)
        ->and(checkboxesFailures($config, ['a', 'b', 'c']))->toBe(['You may not select more than 2 options.']);
});

it('ignores a range bound that is missing or not numeric', function (): void {
    expect(checkboxesFieldtype()->getValidationRules(null, ['selection_limit' => 'range']))->toBe([])
        ->and(checkboxesFieldtype()->getValidationRules(null, ['selection_limit' => 'range', 'min_selections' => 'x', 'max_selections' => 'y']))->toBe([])
        ->and(checkboxesFieldtype()->getValidationRules(null, ['selection_limit' => 'range', 'min_selections' => null, 'max_selections' => null]))->toBe([]);
});

it('ignores the range bounds unless the limit is a range', function (): void {
    expect(checkboxesFieldtype()->getValidationRules(null, ['selection_limit' => 'exact', 'min_selections' => 2, 'max_selections' => 3]))->toBe([]);
});

it('fails an exact selection of any other size, but not an empty one or an exact match', function (): void {
    $config = ['selection_limit' => 'exact', 'exact_selections' => 2];

    expect(checkboxesFailures($config, ['a']))->toBe(['You must select exactly 2 options.'])
        ->and(checkboxesFailures($config, ['a', 'b', 'c']))->toBe(['You must select exactly 2 options.'])
        ->and(checkboxesFailures($config, ['a', 'b']))->toBe([])
        ->and(checkboxesFailures($config, []))->toBe([])
        ->and(checkboxesFailures($config, 'a'))->toBe([]);
});

it('ignores an exact count that is missing or not numeric, or a limit that is not exact', function (): void {
    expect(checkboxesFieldtype()->getValidationRules(null, ['selection_limit' => 'exact']))->toBe([])
        ->and(checkboxesFieldtype()->getValidationRules(null, ['selection_limit' => 'exact', 'exact_selections' => 'x']))->toBe([])
        ->and(checkboxesFieldtype()->getValidationRules(null, ['selection_limit' => 'range', 'exact_selections' => 2]))->toBe([]);
});

it('builds its field rules from the field config, after the parent rules', function (): void {
    $fieldtype = checkboxesFieldtype(['selection_limit' => 'exact', 'exact_selections' => 2]);
    $parent = (new Statamic\Fieldtypes\Checkboxes)->setField($fieldtype->field())->rules();
    $rules = $fieldtype->rules();

    expect(array_is_list($rules))->toBeTrue()
        ->and($rules)->toHaveCount(count($parent) + 1)
        ->and(end($rules))->toBeInstanceOf(Closure::class);
});

it('reads a fractional bound as a whole number, in the message and in the comparison', function (): void {
    expect(checkboxesFailures(['selection_limit' => 'range', 'min_selections' => 2.5], ['a', 'b']))->toBe([])
        ->and(checkboxesFailures(['selection_limit' => 'range', 'min_selections' => 2.5], ['a']))->toBe(['You must select at least 2 options.'])
        ->and(checkboxesFailures(['selection_limit' => 'range', 'max_selections' => 2.5], ['a', 'b', 'c']))->toBe(['You may not select more than 2 options.'])
        ->and(checkboxesFailures(['selection_limit' => 'exact', 'exact_selections' => 2.5], ['a', 'b']))->toBe([])
        ->and(checkboxesFailures(['selection_limit' => 'exact', 'exact_selections' => 2.5], ['a']))->toBe(['You must select exactly 2 options.']);
});
