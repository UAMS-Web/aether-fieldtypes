<?php

declare(strict_types=1);

use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Subsection;

covers(Subsection::class);

/**
 * The `Subsection` fieldtype, a heading inside a Panel Card that stores no value and replaces
 * Statamic's deprecated Section (moved from the UAMS site, UAMS-Web/uams-statamic#312).
 *
 * @command  vendor/bin/pest tests/Integration/Fieldtypes/SubsectionTest.php
 */
it('presents as a special subsection field with the section icon', function (): void {
    // Build the fieldtype
    $fieldtype = new Subsection;

    // Expect its title, component, icon, category and search keywords
    expect(Subsection::title())->toBe('Subsection')
        ->and($fieldtype->component())->toBe('subsection')
        ->and($fieldtype->icon())->toBe('fieldtype-section')
        ->and($fieldtype->categories())->toBe(['special'])
        ->and($fieldtype->keywords())->toBe([
            'divider',
            'header',
            'heading',
            'section',
            'sub-section',
            'subsection',
            'subtitle',
            'title',
        ]);
});

it('holds no value, so it is neither localizable, validatable nor defaultable', function (): void {
    // Build the fieldtype
    $fieldtype = new Subsection;

    // Expect all three capabilities off
    expect($fieldtype->localizable())->toBeFalse()
        ->and($fieldtype->validatable())->toBeFalse()
        ->and($fieldtype->defaultable())->toBeFalse();
});

it('registers under the subsection handle once the add-on boots', function (): void {
    // Expect the host's fieldtype registry to resolve the handle to this class, without a stand-in
    expect(Subsection::handle())->toBe('subsection')
        ->and(app('statamic.fieldtypes')->get('subsection'))->toBe(Subsection::class);
});
