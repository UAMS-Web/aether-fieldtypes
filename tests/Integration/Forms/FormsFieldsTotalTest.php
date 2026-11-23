<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Statamic\Facades\Antlers;
use Statamic\Fields\Field;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Total;

/**
 * The public-form template for the Total fieldtype (UAMS-Web/uams-statamic-archive#488),
 * resources/views/forms/fields/total.antlers.html. It renders a
 * read-only running order total: the display + hidden input the
 * total-pricing-engine.js writes into, plus the `[data-total-*]` hooks the engine
 * keys off. The render context is built from the real
 * Total::extraRenderableFieldData(). Rendering a template against a hand-built
 * scope has no mutable content surface.
 *
 * @command vendor/bin/pest tests/Integration/Views/FormsFieldsTotalTest.php
 */

/**
 * The front-end render context for a total field.
 *
 * @param  array<string, mixed>  $config
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function totalContext(array $config = [], mixed $value = '', array $overrides = []): array
{
    $fieldtype = new Total;
    $fieldtype->setField(new Field('order_total', array_merge(['type' => 'total'], $config)));

    return array_merge([
        'handle' => 'order_total',
        'id' => 'contact-form-order_total-field',
        'value' => $value,
    ], $fieldtype->extraRenderableFieldData(), $overrides);
}

/**
 * Render the total form template against a real-fieldtype context.
 *
 * @param  array<string, mixed>  $config
 * @param  array<string, mixed>  $overrides
 */
function renderTotal(array $config = [], mixed $value = '', array $overrides = []): string
{
    return (string) Antlers::parse(
        File::get(__DIR__.'/../../../resources/views/forms/fields/total.antlers.html'),
        totalContext($config, $value, $overrides),
        true,
    );
}

it('renders the total wrapper, display, and hidden input with the engine hooks', function (): void {
    $out = renderTotal();

    expect($out)->toContain('data-total-field');
    expect($out)->toContain('data-total-handle="order_total"');
    expect($out)->toContain('data-total-display-text');
    expect($out)->toContain('data-total-input');
    // The hidden input submits under the field handle so the total round-trips.
    expect($out)->toContain('type="hidden"');
    expect($out)->toContain('name="order_total"');
});

it('renders the configured amount prefix', function (): void {
    $out = renderTotal(['prefix' => 'Order Total']);

    expect($out)->toContain('total-prefix');
    expect($out)->toContain('Order Total');
});

it('renders a repopulated value into both the display and the hidden input', function (): void {
    $out = renderTotal([], '125.50');

    // The value appears in the visible display text and the hidden input value.
    expect(substr_count($out, '125.50'))->toBeGreaterThanOrEqual(2);
});

it('escapes an author-controlled prefix', function (): void {
    $out = renderTotal(['prefix' => '<b>x</b>']);

    expect($out)->not->toContain('<b>x</b>');
    expect($out)->toContain('&lt;b&gt;');
});
