<?php

declare(strict_types=1);

use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\DefinesInputConfigFieldsDefaultsHarness;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\DefinesInputConfigFieldsHarness;
use UAMSWeb\Aether\Fieldtypes\Traits\DefinesInputConfigFields;

covers(DefinesInputConfigFields::class);

/**
 * Tests for the DefinesInputConfigFields trait, driven through a harness that composes it alone:
 * the three field builders, the override and filter pass, the section grouping, the preset
 * selector and the additional-field injection.
 *
 * The two JSON files under tests/Support/Fixtures are snapshots of the builders' default output,
 * generated from this package's copy of the trait when it moved out of the site
 * (UAMS-Web/aether-fieldtypes#5). They pin the schema the Control Panel renders, so a change to a
 * label, an instruction, a condition or a key is a deliberate edit of the snapshot in the same
 * change.
 *
 * @command  vendor/bin/pest tests/Integration/Traits/DefinesInputConfigFieldsTest.php
 */

/**
 * A snapshot fixture, decoded.
 *
 * @return array<string, array<string, mixed>>
 */
function definesInputFixture(string $name): array
{
    /** @var array<string, array<string, mixed>> $decoded */
    $decoded = json_decode((string) file_get_contents(__DIR__.'/../../Support/Fixtures/'.$name), true, 512, JSON_THROW_ON_ERROR);

    return $decoded;
}

/**
 * The fields of one section of a grouped result.
 *
 * @param  array<int, array<string, mixed>>  $sections
 * @return array<string, array<string, mixed>>
 */
function definesInputFields(array $sections, int $index = 0): array
{
    expect($sections[$index]['fields'] ?? null)->toBeArray();

    /** @var array<string, array<string, mixed>> $fields */
    $fields = $sections[$index]['fields'];

    return $fields;
}

it('builds the HTML attribute config fields as the snapshot records them', function (): void {
    expect((new DefinesInputConfigFieldsHarness)->htmlAttributeConfigFields([]))
        ->toBe(definesInputFixture('input-html-attribute-config-fields.json'));
});

it('builds the input mask config fields as the snapshot records them', function (): void {
    expect((new DefinesInputConfigFieldsHarness)->inputMaskConfigFields([]))
        ->toBe(definesInputFixture('input-mask-config-fields.json'));
});

it('takes every attribute a builder leaves open from the override, and only the fixed ones from its own code', function (string $builder, string $fixture, array $fixed): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $defaults = definesInputFixture($fixture);

    $overrides = [];
    foreach ($defaults as $key => $field) {
        foreach (array_keys($field) as $attribute) {
            if (! in_array($attribute, ['if', 'section'], true)) {
                $overrides[$key][$attribute] = "override:{$key}:{$attribute}";
            }
        }
    }

    $built = $builder === 'htmlAttributeConfigFields'
        ? $harness->htmlAttributeConfigFields($overrides)
        : $harness->inputMaskConfigFields($overrides);

    $notOverridden = [];
    foreach ($overrides as $key => $attributes) {
        foreach ($attributes as $attribute => $sentinel) {
            if (($built[$key][$attribute] ?? null) !== $sentinel) {
                $notOverridden[] = "{$key}.{$attribute}";
            }
        }
    }

    expect($notOverridden)->toBe($fixed);
})->with([
    'html attributes' => ['htmlAttributeConfigFields', 'input-html-attribute-config-fields.json', [
        'autocapitalize.internal',
        'autocomplete_query.options',
        'autocomplete_query.internal',
        'autocomplete.options',
        'autocorrect.internal',
        'inputmode.internal',
        'minlength.internal',
        'max.internal',
        'maxlength.internal',
        'pattern.internal',
        'spellcheck.options',
        'calculation_query.display',
        'calculation_query.instructions',
        'calculation_query.type',
        'calculation_type.display',
        'calculation_type.instructions',
        'calculation_type.type',
        'calculation_type.options',
        'calculation_type.default',
        'calculation_formula.display',
        'calculation_formula.type',
        'calculation_formula.mode',
        'calculation_formula.mode_selectable',
        'calculation_formula.visibility',
        'calculation_formula_math.display',
        'calculation_formula_math.instructions',
        'calculation_formula_math.type',
        'calculation_formula_math.mode',
        'calculation_formula_math.mode_selectable',
        'calculation_formula_text.display',
        'calculation_formula_text.instructions',
        'calculation_formula_text.type',
        'calculation_formula_text.mode',
        'calculation_formula_text.mode_selectable',
        'calculation_decimals.display',
        'calculation_decimals.instructions',
        'calculation_decimals.type',
    ]],
    'input mask' => ['inputMaskConfigFields', 'input-mask-config-fields.json', []],
]);

