Written against v0.3.0.

This page covers the composite fieldtypes (`address`, `name`, `gps`, `product`, `total`, `signature`), the media fieldtypes (`assets`, `audio`, `video`) and the layout fieldtypes (`replicator`, `section`, `subsection`, `html`). Handles are derived by Statamic from the class name in snake case; none of these classes sets `$handle`. Where a fieldtype extends a Statamic core fieldtype, only what the add-on adds or changes is described; the core options are inherited unchanged.

- [Address](#address)
- [Name](#name)
- [Gps](#gps)
- [Assets](#assets)
- [Audio](#audio)
- [Video](#video)
- [Signature](#signature)
- [Replicator](#replicator)
- [Section](#section)
- [Subsection](#subsection)
- [Html](#html)
- [Product](#product)
- [Total](#total)

Related pages: [Validation rules](Validation-rules.md), [Form field partials](Form-field-partials.md), [Map settings](Map-settings.md).

## Address

A structured postal address with American, Canadian and International formats. It is a custom fieldtype built on the `HasSubfields` trait, not a fork of a core fieldtype.

- Handle: `address`
- Selectable in form blueprints: yes (`$selectableInForms = true`)
- Form partial: `resources/views/forms/fields/address.antlers.html`

### Control Panel config options

All options appear in a `Subfields` section.

| Name | Type | Default | Effect |
| --- | --- | --- | --- |
| `preset` | select | `american` | Address Format: `american`, `canadian` or `international`. Chooses which state/province subfield applies and whether `country` is offered. |
| `<subfield>_include` | toggle | `true` for every subfield | Include the subfield. |
| `<subfield>_required` | toggle | see below | Whether the subfield is required when the field itself is required. |
| `<subfield>_label` | text | the subfield's default label | Label override. |
| `<subfield>_placeholder` | text | none | Placeholder; offered only for text subfields. |
| `<subfield>_default` | text or select | empty | Default value for the subfield. |
| `<subfield>_select_value_type` | radio | `label` | For select subfields: store the option label (`label`) or its key (`key`). |
| `<subfield>_width` | width | `100` | Width of the subfield in the Control Panel and form layout. |

The `<subfield>` prefixes, with their default `_required` and default `_width`:

| Subfield handle | Shown for preset | Required default | Width default |
| --- | --- | --- | --- |
| `street_address` | all | yes | `100` |
| `address_line_2` | all | no | `100` |
| `city` | all | yes | `50` |
| `state_province_region_american` | `american` | yes | `50` |
| `state_province_region_canadian` | `canadian` | yes | `50` |
| `state_province_region_international` | `international` | yes | `50` |
| `postal_code` | all | yes | `50` |
| `country` | `international` | yes | `50` |

The three `state_province_region_*` subfields share the canonical storage key `state_province_region`. The American variant offers U.S. states, territories and the District of Columbia, the Canadian variant offers provinces and territories, and the international variant is free text. `country` is a select of ISO 3166-1 alpha-2 codes. The `postal_code` subfield has a placeholder default of `e.g., 12345 or 12345-6789`.

The `_select_value_type` setting is applied by the Control Panel component (`HasSubfields.vue`), which writes the option label when set to `label` and the option key when set to `key`. The PHP `process()` method stores what it receives.

### Stored value

A flat map keyed by the canonical subfield handle: `street_address`, `address_line_2`, `city`, `state_province_region`, `postal_code` and (international) `country`. When at least one subfield holds a non-blank value, `process()` also writes a `preset` key with the configured preset (the first preset, `american`, when unset). An address with every subfield blank is stored as an empty array.

### Validation

- The field-level rule is `array`.
- Each visible subfield gets `required` when the field's `validate` contains `required`, the subfield is included and its `_required` toggle is on; otherwise `nullable`. Subfields hidden by the current preset are skipped.
- When the blueprint validation uses `sometimes`, the nested rules honor it.
- No format rule (for example ZIP Code) is attached to subfields by the add-on's PHP. The `postal_code` subfield definition declares the type `zip_code`, but the PHP validation above only ever applies `required` or `nullable`.

### Form rendering

`extraRenderableFieldData()` adds `state_options_american`, `state_options_canadian` and `country_options` (code to name maps) to the partial's scope so the partial can render dropdowns. The submitted value is the option code.

```yaml
-
  handle: mailing_address
  field:
    type: address
    display: Mailing Address
    preset: canadian
    validate:
      - required
    address_line_2_include: false
```

## Name

A structured personal name built on the `HasSubfields` trait. It has no preset.

- Handle: `name`
- Selectable in form blueprints: yes
- Form partial: `resources/views/forms/fields/name.antlers.html`

### Control Panel config options

The same per-subfield option families as [Address](#address) (`_include`, `_required`, `_label`, `_placeholder`, `_default`, `_width`) in a `Subfields` section. Name subfields are all text, so there is no `_select_value_type`, and no `preset` option.

| Subfield | Included by default | Required by default | Placeholder default | Width default |
| --- | --- | --- | --- | --- |
| `prefix` | no | no | `e.g. Dr., Mr., Ms.` | `50` |
| `first_name` | yes | yes | `Enter first name` | `50` |
| `middle_name` | no | no | `Enter middle name` | `50` |
| `last_name` | yes | yes | `Enter last name` | `50` |
| `suffix` | no | no | `e.g. Jr., Sr., III` | `50` |

### Stored value and validation

Stored as a flat map under the field handle (`prefix`, `first_name`, `middle_name`, `last_name`, `suffix`). Validation follows the same rules as Address: the field is an `array`, and each included subfield is `required` when the field is required and the subfield's `_required` toggle is on, otherwise `nullable`. The `process()` method keeps an array as is and turns anything else into an empty array; it does not add a `preset` key.

## Gps

A latitude and longitude picker. It replaces a free-text "lat, lng" string with a structured value chosen on an interactive Leaflet and OpenStreetMap map in the Control Panel.

- Handle: `gps`
- Display title: `GPS / Map`; category `special`
- Selectable in collection blueprints: yes. Selectable in form blueprints: no (`$selectableInForms = false`)
- Form partial: none

### Control Panel config options

In a `Map Defaults` section:

| Name | Type | Default | Effect |
| --- | --- | --- | --- |
| `default_latitude` | text | unset | Latitude the picker centers on before a marker is placed. Falls back to `maps.default.latitude`. |
| `default_longitude` | text | unset | Longitude the picker centers on before a marker is placed. Falls back to `maps.default.longitude`. |
| `default_zoom` | integer | unset | Initial zoom, described in the field as 0 (world) to 19 (building). Falls back to `maps.default.zoom`. |
| `enable_address_search` | toggle | `false` | Shows an address-search box in the picker (see below). |

The map defaults, tile source and aerial layer come from `config/maps.php`; see [Map settings](Map-settings.md).

### Picker behavior

The component offers a Street and Aerial layer toggle, a paste box for coordinates (decimal, DMS, `geo:` URI and UTM formats are accepted per its on-screen instructions), a draggable marker, reset and center controls, and read-only generated latitude and longitude values.

When `enable_address_search` is on and the field is not read-only, a search box is shown. At v0.3.0 the box requests the Control Panel URL `gps/nominatim-search` with the query as a `q` parameter, after a 400 ms debounce and only once the query reaches the minimum length (`maps.nominatim.min_query_length`, default `3`). It expects a JSON response with a `results` list of `{ lat, lng, label }`. The add-on's own code does not register a handler for that URL: `config/maps.php` describes it as "an endpoint the host provides". If the host does not provide one, the request fails and the box shows "Address search is unavailable right now. Try again shortly." Choosing a result drops the pin and stores its coordinates with the current zoom.

### Stored value

```yaml
map:
  lat: 34.7492719
  lng: -92.3198281
  zoom: 16
```

`lat` and `lng` are rounded to 7 decimal places; `zoom` is stored as an integer only when present and numeric. Both `lat` and `lng` must be numeric, otherwise the value is `null`. A legacy comma-separated string such as `34.7, -92.3` is accepted on read and write and converted to the array shape. The same normalization runs in `preProcess()`, `process()` and `augment()`.

### Validation

- `<handle>.lat`: `nullable`, `required_with:<handle>.lng`, and the `Latitude` rule (range `-90.0` to `90.0`).
- `<handle>.lng`: `nullable`, `required_with:<handle>.lat`, and the `Longitude` rule (range `-180.0` to `180.0`).

A half-coordinate is therefore rejected. See [Validation rules](Validation-rules.md).

## Assets

The Statamic Assets fieldtype with file type restrictions, file size limits, minimum image dimensions and an email attachment toggle. It extends `Statamic\Fieldtypes\Assets\Assets`, so every core option (container, max files, folder, and so on) and the core icon are unchanged.

- Handle: `assets`
- Selectable in form blueprints: yes (inherited from the core fieldtype)
- Form partial: `resources/views/forms/fields/assets.antlers.html` (renders the shared `files` partial)

### Control Panel config options added

In a new `File Restrictions` section:

| Name | Type | Default | Effect |
| --- | --- | --- | --- |
| `file_type_restrictions` | radio | `all` | `all`, `mime_types` (specific types) or `mime_categories` (type categories). |
| `mime_types` | multiple select (preset `mime_types`) | unset | Allowed MIME types; shown when `file_type_restrictions` is `mime_types`. |
| `mime_categories` | multiple select (preset `mime_categories`) | unset | Allowed categories such as `image`; shown when `file_type_restrictions` is `mime_categories`. |
| `min_file_size` | float | unset | Minimum size per file, in megabytes. |
| `max_file_size` | float | unset | Maximum size per file, in megabytes. |
| `min_width` | integer | unset | Minimum image width in pixels; shown when `file_type_restrictions` is not `all`. |
| `min_height` | integer | unset | Minimum image height in pixels; shown when `file_type_restrictions` is not `all`. |

In a form blueprint only, an `attach_to_email` toggle (default `false`) is added to the core `Appearance` section, with instructions that the form's email notification settings must also have attachments enabled.

### Validation and enforcement at v0.3.0

- `rules()` adds one asset-aware rule, `AssetMinimumDimensions`, when `min_width` or `min_height` is above zero and the field is restricted to images only (every allowed category is `image`, or every allowed MIME type starts with `image/`). It reads the dimensions stored in asset metadata, and an asset whose dimensions could not be measured is skipped rather than rejected. See [Validation rules](Validation-rules.md).
- `getValidationRules()` builds string rules for the MIME types (`mimetypes:`), category extensions (`extensions:`) and size bounds (`between:`, `min:`, `max:`, converted at 1 MB = 1000 KB). The class does not wire these into `rules()`, because they are file-upload rules that would reject an array of asset references; the tests assert that no string upload rules are added. The type and size settings therefore do not reject a selection through validation at v0.3.0.
- `preload()` adds query scopes to the field config so the asset browser can filter: `assets_by_file_type` plus encoded `assets_by_file_type_<category-or-mime-case>` handles when types are restricted, and `assets_by_file_size` plus encoded `assets_by_file_size_min_<bytes>` and `assets_by_file_size_max_<bytes>` handles (1 MB = 1,000,000 bytes) when a size bound is set. The tree for this release contains the `AssetsByFileSize` scope class (`src/Scopes/AssetsByFileSize.php`) but no `assets_by_file_type` scope class.
- `getAcceptAttribute()` builds an `accept` string (MIME types, or `<category>/*`) but no template in this release calls it.
- No add-on code in this release reads `attach_to_email`; the option is stored in the blueprint only.

`src/Fieldtypes/Assets_docs.md` describes type and size restrictions as validated automatically; the code above is what v0.3.0 does.

```yaml
-
  handle: photos
  field:
    type: assets
    container: images
    file_type_restrictions: mime_categories
    mime_categories:
      - image
    min_width: 800
    min_height: 600
```

## Audio

Stores a media URL and previews it in the Control Panel with a native `<audio controls>` element. Direct file URLs only; there is no embed or iframe support.

- Handle: `audio` (new, additive)
- Extends: `Statamic\Fieldtypes\Video`; adds the keywords `audio`, `sound`, `music`, `mp3`, `podcast`, the icon `media-music-notes` and the Vue component `custom_audio`
- Selectable in form blueprints: no (the inherited default)
- Form partial: none
- Config options: those of the core Video fieldtype (`Placeholder` in the `Appearance` section and `Default Value` in the `Data & Format` section, both text); the add-on adds none.
- Stored value: the URL string, as the core Video fieldtype stores it.

## Video

Statamic's `video` handle, with the Control Panel preview replaced by a native `<video>` element for direct file URLs instead of an iframe.

- Handle: `video` (supersedes the core fieldtype of the same handle)
- Extends: `Statamic\Fieldtypes\Video`; the only override is the Vue component `custom_video`
- Selectable in form blueprints: no (the inherited default)
- Form partial: none
- Config options, stored value and validation: those of the core Video fieldtype (`Placeholder`, `Default Value`); the PHP is otherwise unchanged.

## Signature

A drawn signature captured on a canvas on public forms, with an accessible typed-name alternative.

- Handle: `signature`
- Selectable in form blueprints: yes
- Form partial: `resources/views/forms/fields/signature.antlers.html`
- Config options: none beyond the core options (the add-on's own option list is empty; a block of width, color and pen options is commented out in the source). A default value is not offered (`$defaultable = false`).

### Stored value and validation

A string. In draw mode the form's hidden input carries a base64 PNG data URL; in type mode it carries the typed full name, which `process()` also accepts. `process()` and `preProcess()` convert `''` and `null` to `null` and otherwise return the value unchanged. The add-on adds no validation rule; a required empty signature is caught by the form's `required` rule because the value becomes `null`.

## Replicator

Statamic's Replicator with a form-blueprint option, a minimum number of sets and "at least one enabled set" validation. It extends `Statamic\Fieldtypes\Replicator` and uses the `ConfigSectionWrapper` trait.

- Handle: `replicator`
- Selectable in form blueprints: yes (the core default is no)
- Form partial: `resources/views/forms/fields/replicator.antlers.html`

### Control Panel config options added

| Name | Type | Default | Effect |
| --- | --- | --- | --- |
| `min_sets` | integer | unset | Minimum Sets. Added to the `Boundaries & Limits` section. Counts all sets, disabled ones included. |

### Validation

- `ReplicatorRequireAtLeastOneEnabledSet` is added when the field's `validate` config holds any `required*` rule (`required`, `required_if`, `required_unless`, `required_with`, and so on). It fails with "At least one set must be enabled." unless a set exists whose `enabled` is not `false`. It runs only when the field is required for the current request, so `sometimes` with an unmet condition skips it.
- `ReplicatorMinSets` is added when `min_sets` is above zero. It fails with "At least one set is required." (for 1) or "At least :min sets are required." when fewer sets are present, and only when the field is also required for the request.

See [Validation rules](Validation-rules.md).

### Value handling and form rendering

`process()` and `preProcessValidatable()` coerce each row's `enabled` to a boolean (`'0'`, `'false'`, `'no'`, `'off'` and the empty string become `false`), because a public form posts it as a string. For public forms, `preProcessTagRenderable()` supplies `replicator_rows` (existing or old-input rows), `replicator_set_templates` (one blank template per set type), `replicator_min_sets` and `replicator_index_token` to the partial. Subfield inputs post as `<handle>[<index>][<subfield>]`. The index placeholder is `REPLICATORSETINDEX` for a top-level replicator and `REPLICATOR-NESTED-<depth>-INDEX` for a replicator nested inside another's set.

## Section

Statamic's Section fieldtype (a heading with instructions that groups the fields after it), made available in form blueprints.

- Handle: `section`
- Extends: `Statamic\Fieldtypes\Section`; the only change is `$selectableInForms = true`
- Form partial: `resources/views/forms/fields/section.antlers.html` (renders the display as an `h3` and the instructions as small text; no input, no submitted value)
- Config options and stored value: those of the core fieldtype. The core class is not selectable in collection blueprints (`$selectable = false`).

## Subsection

A divider that separates blocks of content within a Control Panel panel. It is the add-on's replacement for the core Section, which Statamic 6 deprecates and plans to remove in Statamic 7, as the class docblock states.

- Handle: `subsection`
- Display title: `Subsection`; category `special`; icon `fieldtype-section`; Vue component `subsection`
- Not localizable, not validatable, no default value (`$localizable`, `$validatable` and `$defaultable` are all `false`)
- Selectable in form blueprints: no (the inherited default)
- Form partial: none
- Config options and stored value: none defined. The add-on uses it internally to group options in config panels (the `type: subsection` entries in the Address, Name and Assets option lists).

## Html

Statamic's Html fieldtype: blueprint-authored markup shown as content, with no input.

- Handle: `html`
- Extends: `Statamic\Fieldtypes\Html`; the only change is `$selectableInForms = false`
- Form partial: `resources/views/forms/fields/html.antlers.html` (emits the `html` config value raw inside a `form-html` div)
- Config options: those of the core fieldtype (`html` as a code field, and a `sanitize` toggle that defaults to `true`).

The docblock explains why it is hidden from form blueprints: the partial outputs the value unescaped, so offering it to the non-admin `form_editor` role would allow stored cross-site scripting on public forms. A blueprint edited directly can still use it. The partial's own comment names a `vendor/statamic/forms/fields.antlers.html` wrapper for the label handling, and the class docblock gives the partial path as `resources/views/vendor/statamic/forms/fields/html.antlers.html`; the file in this release is at the path listed above.

## Product

A product for public forms, either a single base-priced item with a quantity or a choice among priced options.

- Handle: `product`
- Selectable in collection blueprints: yes. Selectable in form blueprints: yes. No category (`$categories = []`); icon `shopping-store`
- Form partial: `resources/views/forms/fields/product.antlers.html`

### Control Panel config options

In a `Product Options` section:

| Name | Type | Default | Effect |
| --- | --- | --- | --- |
| `selection_type` | radio | `single` | `single` (Single Product) or `multiple` (Multiple Product Choices). Required. |
| `price` | currency | unset | Base price. Shown and required when `selection_type` is `single`; excluded otherwise. |
| `disable_quantity` | toggle | `false` | Fixes the quantity at one. Shown when `selection_type` is `single`. |
| `options` | grid (stacked, "Add Product") | unset | Choices for `multiple`: `key` (text, required), `label` (text, optional) and `price` (currency, required). Required when `selection_type` is `multiple`; excluded otherwise. |

### Stored value

`process()` returns `null` unless something was ordered. Otherwise it returns a map recomputed on the server from the field config, not from the submitted `line_total`:

| Key | `single` | `multiple` |
| --- | --- | --- |
| `selection_type` | `single` | `multiple` |
| `key` | the field handle | the chosen option key |
| `label` | the field display | the option label, falling back to the key |
| `quantity` | the submitted quantity, or `1` when `disable_quantity` is on | the submitted quantity, `1` when missing |
| `price` | the configured base price | the chosen option's price |
| `line_total` | `price * quantity`, rounded to 2 decimals | same |

A missing, zero or negative quantity, a missing key, or a key that is not one of the configured options yields `null`.

### Validation and form rendering

The fieldtype adds no validation rule of its own. `extraRenderableFieldData()` supplies `formatted_price` (the base price as a U.S. dollar string, with the minus sign before the `$` for negatives) and `product_options` (each option with `key`, `label`, a numeric `price` and a `formatted_price`) to the partial. The partial's hooks drive `product-pricing-engine.js`, which writes a hidden `line_total` and fires `product:price-changed` for the [Total](#total) field.

```yaml
-
  handle: registration
  field:
    type: product
    display: Registration
    selection_type: single
    price: 25
    disable_quantity: true
```

## Total

A read-only running order total for public forms, summing the line totals written by [Product](#product) fields (and by `option` fields, which are documented on another page).

- Handle: `total`
- Selectable in collection blueprints: yes. Selectable in form blueprints: yes. No category; icon `money-bag-dollar`
- Form partial: `resources/views/forms/fields/total.antlers.html`

### Control Panel config options

| Name | Type | Default | Effect |
| --- | --- | --- | --- |
| `prefix` | text | `Total` | Amount Prefix: text shown before the computed total. |

### Stored value

`process()` stores the submitted value as a plain string with two decimals (for example `25.00`), or `null` when it is not numeric. The value is computed in the browser by `total-pricing-engine.js` and is advisory: the authoritative per-line totals are recomputed by the Product and Option fieldtypes on the server. The fieldtype adds no validation rule.
