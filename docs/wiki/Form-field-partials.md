Written against v0.3.0.

The add-on ships 30 Antlers partials under `resources/views/forms/fields/`, one for each form field type it renders on the front end, plus a shared width helper. This page lists what each partial renders, the variables it reads, the markup and data attributes it emits, and how a host application overrides one. The Alpine components and engines the partials depend on are described in [Alpine-helper.md](Alpine-helper.md).

- [How the partials are loaded](#how-the-partials-are-loaded)
- [Overriding a partial](#overriding-a-partial)
- [Variables shared by most partials](#variables-shared-by-most-partials)
- [Single-input partials](#single-input-partials)
- [Partials that delegate to default](#partials-that-delegate-to-default)
- [Choice partials](#choice-partials)
- [Composite partials](#composite-partials)
- [Pricing partials](#pricing-partials)
- [Interactive partials](#interactive-partials)
- [Display-only partials](#display-only-partials)
- [The width helper](#the-width-helper)

## How the partials are loaded

`UAMSWeb\Aether\Fieldtypes\ServiceProvider::bootAddon()` calls `loadViewsFrom(__DIR__.'/../resources/views', 'statamic')`, so the add-on's `resources/views` directory is registered under the `statamic` view namespace. A form field is therefore resolved as `statamic::forms.fields.<handle>`, where `<handle>` is the file name without `.antlers.html` (`text`, `checkboxes`, `product`, and so on). The partials that include another partial do so with the same namespace, for example `{{ partial:statamic::forms/fields/default }}`.

The provider's own comment states the lookup order: the host's `vendor/statamic` override directory is searched before the add-on's directory, so a host partial of the same name still wins. That ordering is what the code comment claims; the add-on does not alter Statamic's lookup beyond registering its directory.

## Overriding a partial

To replace a partial, create a file of the same name in the host application. The default.antlers.html partial documents the location in its closing comment: `resources/views/vendor/statamic/forms/fields/<handle>.antlers.html`. For example, `resources/views/vendor/statamic/forms/fields/text.antlers.html` replaces the add-on's `text` partial. An override only needs the markup for that one handle; every partial that the host does not override keeps using the add-on's copy.

The `default` partial ends with an HTML comment naming the field type and handle. Its own comment explains it as a marker for a field type that reached `default` without a per-type template, which a host can fill by adding that file under `vendor/statamic` as above.

## Variables shared by most partials

Statamic supplies these variables to a field's view; the partials read them as listed. The partial-specific variables are in the tables further down.

| Variable | Read for |
| --- | --- |
| `id` | the element `id` and the prefix of `aria-describedby` targets (`<id>-error`, `<id>-instructions`) |
| `handle` | the `name` attribute |
| `value` | the current value, passed through the `sanitize` modifier before output in most inputs |
| `error` | adds the `is-invalid` class and `aria-invalid="true"` with `aria-describedby="<id>-error"` |
| `instructions` | when there is no error, `aria-describedby="<id>-instructions"` |
| `validate` | when it contains `required`, the `required` attribute is emitted |
| `visibility` | `read_only` renders `readonly` with `aria-readonly="true"` on text-like inputs, or `disabled` on selects, checkboxes, radios and files |
| `js_driver`, `js_attributes` | when `js_driver` is set, `js_attributes` is output as-is inside the element (the conditional-fields hook) |
| `calculation_query`, `calculation_type`, `calculation_formula`, `calculation_decimals` | the calculated-field attributes described below |

Classes follow Bootstrap 4 naming: `form-control`, `is-invalid`, `form-check`, `form-check-input`, `form-check-label`, `form-control-file`, `custom-control`, `custom-switch`, `form-group`, `input-group`.

### Calculated fields

Where a partial supports calculation, `calculation_query == true` adds `data-calculation-type`, `data-calculation-formula` and `data-calculation-decimals` to the input. Single-input text partials (`default`, `text`, `integer`) also add `readonly` and `aria-readonly="true"`. The calculation engine (`registerCalculationEngine`) selects elements by `[data-calculation-type]`.

- In `default`, `text` and `integer`, the formula is `calculation_formula ?? calculation_formula_math ?? calculation_formula_text ?? ''` and the type defaults to `math`. `calculation_decimals` is passed through unchanged; the partial comments say the engine applies the 2-decimal default when the value is empty or missing.
- In `checkboxes`, `radio` and `select`, the attributes are fixed at `data-calculation-type="text"`, `data-calculation-formula="{{ calculation_formula ?? '' }}"` and an empty `data-calculation-decimals=""`; those three partials do not add `readonly`.

## Single-input partials

| Partial | Renders | Variables read besides the shared set | Emitted markup |
| --- | --- | --- | --- |
| `default` | a generic `<input>` | `input_type` (default `text`), `placeholder`, `character_limit`, `autocomplete`, `autocapitalize`, `autocorrect`, `inputmode`, `min`, `max`, `minlength`, `maxlength`, `pattern`, `step`, `spellcheck`, `transform_case`, `type` | `class="form-control"` (plus `is-invalid`), `id`, `type`, `name`, `value`, those attributes when set, `data-transform-case` from `transform_case`, the calculation attributes, then a trailing HTML comment `<!-- form-field default render: type="..." handle="..." -->` |
| `text` | `<input>` for the text field type | `input_type` (default `text`), `placeholder`, `character_limit`, `maxlength`, `minlength`, `autocomplete`, `autocapitalize`, `autocorrect`, `inputmode` | same shape as `default` with the shorter attribute list (`placeholder`, `maxlength` from `character_limit` or else `maxlength`, `minlength`, `autocomplete`, `autocapitalize`, `autocorrect`, `inputmode`) and no trailing comment |
| `integer` | `<input type="number">` | `placeholder`, `character_limit`, `autocomplete`, `min`, `max`, `step` | `type="number"`, calculation attributes as in `text` |
| `hidden` | `<input type="hidden">` | `value`, `js_driver`, `js_attributes` | `id`, `name`, `value`; no class, no required, no error attributes |
| `files` | `<input type="file">` | `max_files` | `class="form-control-file"`; `name` gets a `[]` suffix and the `multiple` attribute is emitted when `max_files` is not `1`; `required` when the rule is present; `disabled` when read-only |
| `assets` | the same input as `files` | same as `files` | the file contains only `{{ partial:statamic::forms/fields/files }}` |

`default` adds `data-transform-case` by way of `{{ transform_case | attribute:data-transform-case }}`; the other partials do not read `transform_case`.

Accessibility in these partials: when `error` is set the input carries `aria-invalid="true"` and `aria-describedby="<id>-error"`; otherwise, when `instructions` is set, `aria-describedby="<id>-instructions"`. `hidden` carries neither. The `files` partial emits the `required` keyword directly rather than through the `attribute` modifier.

## Partials that delegate to default

These partials contain a single include of `default`. They render exactly what `default` renders, with the fixed overrides noted.

| Partial | Include | Effect |
| --- | --- | --- |
| `email` | `{{ partial:statamic::forms/fields/default }}` | no argument; the `input_type` comes from the field's own variables, falling back to `text` |
| `float` | `{{ partial:statamic::forms/fields/default }}` | same |
| `password` | `{{ partial:statamic::forms/fields/default }}` | same |
| `phone` | `{{ partial:statamic::forms/fields/default }}` | same |
| `url` | `{{ partial:statamic::forms/fields/default }}` | same |
| `username` | `{{ partial:statamic::forms/fields/default }}` | same |
| `time` | `{{ partial:statamic::forms/fields/default input_type="time" }}` | forces `type="time"` |

`date` also delegates to `default` for its single mode; see [Composite partials](#composite-partials).

## Choice partials

| Partial | Renders | Variables read | Emitted markup |
| --- | --- | --- | --- |
| `checkboxes` | a group of checkboxes | `options` (key to label map), `inline`, `full_choices`, `calculation_*`, `js_*` | a wrapper `<div>`; one hidden `<input name="<handle>[]">` first (so an empty group submits an empty array); per option a `form-check` div (`form-check-inline` when `inline`) with `<input type="checkbox" name="<handle>[]" value="<option>">` and a `<label>`; ids are `<id>-<slugified option>-option`; checked when `value` contains the option |
| `radio` | a group of radios | `options`, `inline`, `full_choices`, `calculation_*`, `js_*` | per option `<input type="radio" name="<handle>" value="<option>">`, checked when `value == option`; same id scheme and classes as `checkboxes` |
| `select` | a drop-down | `options`, `multiple`, `placeholder`, `full_choices`, `calculation_*`, `js_*` | `name` gets `[]` when `multiple`; when not multiple, a leading empty `<option>` showing `placeholder` or the translation `Please select...`; each `<option class="font-weight-light">` is `selected` per the value |
| `toggle` | a switch | `inline_label`, `js_*` | `custom-control custom-switch` wrapper; a hidden `<input name="<handle>" value="0">` followed by `<input type="checkbox" value="1">`, checked when `value` is truthy and not `'0'`; an optional `<label class="custom-control-label">` from `inline_label` |

Choice limits: in `checkboxes`, `radio` and `select`, an option listed in `full_choices` is rendered `disabled` and labelled with the translation `Full` (a `<span class="choice-full">(Full)</span>` in checkboxes and radios, plain `(Full)` text in selects). The server side of that feature is described in [Choice-submission-limits.md](Choice-submission-limits.md).

Accessibility: required, error and instructions handling is the same as in the single-input partials. In `checkboxes`, when the field has the `required` rule the wrapper `<div>` gets `x-data="checkboxRequiredGroup"` and `@change="sync"`, and each checkbox still carries `required` until one is checked; the Alpine component clears or restores `required` across the group. See [Alpine-helper.md](Alpine-helper.md#checkboxrequiredgroup).

`select` reads `$is_selected` with `in_array(option)` for multiple selects and `option == value` otherwise.

## Composite partials

Composite partials render several named subfields under one handle. Subfields submit as `<handle>[<subfield>]`. Each subfield column is wrapped by the width helper (see [The width helper](#the-width-helper)).

### address

Renders a structured address. The partial sets `preset` to `american` when unset; `canadian` and `international` change the state control and the optional country select.

| Subfield | Name | Input | Default width | Included by default | Autocomplete |
| --- | --- | --- | --- | --- | --- |
| Street address | `<handle>[street_address]` | text | 100 | yes | `street-address` |
| Address line 2 | `<handle>[address_line_2]` | text | 100 | yes | `address-line2` |
| City | `<handle>[city]` | text | 50 | yes | `address-level2` |
| State, province or region | `<handle>[state_province_region]` | `<select>` for `american` and `canadian`, text input for `international` | 50 (`state_province_region_american_width`) | always | `address-level1` |
| Postal code | `<handle>[postal_code]` | text | 50 | yes | `postal-code` |
| Country | `<handle>[country]` | `<select>` | 50 | only when `preset == 'international'` and `country_include` is not false | `country` |

Per subfield, the partial reads `<subfield>_include`, `<subfield>_label`, `<subfield>_width`, `<subfield>_default`, `<subfield>_placeholder` (street, line 2 and postal code only) and `<subfield>_required`. The required defaults are true for street address, city, state, postal code and country, and false for line 2; a subfield is required only when the field also has the `required` rule. The state label variable depends on the preset: `state_province_region_american_label`, `state_province_region_canadian_label` or `state_province_region_international_label`. The state options come from `state_options_american` or `state_options_canadian`, and the country options from `country_options` (code to name maps supplied by the fieldtype). Selects start with an empty option showing the translation `Please select...` and are `disabled` when read-only; text inputs are `readonly` with `aria-readonly="true"`. Labels are `<label for="<id>-<subfield>" class="small text-muted mb-1">`. The `is-invalid` class is emitted on street address and city only.

### name

Renders a person name. Subfields and defaults:

| Subfield | Name | Included by default | Default width | Autocomplete | Required by default |
| --- | --- | --- | --- | --- | --- |
| Prefix | `<handle>[prefix]` | no (`prefix_include`) | 50 | `honorific-prefix` | no |
| First name | `<handle>[first_name]` | yes | 50 | `given-name` | yes |
| Middle name | `<handle>[middle_name]` | no | 50 | `additional-name` | no |
| Last name | `<handle>[last_name]` | yes | 50 | `family-name` | yes |
| Suffix | `<handle>[suffix]` | no | 50 | `honorific-suffix` | no |

Each reads `<subfield>_include`, `_label`, `_width`, `_default`, `_placeholder` and `_required`. A subfield is required only when the field also has the `required` rule. The `is-invalid` class is emitted on first and last name only.

### partial_date

Renders year, month and day controls according to `included_parts` (default `year_month_day`).

| `included_parts` | Controls shown |
| --- | --- |
| `year_only` | year |
| `year_month` | year, month |
| `year_month_day` | year, month, day |
| `month_only` | month |
| `month_day` | month, day |

The year is `<input type="number" inputmode="numeric">` named `<handle>[year]` (reads `year_min`, `year_max`, `year_default`), the month is a `<select>` named `<handle>[month]` whose options come from `month_options` (reads `month_default`), and the day is `<input type="number" min="1" max="31">` named `<handle>[day]` (reads `day_default`). `subfields_layout` defaults to `inline`: controls sit in a `row` with `col-md-4` columns; any other value stacks them in a `d-flex flex-column`. Labels use the translations `Year`, `Month` and `Day`. Every control is required when the field has the `required` rule.

### date

Reads `mode`. In `range` mode it renders two `<input type="date">` controls named `<handle>[start]` and `<handle>[end]` in `col-md-6` columns, labelled with the translations `Start` and `End`; both carry `min`, `max` and, when the field is required, `required`. In any other mode it includes `default` with `input_type="date"` and the effective `min` and `max`.

The effective bounds are `earliest_date_custom` when `earliest_date_type == 'custom'` and that value is set, otherwise `min`; and `latest_date_custom` when `latest_date_type == 'custom'` and that value is set, otherwise `max`.

### replicator

Renders repeatable sets. The outer element is `<div class="replicator-field" x-data="replicatorField()">` with `data-replicator-handle`, `data-replicator-min-sets` and `data-replicator-index-token` (from `replicator_min_sets` and `replicator_index_token`).

- Existing sets come from `replicator_rows`. Each is `<div class="replicator-field__set" data-replicator-set x-data="replicatorSet({{ set_values }})">` with a hidden `<handle>[<index>][type]`, an "Enabled" hidden input plus checkbox named `<handle>[<index>][enabled]`, the set's fields (each inside `<template x-if="{{ show_field }}">` so conditional visibility removes the field from the DOM), and a `data-replicator-remove` button.
- Each field shows its `display` label (with a `<sup aria-label="Required">*</sup>` when required), the rendered `field`, an `instructions` paragraph with `id="<id>-instructions"`, and an error paragraph with `id="<id>-error"` (existing sets only).
- A `data-replicator-add` area holds one `data-replicator-add-type="<type>"` button per `replicator_set_templates` entry.
- Each template is also emitted as `<template data-replicator-template="<type>">`, whose names use the `replicator_index_token` placeholder that the Alpine component replaces with a new index.

Behavior is in [Alpine-helper.md](Alpine-helper.md#replicatorfield).

### list

Renders a list of text rows. It builds `initial_items_json` from `value` (`to_json`, sanitized), or `[""]` when there is no value, then emits `x-data='listField(<json>, <max_items ?? 999>, <min_items ?? 1>, "<handle>")'`. Each row is an `input-group` with `<input type="text" :name="'<handle>[' + idx + ']'" x-model="items[idx]">` and, unless read-only, a Remove button (`@click="remove(idx)"`, `:disabled="! canRemove()"`, `:aria-label` of the translation `Remove` plus the row number). Unless read-only, an Add button (`@click="add()"`, `x-show="canAdd()"`) follows. Read-only rows are `readonly` with `aria-readonly="true"`. See [Alpine-helper.md](Alpine-helper.md#listfield).

## Pricing partials

These three partials cooperate through data attributes. The pricing engines (`registerProductPricingEngine`, `registerOptionPricingEngine`, `registerTotalPricingEngine`) find them by selector and keep the subtotals current. See [Alpine-helper.md](Alpine-helper.md#pricing-engines) for the event contract.

| Partial | Root attributes | Variables read | Controls |
| --- | --- | --- | --- |
| `product` | `data-product-field`, `data-product-handle`, `data-product-mode` (`selection_type`, default `single`) | `selection_type`, `product_options` (each with `key`, `price`, `label`, `formatted_price`), `price`, `formatted_price`, `disable_quantity` | `multiple` mode: `<select name="<handle>[key]" data-product-select>` with `data-price` on each option; `single` mode: a `<p data-product-single-price data-price="...">`. Quantity: `<input type="number" name="<handle>[quantity]" min="0" step="1" data-product-quantity>`, or a hidden quantity of `1` when the mode is `single` and `disable_quantity == true`. Hidden `<handle>[line_total]` with `data-product-line-total-input`, and a display paragraph with `data-product-line-total-display` and `data-product-line-total-text` |
| `option` | `data-option-field`, `data-option-handle`, `data-option-mode` (`input_type`, default `select`) | `input_type` (`select`, `radio` or anything else for checkboxes), `option_choices` (each with `key`, `price`, `label`, `formatted_price`) | `select` mode: `<select name="<handle>[key]" data-option-select>`; `radio`: radios named `<handle>[key]` with `data-option-input`; otherwise checkboxes named `<handle>[keys][]` with `data-option-input`. Each choice carries `data-price`. Hidden `<handle>[line_total]` with both `data-option-line-total-input` and `data-product-line-total-input`, and a display paragraph with `data-option-line-total-display` and `data-option-line-total-text` |
| `total` | `data-total-field`, `data-total-handle` | `prefix`, `value` | a paragraph with `data-total-display-text` (shows `value`, default `0.00`) and a hidden `<input name="<handle>" data-total-input>` |

The line-total hidden input in `option` carries the product hook on purpose, so a `total` field sums products and options in the same way. In the `product` and `option` partials, labels use `for="<id>-<key>"` (option) or `for="<id>-quantity"` (product), the quantity label uses the translation `Quantity`, and the subtotal text uses the translation `Subtotal`. Read-only quantity inputs are `readonly`; read-only selects, radios and checkboxes are `disabled`.

## Interactive partials

### signature

Renders `<div class="signature-field" x-data="signatureField()">` with two modes toggled by `x-show="mode === 'draw'"` and `x-show="mode === 'type'"`.

- Draw: a `<canvas x-ref="canvas" class="signature-field__canvas" role="img" tabindex="0" aria-label="Signature drawing area">` plus Clear (`@click="clear()"`) and "Type your name instead" (`@click="useTypeMode()"`) buttons.
- Type: a text input (`id="<id>-typed"`, `autocomplete="name"`, `x-model="typedName"`, `@input="syncTyped()"`), an attestation checkbox (`id="<id>-attest"`, `x-model="attested"`, `@change="syncTyped()"`, labelled "I attest that this typed name is my signature."), and a "Draw signature instead" button.
- A hidden `<input x-ref="input" id="<id>" name="<handle>" :value="value">` holds the submitted value, with `aria-invalid` and `aria-describedby="<id>-error"` when there is an error. The canvas gets `is-invalid` on error.

See [Alpine-helper.md](Alpine-helper.md#signaturefield) for the value format.

## Display-only partials

| Partial | Variables read | Markup |
| --- | --- | --- |
| `section` | `display`, `instructions` | `<div class="form-section my-4">` with an `<h3 class="h5 font-weight-bold mb-1">` heading and, when `instructions` is set, `<p class="text-muted small mb-0">` |
| `html` | `html` | `<div class="form-html my-3">` containing `{{ html }}` unmodified (no sanitize modifier) |

## The width helper

`_width.antlers.html` is not a field. It converts a percentage into Bootstrap grid classes. It is called as `{{ partial:statamic::forms/fields/_width :width="..." }}` and reads `width`.

| `width` | Output |
| --- | --- |
| `25` | `col-12 col-md-3` |
| `33` | `col-12 col-md-4` |
| `50` | `col-12 col-md-6` |
| `66` | `col-12 col-md-8` |
| `75` | `col-12 col-md-9` |
| anything else | `col-12` |

The comparison is against the integer values shown, so `100` yields `col-12`. `address` and `name` are the only partials that include it.
