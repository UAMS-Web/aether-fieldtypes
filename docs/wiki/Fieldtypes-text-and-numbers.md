Written against v0.3.0.

This page documents the single-value text and number fieldtypes in `src/Fieldtypes/`: Text, Email, URL, Password, Username, Hidden, Identifier, CSS Selector, XPath, Mask Field, ZIP Code, Phone, Integer, Decimal Number (`Floatval`) and Currency. For each one it gives the handle, whether the fieldtype can be picked in form blueprints, the Control Panel options it leaves editable, the value it stores, the validation it adds and the public form partial that renders it. The behavior shared by most of them (the options and the input mask) is described once in the first section.

- [Shared behavior of the input fieldtypes](#shared-behavior-of-the-input-fieldtypes)
- [Text](#text)
- [Email](#email)
- [URL](#url)
- [Password](#password)
- [Username](#username)
- [Hidden](#hidden)
- [Identifier](#identifier)
- [CSS Selector](#css-selector)
- [XPath](#xpath)
- [Mask Field](#mask-field)
- [ZIP Code](#zip-code)
- [Phone](#phone)
- [Integer](#integer)
- [Decimal Number (Floatval)](#decimal-number-floatval)
- [Currency](#currency)

## Shared behavior of the input fieldtypes

Every fieldtype on this page except Mask Field uses the `Input` trait (`src/Traits/Input.php`). The trait supplies the Control Panel config schema, the value processing pipeline and the preload data sent to the Vue component. Handles are derived by Statamic from the class name in snake case unless the class (or a parent) sets `$handle`; none of the add-on classes sets one. Where a class extends a Statamic core fieldtype (Text, Hidden, Integer, Floatval), it keeps the core handle.

### Config schema and hidden options

The trait defines one config schema of HTML input attributes (for example `placeholder`, `minlength`, `maxlength`, `pattern`, `min`, `max`, `step`, `inputmode`, `autocomplete`, `spellcheck`), Statamic options (`prepend`, `append`, `antlers`), a dynamic default value group and an input mask group. Each fieldtype then:

- overrides defaults and instructions through `getConfigFieldOverrides()`;
- turns options it does not want into hidden fields (`getHiddenConfigFields()`): a hidden option is not editable in the Control Panel and keeps its default;
- optionally removes options entirely (`getUnsetConfigFields()`; every fieldtype here that defines it returns an empty list);
- optionally adds options (`getAdditionalConfigFields()`) and a preset selector (`getPresets()`).

Options are grouped into Control Panel sections in a fixed order: Input Behavior, Input Identification, Input Value and State, Form Association and Submission, Input Mask, Constraints, Autocomplete and Suggestions, Accessibility, Appearance, Microdata, Advanced Features, Experimental and Limited Availability, Antlers. Many options carry an `if` condition on `input_type` (a hidden option set from each fieldtype's `input_type` default), so an option only shows for the input types it applies to.

The "Editable options" lists below name the options a fieldtype leaves visible. They are subject to the `if` conditions of the shared schema, summarized here instead of repeated per fieldtype:

- `placeholder` shows only when the dynamic default value (`calculation_query`) is off.
- `default` shows only when `calculation_query` is off.
- `autocomplete` shows only when `autocomplete_query` is `on`; `autocapitalize_setting` shows only when `autocapitalize` is `on`.
- `transform_case` shows only when `input_mask` is off.
- `minlength`, `maxlength` and `pattern` apply to the `email`, `password`, `search`, `tel`, `text` and `url` input types; `min`, `max` and `step` apply to `date`, `datetime-local`, `month`, `number`, `range`, `time` and `week`.

### Dynamic default value

The `calculation_query` toggle ("Enable Dynamic Default Value") opts a field into the public-form calculation engine. When it is on, the form partials render `data-calculation-type`, `data-calculation-formula` and `data-calculation-decimals` attributes and mark the input read-only. `calculation_type` is `math` or `text`; the formula is entered in `calculation_formula_math` or `calculation_formula_text` (the `calculation_formula` code field itself is hidden), and `calculation_decimals` ("Decimal Places of Calculated Value") applies to math. This group is documented here only as far as the code shows; the engine that consumes the attributes is not part of this page.

### Input mask

Where `input_mask` is editable, it is a toggle (default `false`) that enables IMask-based masking in the Control Panel and form field. With it on:

- `input_mask_class` selects the mask type: `MaskedDate`, `MaskedDynamic`, `MaskedEnum`, `MaskedNumber`, `MaskedPattern` (default), `MaskedRange`, `MaskedRegExp` or `RepeatBlock`.
- `input_mask_mask_MaskedPattern`, `input_mask_mask_MaskedRegExp` and `input_mask_mask_MaskedDynamic` hold the mask definition for the matching class.
- `input_mask_store_unmasked` (default `false`) chooses whether the stored value is the formatted ("masked") text or the raw ("unmasked") text.
- `input_mask_prepend_text_enabled` / `input_mask_prepend_text` and `input_mask_append_text_enabled` / `input_mask_append_text` add display text before or after the masked value.
- The remaining `input_mask_*` options expose IMask settings (for example `blocks`, `definitions`, `eager`, `lazy`, `overwrite`, `placeholderChar`, `prepare_case`, `radix`, `scale`, `skipInvalid`, `thousandsSeparator`, `min`, `max`), each shown for the mask classes it applies to.

### Value processing

`process()` and `preProcessValidatable()` both call one routine (`ProcessesInputValue::processValueWithConfig()`), so a value is normalized the same way on save and before validation:

1. The field config is merged over the defaults from the fieldtype's overrides, additional fields and default preset.
2. If `input_mask` is exactly `true`, the value goes through the input mask path. When the Vue component sends a structure with both `masked` and `unmasked` keys, the `unmasked` text is stored if `input_mask_store_unmasked` is `true`, and the `masked` text otherwise. A plain (non-structured) value is stored as given. When `input_mask_class` is `MaskedNumber` or `MaskedRange` and the chosen value is a numeric string, it is converted to a float.
3. Otherwise the value goes to `normalizeValue()`, which returns the value unchanged unless a fieldtype overrides it. Of the fieldtypes on this page, only ZIP Code and Phone override it.

Because the trait's `process()` replaces the parent's, Integer and Decimal Number do not cast the stored value to a number in this add-on's own code; they rely on the validation rules listed in their sections.

### `pattern` and server-side validation

The shared trait has a helper, `getInputValidationRules()`, that turns a configured `pattern` into a `regex:` rule. No fieldtype class in `src/Fieldtypes/` calls it from `rules()`, so on the fieldtypes below `pattern`, `minlength` and `maxlength` act through the rendered HTML attributes and the Control Panel component, not through a server-side rule added by the add-on. Server-side rules are the ones named under "Validation" in each section.

## Text

Single-line text input that extends Statamic's core `Text` fieldtype and adds the shared input options, input mask, dynamic default value and case transform.

- Handle: `text` (same as the core fieldtype). Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Text`.
- Selectable in form blueprints: yes (inherited `$selectableInForms = true` from Statamic's `Text`).
- Control Panel component: `custom_text`. Icon: `form-text-field`, taken from the parent.
- `input_type` default: `text`; `calculation_type` default: `text`.
- Editable options: `placeholder`, `default`, `prepend`, `append`, `antlers`, `minlength`, `maxlength`, `autocapitalize` and `autocapitalize_setting`, `autocomplete_query` and `autocomplete`, `autocorrect`, `inputmode`, `spellcheck`, `translate`, `virtualkeyboardpolicy`, `writingsuggestions`, `debounce` (stored value updates after typing pauses instead of on every keystroke; the code comments describe it as used only by this fieldtype), `transform_case`, the dynamic default value group and the whole input mask group. `minlength`, `maxlength`, `autocapitalize`, `autocorrect` and `inputmode` reach the Control Panel input and the form partial; up to v0.3.0 the Control Panel offered them and neither applied them, and the change ships with the next release. `autocomplete_query` only decides whether `autocomplete` shows and is not passed on to the input.
- Stored value: the text entered. With the input mask on, the masked or unmasked text as described in [Value processing](#value-processing).
- Validation: `rules()` returns an empty list; the add-on adds no rule of its own.
- Form partial: `resources/views/forms/fields/text.antlers.html`. It renders an `<input>` whose `type` comes from `input_type` (default `text`), and emits `placeholder`, `maxlength` (from `character_limit`, else from `maxlength`, once), `minlength`, `autocomplete`, `autocapitalize`, `autocorrect` and `inputmode` attributes (the last three only when set to something other than `null`), the calculation attributes when `calculation_query` is on, `readonly` for `visibility: read_only`, and `required` when the field's `validate` contains `required`.

```yaml
-
  handle: nickname
  field:
    type: text
    display: Nickname
    placeholder: 'e.g., Sam'
    maxlength: 40
```

## Email

Email address input.

- Handle: `email`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Email`. Title: Email. Icon: `mail-sign-at`.
- Selectable in form blueprints: yes.
- Control Panel component: `email`.
- Defaults: `input_type` `email`, `autocomplete_query` `on`, `autocomplete` `email` (the only allowed autocomplete value), `autocapitalize` `off`, `autocorrect` `off`, `inputmode` `email`, `spellcheck` `off`, `writingsuggestions` `off`, `calculation_type` `text`.
- Editable options: `placeholder`, `multiple`, `transform_case`, the dynamic default value group and the input mask group. The other options (including `default`, `minlength`, `maxlength`, `pattern`, `autocomplete` and `prepend`/`append`) are hidden.
- Stored value: the text entered (or masked/unmasked text with an input mask).
- Validation: `rules()` returns `['email']`, Laravel's `email` rule.
- Form partial: `resources/views/forms/fields/email.antlers.html`, which delegates to the shared `default.antlers.html` partial (input type from `input_type`, plus `inputmode`, `min`, `max`, `minlength`, `maxlength`, `pattern`, `step`, `spellcheck` and `data-transform-case` attributes when set).

```yaml
-
  handle: contact_email
  field:
    type: email
    display: Contact email
    placeholder: 'name@example.com'
```

## URL

Web address input.

- Handle: `url`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Url`. Title: URL. Icon: `web`.
- Selectable in form blueprints: yes.
- Control Panel component: `url`.
- Defaults: `input_type` `url`, `autocapitalize` `off`, `autocorrect` `off`, `inputmode` `url`, `spellcheck` `off`, `writingsuggestions` `off`, `calculation_type` `text`. The only allowed autocomplete value is `url`.
- Editable options: `placeholder`, `default`, `transform_case`, the dynamic default value group and the input mask group.
- Stored value: the text entered (or masked/unmasked text with an input mask).
- Validation: `rules()` returns `['url']`, Laravel's `url` rule.
- Form partial: `resources/views/forms/fields/url.antlers.html`, which delegates to `default.antlers.html`.

## Password

Masked single-line input.

- Handle: `password`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Password`. Title: Password. Icon: `security-lock`.
- Selectable in form blueprints: yes.
- Control Panel component: `password`.
- Defaults: `input_type` `password`, `autocorrect` `off`, `spellcheck` `off`, `writingsuggestions` `off`, `calculation_type` `text`. Allowed autocomplete values: `current-password` and `new-password`.
- Editable options: `placeholder`, `minlength`, `maxlength`, `pattern`, `autocomplete_query` and `autocomplete`, `transform_case`, the dynamic default value group and the input mask group. The instructions for `minlength`, `maxlength` and `pattern` are specific to passwords.
- Stored value: the value as entered. The class contains no hashing or encryption code, so what is stored depends on what consumes the submission.
- Validation: the class defines no `rules()`, so the add-on adds none.
- Form partial: `resources/views/forms/fields/password.antlers.html`, which delegates to `default.antlers.html` (the rendered input type is `password`).

## Username

Account or handle input with username autocomplete semantics.

- Handle: `username`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Username`. Title: Username. Icon: `user-avatar`.
- Selectable in form blueprints: yes.
- Control Panel component: `custom_text` (shared with Text).
- Defaults: `input_type` `text`, `autocomplete_query` `on`, `autocomplete` `username` (the only allowed value), `autocapitalize` `off`, `autocorrect` `off`, `spellcheck` `off`, `writingsuggestions` `off`, `calculation_type` `text`.
- Editable options: `placeholder`, `default`, `minlength`, `maxlength`, `pattern`, `transform_case`, the dynamic default value group and the input mask group. The instructions for `pattern` suggest `^[a-zA-Z0-9_.-]{3,20}$` as an example.
- Stored value: the text entered (or masked/unmasked text with an input mask).
- Validation: the class defines no `rules()`, so the add-on adds none.
- Form partial: `resources/views/forms/fields/username.antlers.html`, which delegates to `default.antlers.html`.

## Hidden

A value that is not shown to the person filling in a form. It extends Statamic's core `Hidden` fieldtype.

- Handle: `hidden`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Hidden`. Icon: taken from the parent.
- Selectable in form blueprints: yes.
- Control Panel component: `hidden`.
- Defaults: `input_type` `hidden`. The `default` option is overridden to type `integer` (set in `getConfigFieldOverrides()`).
- Editable options: `default`, `antlers` and the dynamic default value group (`calculation_query`, `calculation_type`, the formula options and `calculation_decimals`). Everything else is hidden, and no input mask options are shown.
- Stored value: the value as submitted or defaulted.
- Validation: `rules()` returns an empty list; the class states that Hidden carries no content validation rules.
- Form partial: `resources/views/forms/fields/hidden.antlers.html`, which renders `<input type="hidden">` with the field `id`, `name` (the field handle) and a sanitized `value`. Its comment states that Statamic's field wrapper skips the label, instructions and error markup for `type == 'hidden'`.

## Identifier

A text field for a standardized identifier (an NPI, an ORCID iD, a D-U-N-S number and similar). The author picks an identifier type in a required preset selector, and the preset supplies the input mask, case transform, placeholder and validation rule.

- Handle: `identifier`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Identifier`. Title: Identifier. Icon: `form-text-field`.
- Selectable in form blueprints: no (the class does not set `$selectableInForms`, and the core default is `false`). There is no `identifier.antlers.html` partial.
- Control Panel component: `identifier`.
- Defaults: `input_type` `text`, `input_mask` `true`, `input_mask_class` `MaskedPattern`, `calculation_type` `text`.
- Preset selector: `preset`, labeled "Identifier Type" ("Choose the type of identifier for this field."), a required select with no default preset. Its options are listed below. A preset sets defaults for the mask, `transform_case` and `placeholder` and hides those options.
- Editable options: `preset`, `default` and the dynamic default value group. Placeholder, input mask, case transform, `pattern` and the rest are hidden.
- Stored value: the text entered. With the input mask on, the masked or unmasked text as described in [Value processing](#value-processing).
- Validation: `rules()` (and `getValidationRules()`) return the validation rules of the selected preset, built from its `validation_rules` entries. Each preset names one rule, for example `npi`, as a plain Laravel rule name. The add-on does not register these names: a host application must register each name it uses, and a name that is not registered fails when validation runs. The add-on ships `UAMSWeb\Aether\Fieldtypes\Rules\IdentifierRegex`, which the rule builder uses for `regex`-type entries; no preset in this version defines one. See [Validation rules](Validation-rules.md).
- Search keywords: the base list plus the keywords of every preset.

Presets (the key is the value stored in the field config as `preset`):

| Preset key | Rule name | Mask and case |
| --- | --- | --- |
| `ahaid` | `ahaid` | digits only (regular expression mask) |
| `cms_specialty_code` | `cms_specialty_code` | no mask |
| `epic_dep_id` | `epic_dep_id` | digits only |
| `duns_number` | `duns_number` | digits only |
| `global_location_number` | `global_location_number` | digits only |
| `cage_code` | `cage_code` | `0-9` and `A-Z`, uppercase |
| `ipeds_id` | `ipeds_id` | digits only |
| `ope_id` | `ope_id` | digits only |
| `uei` | `uei` | `0-9` and `A-Z`, uppercase |
| `google_business_profile_cid` | `google_business_profile_cid` | digits only |
| `google_scholar_author_id` | `google_scholar_author_id` | no mask |
| `google_tag_manager_container_id` | `google_tag_manager_container_id` | no mask, uppercase |
| `health_care_provider_taxonomy_code` | `health_care_provider_taxonomy_code` | no mask, uppercase |
| `isic_rev_4_code` | `isic_rev4_code` | no mask, uppercase |
| `isco_08_code` | `isco_08_code` | digits only |
| `iso_6523_code` | `iso_6523_code` | pattern `0000:#[...][:...]`, eager, uppercase on prepare |
| `legal_entity_identifier` | `legal_entity_identifier` | no mask, uppercase |
| `naics_code` | `naics_code` | digits only |
| `npi` | `npi` | digits only |
| `onet_soc_occupation_code` | `onet_soc_occupation_code` | no mask |
| `orcid` | `orcid` | pattern `0000-0000-0000-000#` (last character digit or `X`), uppercase on prepare |
| `pmid` | `pmid` | digits only |
| `pubmed_author_id` | `pubmed_author_id` | no mask, lowercase |
| `researcherid` | `researcher_id` | pattern `a[aa]-0000-0000`, uppercase on prepare |
| `rxcui` | `rxcui` | digits only |
| `scopus_author_id` | `scopus_author_id` | digits only |
| `taxpayer_identification_number` | `taxpayer_identification_number` | pattern `00-0000000` |
| `ticker_symbol` | `ticker_symbol` | no mask, uppercase |
| `va_station_id` | `va_station_id` | digits (regular expression mask `/^\d+/`) |
| `va_substation_id` | `va_substation_id` | no mask, uppercase |
| `va_station_substation_id` | `va_station_substation_id` | no mask, uppercase |

Each preset also sets a `placeholder` such as `e.g., 1234567890` for `npi`; the exact text is in `getPresetDefinitions()`.

```yaml
-
  handle: provider_npi
  field:
    type: identifier
    display: NPI
    preset: npi
```

## CSS Selector

A text field for a CSS selector, validated for syntax.

- Handle: `css_selector`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\CssSelector`. Title: CSS Selector. Icon: `code-inline`.
- Selectable in form blueprints: no (`$selectableInForms = false`). The class also sets `$selectable = false`, so it is not offered in the Control Panel fieldtype picker either. There is no form partial.
- Control Panel component: `css_selector`.
- Defaults: `input_type` `text`, `autocapitalize` `off`, `autocorrect` `off`, `inputmode` `text`, `spellcheck` `off`, `writingsuggestions` `off`, `calculation_query` `false`. No autocomplete values are allowed.
- Editable options: `placeholder`, `default`. The input mask group and the dynamic default value toggle are hidden.
- Stored value: the text entered.
- Validation: `rules()` returns `['css_selector_syntax']`. The add-on does not register that rule name; the host must register it. See [Validation rules](Validation-rules.md).

## XPath

A text field for an XPath expression, validated for syntax. It mirrors CSS Selector.

- Handle: `xpath`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Xpath`. Title: XPath. Icon: `code-inline`.
- Selectable in form blueprints: no (`$selectableInForms = false`). The class also sets `$selectable = false`. There is no form partial.
- Control Panel component: `xpath`.
- Defaults: the same as CSS Selector.
- Editable options: `placeholder`, `default`.
- Stored value: the text entered.
- Validation: `rules()` returns `['xpath_syntax']`. The add-on does not register that rule name; the host must register it. See [Validation rules](Validation-rules.md).

## Mask Field

A base class for fields whose only configuration is an input mask and a case transform. It does not use the `Input` trait and ships no preset of its own; a subclass can supply presets.

- Handle: `mask_field`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\MaskField`. Icon: `form-text-field`.
- Selectable in form blueprints: no (`$selectableInForms = false`). There is no form partial.
- Control Panel component: `mask_field`.
- Options: `placeholder` (text); `input_mask` (text, for example `000-000-0000`, or a regular expression wrapped in `/`; applied with IMask), with instructions describing the mask characters (`0` digit, `a` letter, `#` letter or digit, `*` any character, `[]` optional, `{}` fixed but kept in the value); and `transform_case` (radio: `none`, `uppercase`, `lowercase`; default `none`).
- Stored value: `process()` and `preProcess()` return the value unchanged. The default value is an empty array.
- Subclass presets: if a subclass declares a `presetInputMask` or `presetTransformCase` property that is not `null`, that value becomes the default of the matching option, and `process()` and `preProcess()` merge both values into the config (with `input_mask` defaulting to an empty string and `transform_case` to `none`) and record them under `_preset_fields`. The helpers `hideConfigFields()`, `createSimplifiedConfig()` and `hidePresetFields()` let a subclass remove options, keep a chosen subset, or hide the preset-controlled options.
- Validation: none defined.

## ZIP Code

U.S. ZIP Code input (five digits, or ZIP+4).

- Handle: `zip_code`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\ZipCode`. Title: ZIP Code. Icon: `form-address-field`.
- Selectable in form blueprints: no (the class does not set `$selectableInForms`). There is no `zip_code` form partial.
- Control Panel component: `zip_code`.
- Defaults: `input_type` `text`, `inputmode` `numeric`, `spellcheck` `false`, `placeholder` `e.g., 12345 or 12345-6789`, `input_mask` `true`, `input_mask_mask_MaskedPattern` `00000[-0000]`, `calculation_type` `text`. The only allowed autocomplete value is `postal-code`.
- Editable options: `default` and the dynamic default value group. The placeholder and the input mask options are hidden, so the mask and placeholder are fixed unless set in the blueprint YAML.
- Stored value: the text entered. With the input mask on (its default here), `process()` takes the input mask path and the class's own `normalizeValue()`, which trims whitespace, is not called; it applies only when `input_mask` is turned off.
- Validation: `rules()` returns `UAMSWeb\Aether\Fieldtypes\Rules\ZipCode`, which accepts `/^\d{5}(?:-\d{4})?$/D` and skips empty values. The failure message is `aether-fieldtypes::validation.zip_code`. See [Validation rules](Validation-rules.md).

## Phone

Telephone input with three formats chosen by a preset.

- Handle: `phone`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Phone`. Title: Phone. Icon: `phone-telephone-call`.
- Selectable in form blueprints: yes.
- Control Panel component: `phone_input`.
- Defaults: `input_type` `tel`, `inputmode` `tel`, `spellcheck` `false`, `calculation_type` `text`. Allowed autocomplete values: `tel` and `tel-national`.
- Preset selector: `preset`, "Phone Format", required, default `tel-national`:
  - `tel` ("International (with country code)"): sets `autocomplete_query` `on`, `autocomplete` `tel`, a `pattern`, an example placeholder and `parse_as_phone` `true`.
  - `tel-national` ("National (no country code)"): sets `autocomplete_query` `on`, `autocomplete` `tel-national`, a `pattern`, an example placeholder (replaced at preload time by an example number for the selected country) and `parse_as_phone` `true`.
  - `sms-short-code` ("U.S. SMS Short Code"): sets `autocomplete_query` `off`, the placeholder `e.g., 01234 or 234567`, `maxlength` `6`, `minlength` `5` and `parse_as_phone` `false`.
  A preset's values become the defaults of the matching options, and those options are hidden.
- `country`: a select of countries (keys are alpha-2 codes, values are country names from the `UAMSWeb\Aether\Enums\Country` enum, which is not part of this tree), default `US`. It is shown only for the `tel-national` preset.
- Editable options: `preset`, `country`, `placeholder`, `default` and the dynamic default value group (preset-controlled options are hidden). The input mask options are hidden.
- Stored value:
  - `tel` and `tel-national`: an E.164 string (for example `+15015550123`), produced by `Propaganistas\LaravelPhone\PhoneNumber`. `tel` adds a leading `+` when it is missing; `tel-national` parses with the selected country. If parsing throws, the original value is returned and an error is logged.
  - `sms-short-code`: the input cleaned by `PhoneUtil::cleanInput()`.
  - An empty value, or one that is only `0`, is stored as an empty string.
- Display: `formatValueForDisplay()` formats `tel` values as international and `tel-national` as national; short codes are returned as is.
- Validation: `rules()` returns `InternationalPhone` for `tel`, `NationalPhone` (given the configured country, default `US`) for `tel-national`, and `SmsShortCode` for `sms-short-code`. An unknown preset adds no rule and logs a warning. See [Validation rules](Validation-rules.md).
- Form partial: `resources/views/forms/fields/phone.antlers.html`, which delegates to `default.antlers.html`; the preset's `autocomplete`, `pattern`, `placeholder`, `minlength` and `maxlength` are emitted there.

```yaml
-
  handle: office_phone
  field:
    type: phone
    display: Office phone
    preset: tel-national
    country: US
```

## Integer

Whole-number input. It extends Statamic's core `Integer` fieldtype.

- Handle: `integer`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Integer`. Title: Integer (from the parent). Icon: taken from the parent.
- Selectable in form blueprints: yes (inherited `$selectableInForms = true` from Statamic's `Integer`).
- Control Panel component: `custom_integer`.
- Defaults: `input_type` `number`, `inputmode` `numeric`, `calculation_type` `math`, `calculation_decimals` `0`. The `default`, `min`, `max` and `step` options are integer-typed.
- Allowed autocomplete values: `bday-day`, `bday-month`, `bday-year`, `tel`, `tel-country-code`, `tel-national`, `tel-area-code`, `tel-local`, `tel-local-prefix`, `tel-local-suffix`, `tel-extension`, `postal-code`, `cc-number`, `cc-exp-month`, `cc-exp-year`, `cc-csc` and `one-time-code`.
- Editable options: `min`, `max`, `step`, `placeholder`, `default`, `prepend`, `append`, `autocomplete_query`, `autocomplete` and the dynamic default value group (`calculation_decimals` is hidden).
- Stored value: the add-on's value pipeline returns the submitted value unchanged. No cast to an integer happens in this class's own code.
- Validation: `rules()` returns `integer`, plus `between:<min>,<max>` when both `min` and `max` are set, `min:<min>` when only `min` is set, or `max:<max>` when only `max` is set. An empty string counts as not set.
- Form partial: `resources/views/forms/fields/integer.antlers.html`, which renders `<input type="number">` with `min`, `max`, `step`, `placeholder`, `autocomplete` and the calculation attributes. Its comment states that server-side `Integer::rules()` remains the authority.

```yaml
-
  handle: party_size
  field:
    type: integer
    display: Party size
    min: 1
    max: 20
```

## Decimal Number (Floatval)

Decimal number input. It extends Statamic's core `Floatval` fieldtype, whose own `$handle` is `float`, so the add-on class keeps the handle `float`.

- Handle: `float`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Floatval`. Title: Decimal Number. Icon: taken from the parent.
- Selectable in form blueprints: yes.
- Control Panel component: `custom_floatval`.
- Defaults: `input_type` `number`, `inputmode` `numeric`, `calculation_type` `math`. The `default`, `min`, `max` and `step` options are float-typed. The only allowed autocomplete value is `transaction-amount`.
- Search keywords: the parent's plus `float`, `number`, `numeric`, `decimal` and `floatval`.
- Editable options: `min`, `max`, `step`, `placeholder`, `default`, `prepend`, `append`, `autocomplete_query`, `autocomplete` and the dynamic default value group including `calculation_decimals`.
- Stored value: the submitted value unchanged, as for Integer.
- Validation: `rules()` returns `numeric`, plus `between`, `min` or `max` from the configured bounds exactly as Integer does.
- Form partial: `resources/views/forms/fields/float.antlers.html`, which delegates to `default.antlers.html` (the partial name follows the `float` handle).

## Currency

Monetary amount input with a currency chosen in the config. The currency symbol is shown through the input mask's prepend or append text.

- Handle: `currency`. Class: `UAMSWeb\Aether\Fieldtypes\Fieldtypes\Currency`. Title: Currency. Icon: `money-cash-bill`.
- Selectable in form blueprints: no (the class does not set `$selectableInForms`). There is no `currency` form partial.
- Control Panel component: `currency`.
- `currency_type`: a required select whose options are the cases of the `UAMSWeb\Aether\Enums\Currency` enum (not part of this tree), keyed by the enum value, default `USD`.
- Defaults: `input_type` `text`, `inputmode` `decimal`, `spellcheck` `false`, `writingsuggestions` `false`, `autocomplete_query` `off`, `calculation_type` `math`, and a preconfigured number mask: `input_mask` `true`, `input_mask_class` `MaskedNumber`, `input_mask_store_unmasked` `true`, `input_mask_eager` `false`, `input_mask_normalizeZeros` `true`, `input_mask_overwrite` `false`, `input_mask_padFractionalZeros` `true`, `input_mask_radix` `.`, `input_mask_skipInvalid` `true`, `input_mask_thousandsSeparator` `,`. The `default` option is float-typed.
- Editable options: `currency_type`, `placeholder`, `default`, the dynamic default value group (without `calculation_decimals`), `input_mask_store_unmasked`, `input_mask_min` and `input_mask_max`. Both bounds are float-typed.
- Preload behavior: when `currency_type` is a valid enum value, the config sent to the component sets `prepend` to the currency code, sets `input_mask_scale` to the currency's minor unit (for example `0` for JPY), and, if the currency has a symbol, enables prepended text (placement `before`) or appended text (placement `after`), with a separating space when the enum says so. When `calculation_formula` is exactly `true`, `calculation_decimals` is set to the minor unit. An invalid `currency_type` leaves these values unset. The config also carries the field `handle`, and the preload result includes `fieldtype_handle`.
- Stored value: because `input_mask_store_unmasked` defaults to `true` and the mask class is `MaskedNumber`, a numeric unmasked value is stored as a float (for example `1234.5`). With `input_mask_store_unmasked` off, the masked text (for example `1,234.50`) is stored; a numeric string is converted to a float, and a string with a thousands separator is not numeric, so it stays text.
- Validation: the class defines no `rules()`, so the add-on adds none.

```yaml
-
  handle: registration_fee
  field:
    type: currency
    display: Registration fee
    currency_type: USD
    input_mask_min: 0
```