it('builds the Statamic prepend, append and antlers fields with their translated labels', function (): void {
    expect((new DefinesInputConfigFieldsHarness)->statamicConfigFields())->toBe([
        'prepend' => [
            'display' => __('Prepend'),
            'instructions' => __('statamic::fieldtypes.text.config.prepend'),
            'type' => 'text',
            'section' => 'appearance',
        ],
        'append' => [
            'display' => __('Append'),
            'instructions' => __('statamic::fieldtypes.text.config.append'),
            'type' => 'text',
            'section' => 'appearance',
        ],
        'antlers' => [
            'display' => __('Allow Antlers'),
            'instructions' => __('statamic::fieldtypes.any.config.antlers'),
            'type' => 'toggle',
            'section' => 'antlers',
        ],
    ]);
});

it('defaults the input type to what the fieldtype reports, and lets an override replace it', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->inputType = 'email';

    expect($harness->htmlAttributeConfigFields([])['input_type']['default'])->toBe('email')
        ->and($harness->htmlAttributeConfigFields(['input_type' => ['default' => 'tel']])['input_type']['default'])->toBe('tel');
});

it('offers no overrides, unset or hidden fields, additional fields or presets by default', function (): void {
    $bare = new DefinesInputConfigFieldsDefaultsHarness;

    expect($bare->getConfigFieldOverrides())->toBe([])
        ->and($bare->getUnsetConfigFields())->toBe([])
        ->and($bare->getHiddenConfigFields())->toBe([])
        ->and($bare->getAdditionalConfigFields())->toBe([])
        ->and($bare->getInputType())->toBe('text');
});

it('applies the overrides to the fields that exist and ignores the ones that do not', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $fields = [
        'min' => ['display' => 'Min', 'type' => 'text'],
        'max' => ['display' => 'Max', 'type' => 'text'],
    ];

    $out = $harness->applyConfigFieldFilters($fields, [
        'min' => ['display' => 'Lowest', 'extra' => 1],
        'nothing' => ['display' => 'Ghost'],
    ]);

    expect($out)->toBe([
        'min' => ['display' => 'Lowest', 'type' => 'text', 'extra' => 1],
        'max' => ['display' => 'Max', 'type' => 'text'],
    ]);
});

it('removes the unset fields and turns the hidden ones into bare hidden fields', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->unset = ['gone'];
    $harness->hidden = ['quiet', 'missing'];

    $out = $harness->applyConfigFieldFilters([
        'gone' => ['type' => 'text'],
        'quiet' => ['type' => 'select', 'options' => ['a' => 'A'], 'validate' => 'required', 'visibility' => 'visible', 'display' => 'Quiet'],
        'kept' => ['type' => 'select', 'options' => ['a' => 'A'], 'validate' => 'required', 'visibility' => 'visible'],
    ], []);

    expect($out)->toBe([
        'quiet' => ['type' => 'hidden', 'display' => 'Quiet'],
        'kept' => ['type' => 'select', 'options' => ['a' => 'A'], 'validate' => 'required', 'visibility' => 'visible'],
    ]);
});

it('applies an override before it unsets or hides, so the filter has the last word', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->hidden = ['min'];

    $out = $harness->applyConfigFieldFilters(['min' => ['type' => 'text']], ['min' => ['type' => 'select', 'options' => ['a' => 'A']]]);

    expect($out)->toBe(['min' => ['type' => 'hidden']]);
});

