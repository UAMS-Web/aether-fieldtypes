Written against v0.3.0.

This page covers the fieldtypes that let a user pick from choices or relate to other content: Checkboxes, Radio, Select, Option, True/False, Consent, Trigger, Terms, Terms w/ Search Index, Entries and Lists. Each section lists the handle, whether the fieldtype can be selected in form blueprints, the Control Panel config options, the stored value and the form partial. Config options that Statamic's own fieldtype already provides are not repeated; only what this add-on adds or changes is listed.

- [Shared options for the choice fieldtypes](#shared-options-for-the-choice-fieldtypes)
- [Checkboxes](#checkboxes)
- [Radio](#radio)
- [Select](#select)
- [Option](#option)
- [True/False](#truefalse)
- [Consent](#consent)
- [Trigger](#trigger)
- [Terms](#terms)
- [Terms w/ Search Index](#terms-w-search-index)
- [Entries](#entries)
- [Lists](#lists)

## Shared options for the choice fieldtypes

Checkboxes, Radio and Select share config fields from traits in `src/Traits/`. They are described once here and referenced below. Each of the three keeps the handle of the Statamic fieldtype it extends, and each injects these fields into the existing config sections ("Selection & Options" and "Data & Format") of the parent.

### Preset options (`PresetOptions`)

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `preset` | select | `none` | Replaces the manually entered options with a built-in list. `none` keeps the manual options. Required. |
| `state_types` | checkboxes | `state` | Shown only for `us_states`. Chooses among `state`, `territory` and `district`. |
| `language_scope` | checkboxes | `individual` | Shown only for `languages`. Chooses among `individual`, `collective` and `macrolanguages`. |
| `language_standard` | select | `iso639` | Shown only for `languages`. `iso639` or `bcp47`. |
| `mime_type_categories` | checkboxes | all eight categories | Shown only for `mime_types`. Categories are `application`, `audio`, `font`, `image`, `message`, `model`, `text` and `video`. |

Preset keys: `us_states`, `canadian_provinces`, `countries`, `languages`, `currencies`, `months`, `days_of_week`, `yes_no`, `true_false`, `mime_types` and `mime_categories`. When a preset is selected, the manual `options` field is hidden, and the parent's `push_tags` field is always hidden. While any preset is selected the parent's `cast_booleans` and `taggable` fields are hidden; the `max_items` and `multiple` fields are hidden only while the `yes_no` or `true_false` preset is selected.

### Randomize order (`RandomizesOptions`)

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `randomize_order` | toggle | `false` | Shuffles the options each time they are read. |

### Other option (`OtherOption`)

Used by Checkboxes and Radio only.

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `enable_other` | toggle | `false` | Adds an "Other" choice with a free-text input. |
| `other_label` | text | `Other` | Label of the "Other" choice. Shown only when `enable_other` is on. |
| `other_placeholder` | text | `Enter your custom value` | Placeholder of the custom-value input. Shown only when `enable_other` is on. |

### Dynamic default value (`DynamicDefaultValue`)

Added to "Data & Format".

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `calculation_query` | toggle | `false` | Enables a dynamic default value. While on, the parent's `default` field is hidden. |
| `calculation_formula` | code (Handlebars mode) | none | The text to pre-populate, shown only when `calculation_query` is on. |

### Per-choice submission limits (`HasChoiceSubmissionLimits`)

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `enable_choice_limits` | toggle | `false` | Caps how many submissions may select each choice. |
| `choice_limits` | grid | none | One row per capped choice: `choice` (text, the option key, required) and `limit` (integer, minimum 1, required). Shown only when limits are enabled. A choice left out stays unlimited. |
| `choice_limit_message` | text | `One or more of your selections is no longer available. Please choose another option.` | Message shown when a submission selects a full choice. |

The fieldtype also adds `full_choices` to the renderable field data, which the form partials use to disable and label full choices. Enforcement on submission is described in [Choice submission limits](Choice-submission-limits.md).

## Checkboxes

Multiple-choice list with optional "Other" entry and selection-count limits. Extends Statamic's Checkboxes fieldtype (`Statamic\Fieldtypes\Checkboxes`). Handle: `checkboxes` (the class sets no `$handle`, so it is derived from the class name). Selectable in form blueprints: yes. The class does not set `$selectableInForms`; Statamic's Checkboxes sets it to `true` and this class inherits it. Control Panel component: `custom_checkboxes`, a fork of Statamic's Checkboxes component.

What it adds to the parent:

- Preset options, Other option, randomize order, per-choice submission limits and dynamic default value, as described in [Shared options](#shared-options-for-the-choice-fieldtypes).
- Selection limits, added to "Selection & Options":

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `selection_limit` | select | `unlimited` | `unlimited`, `exact` or `range`. Required. |
| `min_selections` | integer | none | Minimum selections. Minimum value 1. Shown and required only when `selection_limit` is `range`. |
| `max_selections` | integer | none | Maximum selections. Minimum value 1. Shown and required only when `selection_limit` is `range`. |
| `exact_selections` | integer | none | Exact number of selections. Minimum value 1. Shown and required only when `selection_limit` is `exact`. |

Stored value: an array of the selected option values, as in Statamic's Checkboxes. When "Other" is enabled the Control Panel component emits an `__other__:text` form for the custom entry (read from the component's source; the stored shape for form submissions is not documented here).

Validation: the selection-count rules are returned from `rules()` and so attach to the field's own handle.

- `range` with `min_selections`: fails with "You must select at least :min options." only when at least one option is selected and fewer than the minimum are chosen. An empty selection defers to the field's own `required` state.
- `range` with `max_selections`: fails with "You may not select more than :max options." when the array exceeds the maximum.
- `exact` with `exact_selections`: fails with "You must select exactly :exact options." when at least one option is selected and the count differs. An empty selection defers to `required`.

These are closures defined in the fieldtype, not classes in `src/Rules/`.

Form partial: `resources/views/forms/fields/checkboxes.antlers.html`. Full choices are disabled and labeled "Full".

```yaml
-
  handle: interests
  field:
    type: checkboxes
    display: Interests
    options:
      research: Research
      teaching: Teaching
    enable_other: true
    selection_limit: range
    min_selections: 1
    max_selections: 2
```

## Radio

Single-choice list with optional "Other" entry. Extends Statamic's Radio fieldtype (`Statamic\Fieldtypes\Radio`). Handle: `radio`. Selectable in form blueprints: yes (inherited from Statamic's Radio, which sets `$selectableInForms` to `true`). Control Panel component: `custom_radio`, a fork of Statamic's Radio component.

Config options added: preset options, Other option, randomize order, per-choice submission limits and dynamic default value, all described in [Shared options](#shared-options-for-the-choice-fieldtypes).

Stored value: a single option value, as in Statamic's Radio. The stored shape of a custom "Other" entry is not documented here.

Validation: none added by this class.

Form partial: `resources/views/forms/fields/radio.antlers.html`. Full choices are disabled and labeled "Full".

## Select

Dropdown with presets. Extends Statamic's Select fieldtype (`Statamic\Fieldtypes\Select`). Handle: `select`. Selectable in form blueprints: yes (inherited from Statamic's Select, which sets `$selectableInForms` to `true`). The class uses Statamic's own Control Panel component.

Config options added: preset options, randomize order, per-choice submission limits and dynamic default value ([Shared options](#shared-options-for-the-choice-fieldtypes)). The Other option is not available on Select. The parent's `max_items` field is changed to display only when `multiple` is on.

Stored value: as in Statamic's Select (a single value, or an array when `multiple` is on). Validation: none added by this class.

Form partial: `resources/views/forms/fields/select.antlers.html`. Full choices are disabled and labeled "(Full)".

## Option

Priced choices tied to a Product field. Extends `Statamic\Fields\Fieldtype` directly. Handle: `option`. Selectable in form blueprints: yes (`$selectableInForms = true`). Icon: `shopping-bag-tag`. Control Panel component: `option`.

Config options (section "Option Settings"):

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `input_type` | radio | `select` | How choices are presented: `select` (Drop Down), `radio` (Radio Buttons) or `checkbox` (Checkboxes). Required. |
| `product_field` | text | none | The handle of the Product field whose price these options modify. Required. |
| `options` | grid | none | Rows of `key` (text, required), `label` (text, optional) and `price` (currency, required). The price is added to, or subtracted from, the product price and form total when the option is selected. Required. |

Stored value (shape produced by `process()`): `null` when nothing valid is selected, otherwise an array.

- `select` and `radio`: `input_type`, `key`, `label` (falls back to the key), `price` (float) and `line_total` (the price rounded to two decimals). Submitted as `<handle>[key]`.
- `checkbox`: `input_type` set to `checkbox`, `keys`, `selections` (a list of `key`, `label` and `price`) and `line_total` (sum of the selected prices, rounded to two decimals). Submitted as `<handle>[keys][]`.

Keys that are not in the configured options are dropped, so a tampered submission resolves to `null` or to only the valid selections. Non-array input also resolves to `null`.

Renderable data: `option_choices`, a list of `key`, `label`, `price` and `formatted_price` (a `$`-prefixed string with two decimals, with a leading `-` for negative amounts).

Validation: no rules classes are added by this fieldtype.

Form partial: `resources/views/forms/fields/option.antlers.html`, which also submits a `<handle>[line_total]` hidden input.

## True/False

Two-button boolean choice. Extends Statamic's Button Group fieldtype (`Statamic\Fieldtypes\ButtonGroup`). Handle: `true_false` (derived from the class name). Title: "True/False". Icon: `fieldtype-toggle`. Control Panel component: `true_false`. Selectable in form blueprints: not set by the class, so it inherits the default from Statamic's `Fieldtype` base class, which is `false` in the installed Statamic copy this page was checked against; confirm in the host application's Statamic version.

Config options (section "Options"):

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `label_true` | text | none | Overrides the label `True`. A blank value keeps the default. |
| `label_false` | text | none | Overrides the label `False`. A blank value keeps the default. |
| `clearable` | toggle | `false` | Allows clearing the selection back to `null`. |
| `default` | toggle | `false` | Default value of the field. |
| `reverse_order` | toggle | `false` | Shows False before True. |

Stored value: a boolean, or `null`. Both `process()` and `preProcess()` map `null` to `null`, the string `false` to `false`, and any other value to its boolean cast.

Validation: none added. Form partial: none dedicated. No `true_false.antlers.html` exists, so the fallback partial `default.antlers.html` applies; see [Form field partials](Form-field-partials.md).

## Consent

Checkbox that records agreement text alongside the answer. Extends `Statamic\Fields\Fieldtype`. Handle: `consent`. Selectable in form blueprints: not set by the class, so it inherits Statamic's default (`false` in the installed copy this page was checked against). Not defaultable (`$defaultable = false`). Icon: `fieldtype-checkboxes`. Control Panel component: `consent`.

Config options (section "Consent Options"):

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `checkbox_label` | text | `I agree` | Label of the checkbox. |
| `consent_agreement` | markdown | none | Agreement text shown to the user and stored with the form entry, to record what was agreed to. Toolbar: bold, italic, unordered list, ordered list, quote, link; automatic links on. |

Stored value: the Control Panel component updates the value to an array with `value` (boolean) and `text` (the raw Markdown from `consent_agreement`). The agreement is rendered to HTML for display through `preload()`, which returns the field config plus `consent_agreement_html`. The component sanitizes that HTML (`consent-agreement.js`).

Validation: none added. Form partial: none dedicated; no `consent.antlers.html` ships in v0.3.0.

## Trigger

Button that triggers an action, with no stored data. Extends `Statamic\Fields\Fieldtype`. Handle: `trigger`. Selectable in blueprints: no (`$selectable = false`). Selectable in form blueprints: no (`$selectableInForms = false`). Categories: `controls`, `special`. Not localizable, not validatable and not defaultable. Icon: `workflow`. Control Panel component: `trigger`.

Config options (section "Appearance"):

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `input_label` | text | `Trigger Action` | Label of the button. |
| `related_field` | text | empty | Handle of the field that determines whether the trigger is enabled. The trigger is enabled if that field has a value. |

Stored value: none. `process()` and `preProcess()` always return `null`. Validation: none. Form partial: none.

## Terms

Taxonomy terms picker that applies query scopes. Extends Statamic's Terms fieldtype (`Statamic\Fieldtypes\Terms`). Handle: `terms` (derived from the class name). Selectable in form blueprints: not set by the class; Statamic's Terms does not set `$selectableInForms`, so the base default applies.

What it changes: the picker index passes the merged query scope handles to each scope's `apply()` call. Scope handles supplied on the request (`queryScopes`) are preferred; when the request carries none, the field's `query_scopes` config is used. The parent implementation is not called on this path because it would overwrite the request handles with the field config. If the query or params are not of the expected types, the parent behavior is used unchanged. The stated aim is alignment with `Entries` and `statamic/cms#14204`.

Config options: none added; the options are those of Statamic's Terms. Stored value and validation: unchanged from Statamic's Terms. Form partial: none dedicated.

## Terms w/ Search Index

Terms picker backed by a Statamic search index. Extends this add-on's `Terms` class. Handle: `terms_search_index` (derived from the class name). Title: "Terms w/ Search Index". Selectable in form blueprints: not set by the class (see [Terms](#terms)). Extra picker keywords such as "taxonomy terms w/ search index" make it discoverable in the fieldtype picker.

Config changes against `Terms`:

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `search_index` | text | `null` | Name of the search index used to find terms. Added to the first config section. When empty, searching falls back to a `title like %term%` query. |
| `mode` | removed | n/a | The parent's `mode` option is removed so the picker always uses the default Stack Selector mode. |

Behavior of the picker query:

- Taxonomies are limited to those configured on the field. A request may narrow them with `taxonomies` but cannot add taxonomies outside the config. Only taxonomies the current user can `view` are included; if none remain, no terms are returned.
- With a `search_index` configured and a search term present, the term ids returned by the index are used as the filter; no hits means no terms. With no search term the index is not queried.
- The request's `site` filters by site, `exclusions` removes ids, and query scopes are applied as in `Terms`.

Stored value and validation: unchanged from Statamic's Terms. Form partial: none dedicated.

## Entries

Entries picker with blueprint restrictions and a people scope. Extends Statamic's Entries fieldtype (`Statamic\Fieldtypes\Entries`). Handle: `entries` (derived from the class name). Selectable in form blueprints: not set by the class; Statamic's Entries does not set `$selectableInForms`, so the base default applies.

What it adds:

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `blueprints` | taggable | none | Blueprint handles (the file basename, for example `foo` for `path/to/foo.yaml`) that restrict which blueprints can be used to create new entries. Added to the "Appearance" section and shown only when `create` is on. |

Behavior changes:

- Create links: for each configured collection the current user may create entries in, the picker offers one link per visible blueprint (filtered by `blueprints` when set), each pointing to the create URL with `?blueprint=<handle>`. A custom create URL, when provided by the base class, replaces them. Titles are the blueprint title for a single collection; for several collections the collection title (single blueprint) or `Collection: Blueprint`. A configured collection that does not exist throws `CollectionNotFoundException`.
- Query scopes: the field's `query_scopes` are set on the request params as `queryScopes` before the parent applies them.
- People scope: when `people` is among the configured collections and the class `UAMSWeb\Aether\People\Support\PeopleEditingScope` exists, entries outside the current user's scope are excluded from the picker (`whereNotIn` on `outOfScopeEntryIds()`) when the scope's `appliesTo()` returns true for the user. If the scope class lacks `appliesTo()` or `outOfScopeEntryIds()`, a `LogicException` is thrown rather than skipping the restriction. Without that class nothing is applied.
- `preProcess()`: an `EntryCollection` value is reduced to its ids; other values use the parent behavior.

Stored value and validation: unchanged from Statamic's Entries. Form partial: none dedicated.

## Lists

Dynamic list of text items with item-count limits. Extends Statamic's Lists fieldtype (`Statamic\Fieldtypes\Lists`). Handle: `list`, inherited from Statamic's Lists class (it declares `$handle = 'list'`); the class declares none of its own, and a test in the repository asserts the registration under the `list` handle. Selectable in form blueprints: yes (`$selectableInForms = true`). Statamic's icon is kept.

Config options: the parent's fields are wrapped in an "Input Value and State" section, and a "Constraints" section is added:

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `min_items` | integer | `null` | Minimum number of items. Validated with `gte:0`. |
| `max_items` | integer | `null` | Maximum number of items. Validated with `gt:0`. |

Stored value: an array of items, as in Statamic's Lists (the form partial submits a 0-indexed array under the field handle).

Validation: `rules()` always starts with `array`, then adds one of the following when the values are positive integers: `between:min,max` when both are set, `min:N` when only the minimum is set, `max:N` when only the maximum is set. Because the array rule is present, the error messages count items rather than characters.

Form partial: `resources/views/forms/fields/list.antlers.html`, which uses `min_items` and `max_items` to gate its add and remove buttons client-side; server-side validation is authoritative.
