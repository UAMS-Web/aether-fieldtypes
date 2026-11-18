<?php

declare(strict_types=1);

use Statamic\Fields\Field;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\InputFieldtypeHarness;

beforeEach(function (): void {
    InputFieldtypeHarness::register();
});

/**
 * Characterization tests for the section grouping produced by
 * `UAMSWeb\Aether\Fieldtypes\Traits\DefinesInputConfigFields::groupConfigFieldsIntoSections()`,
 * extracted from the former monolithic `Input` trait (issue UAMS-Web/uams-statamic-archive#873).
 *
 * These lock in the observable contract the Control Panel relies on — section
 * order, per-section metadata, and the type-driven hidden bucket — so the
 * decomposition (and any future change to the grouping) stays behavior-preserving.
 *
 * Mutation: 100% on UAMSWeb\Aether\Fieldtypes\Traits\DefinesInputConfigFields (1741 mutants, --covered-only).
 *
 * @command  vendor/bin/pest tests/Integration/Traits/InputConfigFieldSectionsTest.php
 */

/**
 * Resolve the `InputFieldtypeHarness` fieldtype (a representative `Input` consumer) bound to a
 * minimal blueprint field.
 */
function inputConfigTextFieldtype(): InputFieldtypeHarness
{
    // Build a blueprint field selecting the text fieldtype
    $field = new Field(
        'sample_text',
        [
            'type' => 'input_harness',
        ]
    );

    $fieldtype = $field->fieldtype();

    expect($fieldtype)->toBeInstanceOf(InputFieldtypeHarness::class);

    /** @var InputFieldtypeHarness $fieldtype */
    $fieldtype->setField($field);

    return $fieldtype;
}

it('renders sections in the fixed display order', function (): void {
    $sections = inputConfigTextFieldtype()->configFieldItems();

    // Collect the display label of each section (the trailing hidden section
    // has no display key and is represented as null)
    $displays = array_map(
        static function (array $section): ?string {
            $display = $section['display'] ?? null;

            return is_string($display) ? $display : null;
        },
        $sections
    );

    // A consumer that excludes no fields gets every section, in the trait's fixed order. The
    // site's `Text` fieldtype excludes the identification, form association, microdata and
    // advanced sections, so its own list is shorter.
    expect($displays)->toBe([
        'Input Behavior',
        'Input Identification',
        'Input Value and State',
        'Form Association and Submission',
        'Input Mask',
        'Constraints',
        'Autocomplete and Suggestions',
        'Accessibility',
        'Appearance',
        'Microdata',
        'Advanced Features',
        'Experimental and Limited Availability',
        'Antlers',
        null,
    ]);
});

it('emits only sections that carry fields', function (): void {
    foreach (inputConfigTextFieldtype()->configFieldItems() as $section) {
        expect($section)->toHaveKey('fields');
        expect($section['fields'])->not->toBeEmpty();
    }
});

it('omits optional metadata from the input mask and hidden sections', function (): void {
    $sections = inputConfigTextFieldtype()->configFieldItems();

    // Locate the input mask section by its display label
    $inputMask = collect($sections)->firstWhere('display', 'Input Mask');

    expect($inputMask)->not->toBeNull();
    expect($inputMask)->not->toHaveKey('instructions');

    // The final section is the type-driven hidden bucket
    $hidden = end($sections);

    expect($hidden)->not->toHaveKey('display');
    expect($hidden)->not->toHaveKey('instructions');
});

it('isolates hidden-typed fields in the hidden section', function (): void {
    $sections = inputConfigTextFieldtype()->configFieldItems();

    // The trailing section is the hidden bucket — every field must be hidden.
    // Assert the structure explicitly (rather than silently coercing a missing
    // 'fields' key to an empty list) so a structural regression in the SUT fails
    // the test instead of vacuously passing.
    $hidden = end($sections);

    expect($hidden)->toBeArray();
    /** @var array<string, mixed> $hidden */
    expect($hidden)->toHaveKey('fields');

    $hiddenFields = $hidden['fields'];
    expect($hiddenFields)->toBeArray();

    /** @var array<int|string, array<string, mixed>> $hiddenFields */
    foreach ($hiddenFields as $field) {
        expect($field['type'] ?? null)->toBe('hidden');
    }

    // No other (labeled) section may contain a hidden-typed field
    foreach ($sections as $section) {
        if (! isset($section['display'])) {
            continue;
        }

        /** @var array<int, array<string, mixed>> $sectionFields */
        $sectionFields = $section['fields'];

        foreach ($sectionFields as $field) {
            expect($field['type'] ?? null)->not->toBe('hidden');
        }
    }
});