it('groups fields by section in the fixed order and leaves empty sections out', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;

    $out = $harness->groupConfigFieldsIntoSections([
        'a' => ['type' => 'text', 'section' => 'antlers'],
        'b' => ['type' => 'text', 'section' => 'form'],
        'c' => ['type' => 'hidden', 'section' => 'form'],
        'd' => ['type' => 'text'],
        'e' => ['type' => 'text', 'section' => 'no_such_section'],
        'f' => ['type' => 'text', 'section' => 'hidden'],
    ]);

    expect(array_column($out, 'display'))->toBe(['Input Behavior', 'Form Association and Submission', 'Antlers'])
        ->and(array_keys(definesInputFields($out)))->toBe(['d', 'e', 'f'])
        ->and(array_keys(definesInputFields($out, 1)))->toBe(['b'])
        ->and(array_keys(definesInputFields($out, 2)))->toBe(['a'])
        ->and($out[3])->toBe(['fields' => ['c' => ['type' => 'hidden', 'section' => 'form']]]);
});

it('puts a field whose section is not a string in the behavior section', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;

    // A loose match would find `true` among the section names and file the field under key 1.
    $out = $harness->groupConfigFieldsIntoSections([
        'a' => ['type' => 'text', 'section' => true],
    ]);

    expect(array_column($out, 'display'))->toBe(['Input Behavior'])
        ->and(array_keys(definesInputFields($out)))->toBe(['a']);
});

it('describes each section with its display and instructions', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $sections = [
        'behavior', 'identification', 'value_and_state', 'form', 'input_mask', 'constraints',
        'suggestions', 'accessibility', 'appearance', 'microdata', 'advanced', 'experimental', 'antlers',
    ];

    $fields = [];
    foreach ($sections as $section) {
        $fields[$section.'_field'] = ['type' => 'text', 'section' => $section];
    }

    $out = $harness->groupConfigFieldsIntoSections($fields);
    $described = [];
    foreach ($out as $section) {
        $described[] = [$section['display'], $section['instructions'] ?? null];
    }

    expect($described)->toBe([
        ['Input Behavior', 'Settings that control user interactions, editing, and dynamic responses.'],
        ['Input Identification', 'Options to uniquely identify and classify the input for scripting or styling.'],
        ['Input Value and State', 'Configurations for initial values, types, and default states of the input.'],
        ['Form Association and Submission', 'Links the input to forms and customizes data submission behaviors.'],
        ['Input Mask', null],
        ['Constraints', 'Rules to enforce data format, range, and length requirements.'],
        ['Autocomplete and Suggestions', 'Features for providing or managing input suggestions and autocompletion.'],
        ['Accessibility', 'Enhancements for better usability across devices, languages, and users.'],
        ['Appearance', 'Visual properties like dimensions, visibility, and editable appearance.'],
        ['Microdata', 'Markup for embedding structured data and semantic information.'],
        ['Advanced Features', 'Advanced options for extended functionality like Web Components.'],
        ['Experimental and Limited Availability', 'Features with partial browser support or restrictions.'],
        ['Antlers', 'Features for using Antlers templates within the input.'],
    ]);
});

/**
 * @param  array<string, mixed>  $first
 */
it('returns the sections unchanged when the first one has no fields to inject into', function (array $first): void {
    /** @var array<string, mixed> $first */
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->additional = ['extra' => ['type' => 'text']];

    $sections = [$first];

    expect($harness->injectAdditionalConfigFields($sections))->toBe($sections);
})->with([
    'no fields key' => [['display' => 'A']],
    'fields that are not a list' => [['display' => 'A', 'fields' => 'nope']],
]);

it('returns an empty list of sections unchanged', function (): void {
    expect((new DefinesInputConfigFieldsHarness)->injectAdditionalConfigFields([]))->toBe([]);
});

it('puts the preset selector first, then the additional fields, then the rest of the first section', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->additional = ['extra' => ['type' => 'text'], 'more' => ['type' => 'toggle']];

    $out = $harness->injectAdditionalConfigFields([
        ['display' => 'A', 'fields' => ['min' => ['type' => 'text'], 'preset' => ['type' => 'select'], 'max' => ['type' => 'text']]],
        ['display' => 'B', 'fields' => ['other' => ['type' => 'text']]],
    ]);

    expect(array_keys(definesInputFields($out)))->toBe(['preset', 'extra', 'more', 'min', 'max'])
        ->and($out[0]['display'])->toBe('A')
        ->and(array_keys(definesInputFields($out, 1)))->toBe(['other']);
});

