Written against v0.3.0.

The add-on exposes a public JavaScript entry for front-end forms, `resources/js/alpine.js`. It is a set of registration functions that a host application calls to wire the form partials' Alpine components and engines into its own Alpine instance. This page covers what the entry exports, how a page loads it, each registered Alpine data component and each element engine, and which form partials use them. The partials are described in [Form-field-partials.md](Form-field-partials.md). The Control Panel Vue components are loaded separately through `resources/js/cp.js` and are not covered here.

- [How a page loads the helper](#how-a-page-loads-the-helper)
- [What the entry exports](#what-the-entry-exports)
- [Registered data components](#registered-data-components)
- [Element engines](#element-engines)
- [Which partial uses what](#which-partial-uses-what)

## How a page loads the helper

`resources/js/alpine.js` is a plain ES module that only re-exports. It registers nothing by itself, and the add-on does not attach anything to `window.Alpine`, define an Alpine magic or define an Alpine directive. A host imports the functions it needs and calls them before it calls `Alpine.start()`:

```js
import {
    registerAlpineEngine,
    registerCalculationEngine,
    registerListField,
} from '../../vendor/uams-web/aether-fieldtypes/resources/js/alpine.js'
```

(The import path is the one the file's own header comment shows.)

The built Control Panel bundle in `resources/dist` is not this helper. `vite.config.js` and the provider's `$vite` property list two inputs only, `resources/js/cp.js` and `resources/css/cp.css`, so the public Alpine entry is not part of the committed build. A host bundles `alpine.js` as part of its own front-end build, which is also how the modules it imports are resolved: `signature-field.js` loads `signature_pad` with a dynamic `import('signature_pad')`, and `calculation-engine.js` imports `Alpine` from `@alpinejs/csp` and the calculation code imports `mathjs/number`. The repository's `package.json` lists `@alpinejs/csp`, `mathjs` and `signature_pad` as dependencies; whether a host bundler resolves them from the add-on's `vendor` directory or its own `node_modules` is the host's build decision and is not configured by the add-on.

Order matters for the data components. The file comments on each state that the registration must run before `Alpine.start()`, because Alpine ignores data names registered after its initial scan of the page. The engines tolerate either order (see `registerAlpineEngine`).

The components are written so they also work under the `@alpinejs/csp` build: `x-data` in the partials is a plain call such as `listField(...)` or `signatureField()`, and the logic lives in module scope.

## What the entry exports

| Export | Source file | Takes `Alpine` as an argument | Registers |
| --- | --- | --- | --- |
| `registerAlpineEngine` | `lib/alpine-engine.js` | no (takes an options object) | nothing itself; the generic engine helper |
| `registerCalculationEngine` | `lib/calculation-engine.js` | no | an engine for `[data-calculation-type]` inputs |
| `registerCheckboxRequiredGroup` | `lib/checkbox-required-group.js` | yes | data component `checkboxRequiredGroup` |
| `registerListField` | `lib/list-field.js` | yes | data component `listField` |
| `registerOptionPricingEngine` | `lib/option-pricing-engine.js` | no | an engine for `[data-option-field]` |
| `registerProductPricingEngine` | `lib/product-pricing-engine.js` | no | an engine for `[data-product-field]` |
| `registerReplicatorField` | `lib/replicator-field.js` | yes | data component `replicatorField` |
| `registerReplicatorSet` | `lib/replicator-field.js` | yes | data component `replicatorSet` |
| `registerSignatureField` | `lib/signature-field.js` | yes | data component `signatureField` |
| `registerTotalPricingEngine` | `lib/total-pricing-engine.js` | no | an engine for `[data-total-field]` |

A function that takes `Alpine` is called as `registerListField(Alpine)`. The engine functions take no argument. The other modules in `resources/js/lib` (`date-boundary-engine.js`, `date-field-options.js`, `nominatim.js`, `parse-coordinates.js`, `video-embed-url.js`, and the calculation helpers) are not exported by the entry.

## Registered data components

These are registered with `Alpine.data(name, factory)`. Each is used from an `x-data` attribute in a partial.

### checkboxRequiredGroup

Registered by `registerCheckboxRequiredGroup(Alpine)`. Used by `checkboxes` when the field has the `required` rule: `x-data="checkboxRequiredGroup" @change="sync"`.

An HTML `required` on a checkbox demands that specific box be checked, so a required group would block submission until every box is ticked. The component clears `required` from the whole group once any box is checked and restores it when none is.

| Member | Behavior |
| --- | --- |
| `init()` | calls `sync()` once on mount, so a prefilled group (for example after a server validation error) starts correct |
| `sync()` | finds `input[type="checkbox"]` inside the component's element, and sets each box's `required` to `true` when none is checked and `false` when any is |

### listField

Registered by `registerListField(Alpine)`. Signature: `listField(items = [''], maxItems = 999, minItems = 1, handle = '')`. Used by `list`: `x-data='listField(<json>, <max_items ?? 999>, <min_items ?? 1>, "<handle>")'`.

| Property or method | Behavior |
| --- | --- |
| `items` | the rows; the passed array when it is a non-empty array, otherwise `['']` |
| `maxItems`, `minItems`, `handle` | the passed arguments |
| `canAdd()` | `items.length < maxItems` |
| `canRemove()` | `items.length > minItems` |
| `add()` | appends `''` when `canAdd()` |
| `remove(idx)` | removes row `idx` when `canRemove()` |
| `init()` | if the surrounding form scope already holds an array under `handle`, restores `items` from a copy of it; then watches `items` and mirrors every change back to that scope key (`this[handle]`) |

The mirroring exists because the conditional-fields mechanism renders fields inside `<template x-if>`, which destroys the subtree when a condition turns false; mirroring to the surviving form scope keeps the typed rows when the condition turns true again, as the file's own comment explains. The max and min values mirror the server-side rules; the file comment says server validation is authoritative.

### replicatorField

Registered by `registerReplicatorField(Alpine)`. Used by `replicator` as `x-data="replicatorField()"` on the element that carries the `data-replicator-*` attributes.

Configuration comes from the root element's data attributes:

| Attribute | Used for |
| --- | --- |
| `data-replicator-handle` | the field handle (default empty) |
| `data-replicator-min-sets` | minimum number of sets (default `0`) |
| `data-replicator-index-token` | the placeholder replaced by a new index in templates (default `REPLICATORSETINDEX`) |

Behavior:

- **Adding.** A click on `[data-replicator-add-type="<type>"]` clones `<template data-replicator-template="<type>">` into `[data-replicator-sets]`, replacing every occurrence of the index token with a new index (the counter starts at the number of existing sets plus 1000), and initializes the new tree with `Alpine.initTree`.
- **Removing.** A click on `[data-replicator-remove]` removes its set unless that would leave fewer than the minimum number of sets.
- **Minimum sets.** On mount, when the first `data-replicator-template` exists, sets of that template's type are added until the minimum is met.
- **Reindexing.** On the form's `submit`, `reindex()` renames each input from `<handle>[<anything>]` to `<handle>[<position>]` so the submitted indexes are contiguous.
- **State preservation.** The component mirrors a snapshot of its sets (type, source position, field values and nested replicator snapshots; file inputs are skipped) to the form scope under the handle on input and click, and restores from that snapshot when it is re-created, for the same `x-if` teardown reason as `listField`. Nested replicators are supported: a nested component does not mirror on its own, and defers to the outermost one.

### replicatorSet

Registered by `registerReplicatorSet(Alpine)`. Signature: `replicatorSet(seed = {})`. Used by each set in `replicator` as `x-data="replicatorSet({{ set_values }})"`. It returns a copy of `seed`, giving each set its own values so Alpine's `x-model` and `x-show` resolve per set instead of walking up to an ancestor scope.

### signatureField

Registered by `registerSignatureField(Alpine)`. Used by `signature` as `x-data="signatureField()"`.

| Member | Behavior |
| --- | --- |
| `mode` | `'draw'` (default) or `'type'` |
| `typedName`, `attested`, `value` | typed name, attestation checkbox state, and the value written to the hidden input |
| `init()` | reads the hidden input's name as the handle; takes an existing value from the form scope under that handle, else from the hidden input's `value`; watches `value` and mirrors it to the form scope; a stored value that does not start with `data:image/` switches to type mode with `attested = true`; then loads `signature_pad` dynamically, builds a pad on `x-ref="canvas"` (pen color `#000000`, transparent background), sizes the canvas to the device pixel ratio, restores a stored `data:image/` value with `fromDataURL`, and recaptures on pad end and on window resize |
| `destroy()` | removes the resize listener |
| `captureDrawing()` | sets `value` to `pad.toDataURL('image/png')`, or to an empty string when the pad is empty |
| `clear()` | clears the pad and sets `value` to an empty string |
| `useTypeMode()` / `useDrawMode()` | switch `mode`; the first calls `syncTyped()`, the second calls `captureDrawing()` |
| `syncTyped()` | sets `value` to the trimmed typed name only when it is non-empty and `attested` is true, otherwise to an empty string |

The submitted value is therefore a PNG data URL in draw mode, or the attested typed name as plain text in type mode.

## Element engines

An engine is not an Alpine component: it finds elements by selector and attaches behavior to them. They are registered through `registerAlpineEngine`, so they share one lifecycle.

### registerAlpineEngine

Signature: `registerAlpineEngine({ selector, boundAttr, init })`.

| Option | Type | Meaning |
| --- | --- | --- |
| `selector` | string | CSS selector for the elements to bind |
| `boundAttr` | string | camel-cased `dataset` key used as the idempotency flag; for example `calculationBound` becomes `data-calculation-bound="1"` once an element is wired |
| `init` | function | called once per matched element with the element |

Behavior:

- Binding waits until Alpine has begun initializing the page (the first `[x-data]` element carries Alpine's internal `_x_dataStack`, or the page has no `x-data` element and has finished parsing). Before that, it listens once for the `alpine:initialized` event.
- Elements already flagged are skipped, so rescans are idempotent.
- If `init` throws for one element, the error is logged with the selector and the flag is cleared so a later bind retries that element; the other elements are still bound.
- It returns a `bind()` function that scans again. Elements inserted after Alpine started are not bound on their own; call the returned function after inserting them.

### registerCalculationEngine

Takes no arguments; registers an engine on `[data-calculation-type]` with the flag `calculationBound`. The `text`, `integer` and `default` partials emit the attributes when a field has `calculation_query: true`; `checkboxes`, `radio` and `select` emit them as well (see [Form-field-partials.md](Form-field-partials.md#calculated-fields)).

Attributes read from the input:

| Attribute | Effect | Default |
| --- | --- | --- |
| `data-calculation-type` | `math` or `text`; any other value, or an empty formula, skips the element | required |
| `data-calculation-formula` | the formula | required |
| `data-calculation-decimals` | decimal places for math results | `2` when empty or missing |
| `data-calculation-syntax` | `bare` or `bracket` | `bracket` (anything other than `bare`) |
| `data-calculation-group` | an input in the same group is not recomputed while another input of the group was the last one touched | none |
| `data-calculation-primary` | when present, the engine clears the value if a referenced field becomes empty after it had written a result | absent |

The partials do not emit `data-calculation-syntax`, `-group` or `-primary`; a host override or other markup supplies them.

How it works: the input must sit inside a `<form>` or an element with `data-calculator-context`, and that container must have an Alpine scope (`x-data`); otherwise the engine logs a warning and skips the element. The input's `name` (a trailing `[]` marks a list value, and names containing `[` are skipped) is the key it writes to in the form scope. An `Alpine.effect` reads the referenced field values from the scope, waits until every referenced field (and every `[get_param:name]` URL parameter) has a value, evaluates the formula and writes the formatted result to the scope. An input that the user is editing is not overwritten (it records the last touched handle on `input` and `focus`).

Formula syntaxes, from `lib/calculation.js`:

| Syntax | Example | Notes |
| --- | --- | --- |
| `bracket` (default) | `[weight_lb] * 0.453592 + [weight_oz] * 0.0283495` | `[handle]` references are translated and evaluated by a sandboxed mathjs instance; `Math.fn(` and `**` are translated; `[get_param:name]` reads a URL query parameter |
| `bare` | `weight_lb * 0.453592 + weight_oz * 0.0283495` | evaluated by mathjs; `getParam(name)` reads a URL query parameter and returns `0` when missing or not a finite number |

Math mode uses the number-only mathjs build with `import`, `createUnit`, `evaluate`, `simplify` and `derivative` replaced by functions that throw. Text mode substitutes `[handle]` references into the formula text. Limits exported by the module: `MAX_FIELDS_IN_FORMULA = 100`, `MAX_REPLACEMENTS = 1000`, `MAX_FORMULA_LENGTH = 10000`, `MAX_TEXT_OUTPUT_LENGTH = 50000`, `MAX_PARSE_DEPTH = 10`. Math results are formatted with `toFixed(decimals)`; decimals outside 0 to 20 fall back to 2, and `0` rounds to an integer.

### Pricing engines

The product, option and total fields cooperate through one event and one shared hidden-input hook. Each engine binds plain DOM listeners and needs no Alpine scope.

| Engine | Selector | Flag | What it does |
| --- | --- | --- | --- |
| `registerProductPricingEngine()` | `[data-product-field]` | `productPricingBound` | unit price is the selected option's `data-price` (`multiple` mode) or `[data-product-single-price]`'s `data-price` (single mode); multiplied by `[data-product-quantity]` (a non-positive or non-numeric quantity counts as `0`); writes the line total, rounded to cents, to `[data-product-line-total-input]` and the display `[data-product-line-total-text]` |
| `registerOptionPricingEngine()` | `[data-option-field]` | `optionPricingBound` | the active price is the selected option's `data-price` (`select` mode) or the sum of checked `[data-option-input]` prices; writes to `[data-option-line-total-input]` and `[data-option-line-total-text]` |
| `registerTotalPricingEngine()` | `[data-total-field]` | `totalPricingBound` | sums every `[data-product-line-total-input]` in the form and writes the result to `[data-total-display-text]` and `[data-total-input]` |

Product and option engines dispatch a bubbling `product:price-changed` `CustomEvent` on the parent `<form>` with `detail: { handle, lineTotal }`, once on init and on every change. The total engine listens for it on the form and also computes once on init, so the order in which the engines initialize does not matter. Displayed amounts are formatted as US dollars with the sign outside the symbol (`-$25.00`); the hidden inputs hold the plain number with two decimals. The `option` partial's line-total input carries both `data-option-line-total-input` and `data-product-line-total-input`, which is why totals sum products and options uniformly.

## Which partial uses what

| Partial | Uses | Where in the partial |
| --- | --- | --- |
| `checkboxes` (when required) | `checkboxRequiredGroup` | `x-data="checkboxRequiredGroup" @change="sync"` on the wrapper |
| `list` | `listField` | `x-data='listField(...)'` with `x-for`, `x-model`, `@click`, `x-show` |
| `replicator` | `replicatorField`, `replicatorSet` | `x-data="replicatorField()"` on the root, `x-data="replicatorSet(...)"` per set, `<template x-if>` per field |
| `signature` | `signatureField` | `x-data="signatureField()"`, `x-show`, `x-ref`, `x-model`, `@click`, `@input`, `@change`, `:value` |
| `default`, `text`, `integer`, `checkboxes`, `radio`, `select` (with `calculation_query`) | calculation engine | `data-calculation-*` attributes |
| `product` | product pricing engine | `data-product-*` attributes |
| `option` | option pricing engine | `data-option-*` attributes and the shared `data-product-line-total-input` |
| `total` | total pricing engine | `data-total-*` attributes |

Because the partials only emit markup and attributes, a page that renders them without calling the matching registration functions shows static controls: the list has no rows, the signature has no canvas behavior, and totals do not update.