it('puts the additional fields first when there is no preset selector', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->additional = ['extra' => ['type' => 'text']];

    $out = $harness->injectAdditionalConfigFields([['fields' => ['min' => ['type' => 'text']]]]);

    expect(array_keys(definesInputFields($out)))->toBe(['extra', 'min']);
});

/**
 * @param  array<string, mixed>  $presets
 */
it('adds no preset selector when the fieldtype defines no preset options', function (array $presets): void {
    /** @var array<string, mixed> $presets */
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = $presets;

    $sections = [['fields' => ['min' => ['type' => 'text']]]];

    expect($harness->applyPresetToConfigFields($sections))->toBe($sections);
})->with([
    'no presets at all' => [[]],
    'options that are empty' => [['options' => []]],
    'options that are not a list' => [['options' => 'nope']],
]);

it('builds the preset selector from the options, labelling a preset by its label or its capitalized key', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = [
        'default' => 'phone',
        'options' => ['phone' => ['label' => 'Phone number'], 'zip' => []],
    ];

    $out = $harness->applyPresetToConfigFields([['display' => 'A', 'fields' => ['min' => ['type' => 'text']]]]);

    expect(array_keys(definesInputFields($out)))->toBe(['preset', 'min'])
        ->and(definesInputFields($out)['preset'])->toBe([
            'display' => 'Preset',
            'instructions' => 'Choose a preset for this field.',
            'type' => 'select',
            'options' => ['phone' => 'Phone number', 'zip' => 'Zip'],
            'default' => 'phone',
            'required' => true,
            'internal' => true,
        ]);
});

it('labels an unlabelled preset with an integer key by that key as a string', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = ['options' => [7 => []]];

    $out = $harness->applyPresetToConfigFields([['display' => 'A', 'fields' => ['min' => ['type' => 'text']]]]);

    expect(definesInputFields($out)['preset']['options'])->toBe([7 => '7']);
});

it('takes the selector display and instructions from the presets when they give them', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = ['display' => 'Kind', 'instructions' => 'Pick one.', 'options' => ['a' => ['label' => 'A']]];

    $out = $harness->applyPresetToConfigFields([['fields' => []]]);

    expect(definesInputFields($out)['preset']['display'])->toBe('Kind')
        ->and(definesInputFields($out)['preset']['instructions'])->toBe('Pick one.')
        ->and(definesInputFields($out)['preset']['default'])->toBe('');
});

/**
 * @param  array<string, mixed>  $first
 */
it('adds no selector when the first section has no fields array', function (array $first): void {
    /** @var array<string, mixed> $first */
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = ['options' => ['a' => ['label' => 'A']]];

    expect($harness->applyPresetToConfigFields([$first]))->toBe([$first]);
})->with([
    'a section with no fields key' => [['display' => 'A']],
    'a section whose fields are not an array' => [['fields' => 'nope']],
]);

it('sets the default of every field the selected preset configures and hides it', function (string $selected): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = [
        'default' => 'phone',
        'options' => [
            'phone' => ['label' => 'Phone', 'config' => ['min' => 7, 'ghost' => 1]],
            'zip' => ['label' => 'Zip', 'config' => ['max' => 5]],
        ],
    ];
    $harness->useConfig($selected === '' ? [] : ['preset' => $selected]);

    $out = $harness->applyPresetToConfigFields([
        ['fields' => ['min' => ['type' => 'text'], 'note' => 'not an array']],
        ['fields' => ['max' => ['type' => 'text'], 'min' => ['type' => 'text', 'default' => 1]]],
    ]);

    $expected = match ($selected) {
        '' => [
            'min' => [['type' => 'text', 'default' => 7, 'visibility' => 'hidden'], ['type' => 'text', 'default' => 7, 'visibility' => 'hidden']],
            'max' => [null, ['type' => 'text']],
        ],
        default => [
            'min' => [['type' => 'text'], ['type' => 'text', 'default' => 1]],
            'max' => [null, ['type' => 'text', 'default' => 5, 'visibility' => 'hidden']],
        ],
    };

    expect(definesInputFields($out)['min'] ?? null)->toBe($expected['min'][0])
        ->and(definesInputFields($out, 1)['min'] ?? null)->toBe($expected['min'][1])
        ->and(definesInputFields($out)['max'] ?? null)->toBe($expected['max'][0])
        ->and(definesInputFields($out, 1)['max'] ?? null)->toBe($expected['max'][1]);
})->with([
    'the default preset' => [''],
    'the configured preset' => ['zip'],
]);

it('applies no preset configuration when the selected preset is not an option', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = ['options' => ['a' => ['label' => 'A', 'config' => ['min' => 1]]]];
    $harness->useConfig(['preset' => 'zzz']);

    $out = $harness->applyPresetToConfigFields([['fields' => ['min' => ['type' => 'text']]]]);

    expect(definesInputFields($out)['min'])->toBe(['type' => 'text']);
});

it('applies no configuration from a preset whose config is not an array', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = ['default' => 'a', 'options' => ['a' => ['label' => 'A', 'config' => 'nope']]];

    $out = $harness->applyPresetToConfigFields([['fields' => ['min' => ['type' => 'text']]]]);

    expect(definesInputFields($out)['min'])->toBe(['type' => 'text']);
});

it('ignores a selected preset that is not a scalar', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = ['options' => ['a' => ['label' => 'A', 'config' => ['min' => 1]]]];
    $harness->useConfig(['preset' => ['a']]);

    $out = $harness->applyPresetToConfigFields([['fields' => ['min' => ['type' => 'text']]]]);

    expect(definesInputFields($out)['min'])->toBe(['type' => 'text']);
});

it('reads a selected preset of false as the empty key, not as the first option', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = ['options' => [['label' => 'First', 'config' => ['min' => 1]]]];
    $harness->useConfig(['preset' => false]);

    $out = $harness->applyPresetToConfigFields([['fields' => ['min' => ['type' => 'text']]]]);

    expect(definesInputFields($out)['min'])->toBe(['type' => 'text']);
});

it('reads a selected preset that is not a scalar as the empty key', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = ['options' => ['' => ['label' => 'None', 'config' => ['min' => 1]]]];
    $harness->useConfig(['preset' => ['a']]);

    $out = $harness->applyPresetToConfigFields([['fields' => ['min' => ['type' => 'text']]]]);

    expect(definesInputFields($out)['min'])->toBe(['type' => 'text', 'default' => 1, 'visibility' => 'hidden']);
});

it('reads a numeric selected preset as its string key', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->presets = ['options' => [5 => ['label' => 'Five', 'config' => ['min' => 1]]]];
    $harness->useConfig(['preset' => 5]);

    $out = $harness->applyPresetToConfigFields([['fields' => ['min' => ['type' => 'text']]]]);

    expect(definesInputFields($out)['min'])->toBe(['type' => 'text', 'default' => 1, 'visibility' => 'hidden']);
});

it('assembles the Control Panel sections from the builders, the filters, the preset and the additional fields', function (): void {
    $harness = new DefinesInputConfigFieldsHarness;
    $harness->overrides = ['min' => ['display' => 'Lowest']];
    $harness->unset = ['max'];
    $harness->hidden = ['step'];
    $harness->additional = ['extra' => ['type' => 'text']];
    $harness->presets = ['options' => ['a' => ['label' => 'A', 'config' => ['min' => 3]]], 'default' => 'a'];

    $sections = $harness->configFieldItems();
    $byKey = [];
    foreach ($sections as $index => $section) {
        $byKey = [...$byKey, ...definesInputFields($sections, $index)];
    }

    expect(array_keys(definesInputFields($sections))[0])->toBe('preset')
        ->and(array_keys(definesInputFields($sections))[1])->toBe('extra')
        ->and($byKey['min']['display'])->toBe('Lowest')
        ->and($byKey['min']['default'])->toBe(3)
        ->and($byKey['min']['visibility'])->toBe('hidden')
        ->and($byKey)->not->toHaveKey('max')
        ->and($byKey['step']['type'])->toBe('hidden')
        ->and($byKey)->toHaveKeys(['prepend', 'append', 'antlers', 'transform_case', 'input_mask']);
});
