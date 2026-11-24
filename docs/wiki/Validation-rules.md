Written against v0.3.0.

The validation rules in `src/Rules/` (namespace `UAMSWeb\Aether\Fieldtypes\Rules`) and the `AssetsByFileSize` query scope. Each fieldtype attaches its own rules from its `rules()` or `extraRules()` method, so a blueprint author gets them without configuring a rule by hand. This page records what each rule checks, what it is constructed with, where its message comes from, and which fieldtype uses it. For the fieldtypes themselves, see [Fieldtypes: text and numbers](Fieldtypes-text-and-numbers.md), [Fieldtypes: choices](Fieldtypes-choices.md), [Fieldtypes: dates and times](Fieldtypes-dates-and-times.md) and [Fieldtypes: composite, media and layout](Fieldtypes-composite-media-and-layout.md).

- [Messages and overrides](#messages-and-overrides)
- [Rule summary](#rule-summary)
- [BaseValidationRule](#basevalidationrule)
- [ZipCode](#zipcode)
- [SmsShortCode](#smsshortcode)
- [Latitude and Longitude](#latitude-and-longitude)
- [InternationalPhone](#internationalphone)
- [NationalPhone](#nationalphone)
- [IdentifierRegex](#identifierregex)
- [DateBoundary](#dateboundary)
- [AssetMinimumDimensions](#assetminimumdimensions)
- [ReplicatorMinSets](#replicatorminsets)
- [ReplicatorRequireAtLeastOneEnabledSet](#replicatorrequireatleastoneenabledset)
- [FieldRequiredHelper](#fieldrequiredhelper)
- [AssetsByFileSize query scope](#assetsbyfilesize-query-scope)

## Messages and overrides

Messages that come from the add-on's translation file are read through the `aether-fieldtypes` namespace, for example `__('aether-fieldtypes::validation.zip_code')`. The file is `resources/lang/en/validation.php`. Its header states the override: publish it to `lang/vendor/aether-fieldtypes/en/validation.php` in the host application and edit the message there.

Not every rule reads its message from that file. The table below shows which ones do. A rule marked "hard-coded" builds its message in PHP, so the lang file cannot change it.

The keys in `validation.php` that are not used by a class in `src/Rules/` are:

- `month.after_or_equal` and `month.before_or_equal`, read by the `Month` fieldtype.
- `week.after_or_equal` and `week.before_or_equal`, read by the `Week` fieldtype.
- `replicator.min_sets_instructions`, the instructions text for the `min_sets` config field of the `Replicator` fieldtype. It is not a failure message.

## Rule summary

| Rule | Fails with | Used by |
| --- | --- | --- |
| `ZipCode` | `validation.zip_code` | `ZipCode` fieldtype |
| `SmsShortCode` | `validation.sms_short_code` | `Phone` fieldtype, `sms-short-code` preset |
| `Latitude` | `validation.decimal_valid`, `validation.latitude` | `Gps` fieldtype (`<handle>.lat`) |
| `Longitude` | `validation.decimal_valid`, `validation.longitude` | `Gps` fieldtype (`<handle>.lng`) |
| `InternationalPhone` | hard-coded message | `Phone` fieldtype, `tel` preset |
| `NationalPhone` | hard-coded message | `Phone` fieldtype, `tel-national` preset |
| `IdentifierRegex` | the message passed to the constructor | `Identifier` fieldtype, `regex` rules |
| `DateBoundary` | hard-coded messages | `Date` fieldtype, when a boundary is configured |
| `AssetMinimumDimensions` | hard-coded message | `Assets` fieldtype, when `min_width` or `min_height` is set on an images-only field |
| `ReplicatorMinSets` | `validation.replicator.min_sets.one`, `validation.replicator.min_sets.multiple` | `Replicator` fieldtype, when `min_sets` is greater than 0 |
| `ReplicatorRequireAtLeastOneEnabledSet` | `validation.replicator.at_least_one_enabled_set` | `Replicator` fieldtype, when the field has a required rule |

## BaseValidationRule

`BaseValidationRule` is the abstract parent of `ZipCode`, `SmsShortCode`, `Latitude` and `Longitude`. It implements Laravel's `ValidationRule` and makes `validate()` final. A child implements only `validateValue(string $attribute, string $value, Closure $fail)`.

Before the child runs, `validate()` prepares the value:

- An empty value (anything PHP treats as falsy, which includes `null`, `''`, `0` and the string `'0'`) is skipped, so a rule such as `required` decides whether emptiness is an error.
- An array value fails with the English message `The value must be a string, not an array.` This message is hard-coded and is not in the lang file.
- Any other value is cast to a string and passed to `validateValue()`.

`isValid(mixed $value): bool` is a public method that runs the same preparation and check without reporting a message. It returns `true` for an empty value and `false` for an array or a value the child rejects.

## ZipCode

Checks a U.S. ZIP Code: five digits, optionally followed by a hyphen and four digits. The pattern is `/^\d{5}(?:-\d{4})?$/D`, so no surrounding whitespace or trailing newline is accepted.

| Item | Value |
| --- | --- |
| Constructor input | None |
| Failure message | `validation.zip_code` |
| Used by | `ZipCode` fieldtype, returned from its `rules()` method |

## SmsShortCode

Checks a U.S. SMS short code: five or six digits whose first digit is `0` or `2` through `9`. The pattern is `/^(0|[2-9])\d{4,5}$/D`.

| Item | Value |
| --- | --- |
| Constructor input | None |
| Failure message | `validation.sms_short_code` |
| Used by | `Phone` fieldtype when its `preset` config is `sms-short-code` |

## Latitude and Longitude

Check a decimal coordinate. The value must be numeric (`is_numeric`), then within `-90.0` to `90.0` (`Latitude`) or `-180.0` to `180.0` (`Longitude`), both ends inclusive. Cardinal direction letters are not numeric and fail the first check.

| Item | Value |
| --- | --- |
| Constructor input | None |
| Failure message, not numeric | `validation.decimal_valid` |
| Failure message, out of range | `validation.latitude` or `validation.longitude` |
| Used by | `Gps` fieldtype, in `extraRules()`: `Latitude` on `<handle>.lat` and `Longitude` on `<handle>.lng`, each alongside `nullable` and a `required_with` rule on the other half of the pair |

## InternationalPhone

Checks that a value is a valid phone number in international form. Input is cleaned with `PhoneUtil::cleanInput()`, a leading `+` is added when missing (`PhoneUtil::addPlusIfMissing()`), and the result is checked with `Propaganistas\LaravelPhone\PhoneNumber::isValid()`. An empty value, a non-scalar value, or a value the phone library cannot parse fails. Unlike the rules above, an empty value is not skipped here: `passes()` returns `false` for it.

| Item | Value |
| --- | --- |
| Constructor input | None |
| Failure message | Hard-coded: ``Enter a valid international phone number (e.g., `+1-555-555-5555`).`` |
| Public methods | `validate()`, `passes($attribute, mixed $value): bool`, `message(): string` |
| Used by | `Phone` fieldtype when its `preset` config is `tel` |

## NationalPhone

Checks that a value is a valid phone number for one country.

- A cleaned value that starts with `+` is parsed as an international number and must be valid and belong to the configured country.
- Any other value is parsed as a national number for the configured country.
- An empty value, a non-scalar value, or a value the phone library cannot parse fails. As with `InternationalPhone`, empty is not skipped.

| Parameter | Type | Default | Effect |
| --- | --- | --- | --- |
| `$country` | `string` | `'US'` | Country code the number must be valid for |

The failure message is built in PHP: `Enter a valid national phone number for <country name> (e.g., <example>).`, or the same without the example clause when no example number is available. The country name comes from the `UAMSWeb\Aether\Enums\Country` enum (`sentenceName()`) and falls back to the code when the enum has no match. That enum is not part of this add-on.

Used by the `Phone` fieldtype when its `preset` config is `tel-national` (the default preset), with the field's `country` config (default `US`).

## IdentifierRegex

A generic regex rule that carries its own message. It exists so the `Identifier` fieldtype can turn each `regex` rule of an identifier preset into a rule with a custom failure message.

| Parameter | Type | Default | Effect |
| --- | --- | --- | --- |
| `$pattern` | `string` | none | Full PCRE pattern, delimiters included, passed to `preg_match()` |
| `$message` | `string` | none | Failure message, used as written |

An empty value (including `'0'`) is skipped. An array value is flattened by joining its scalar elements, with non-scalar elements counted as empty strings. Any other value is cast to a string and must match `$pattern`.

Used by the `Identifier` fieldtype for each of its configured rules whose `rule` is `regex`; the pattern comes from the rule's first argument and the message from the rule's `message`. A missing pattern or message becomes an empty string.

## DateBoundary

A server-side check that backs the `Date` fieldtype's boundary settings, so a scripted POST cannot store a date the picker would have blocked. It enforces:

- The earliest and latest allowed date, resolved by `UAMSWeb\Aether\Fieldtypes\Support\DateBoundaryEngine`. A boundary can be relative to another field of the same form or set (`earliest_date_field`, `latest_date_field`), and that field's submitted value is read from the validation data.
- The allowed days of the week (`days_of_week`).
- Blackout periods (`date_exceptions`).

Comparison is by calendar day. For a `time_enabled` field the day is read in the display timezone (`statamic.system.display_timezone`, then `app.timezone`, then `UTC`). A range value (`start` and `end`) is checked at both ends. An empty or unparseable value is skipped, because the date format and required rules own that case. The first violation found is reported and checking stops.

| Parameter | Type | Default | Effect |
| --- | --- | --- | --- |
| `$config` | `array<string, mixed>` | none | The date field's blueprint config |

The rule implements `DataAwareRule`, so Laravel supplies the full data bag through `setData()`.

Failure messages are hard-coded English strings, passed through `__()` as JSON-key translations rather than lang-file keys:

- `This date must be on or after :date.`
- `This date must be on or before :date.`
- `This date falls on a day of the week that is not allowed.`
- `This date falls within a period that is not available.`

Used by the `Date` fieldtype, added to its `rules()` only when a boundary is configured, so an unconstrained date field gets no extra rule.

## AssetMinimumDimensions

Checks that every selected asset meets a minimum pixel width and height, read from the asset's stored dimension metadata (`Asset::width()` and `Asset::height()`). The file is not re-read, so the check applies to an existing asset picked from a container as well as to a fresh upload that has become a stored asset.

| Parameter | Type | Default | Effect |
| --- | --- | --- | --- |
| `$minWidth` | `int` | none | Minimum width in pixels; `0` means no width minimum |
| `$minHeight` | `int` | none | Minimum height in pixels; `0` means no height minimum |
| `$containers` | `list<string>` | `[]` | Container handles used to resolve a bare-path reference to `container::path` |

An asset is skipped, never rejected, when:

- the reference does not resolve to an asset;
- the asset is an SVG;
- its width or height is not numeric or is zero or less (for example, an image the driver could not measure).

The first failing asset ends the check. The message names the file and the requirement, and is hard-coded English:

- `<file> must be at least <w> pixels wide; it is <n>.` (height minimum is 0)
- `<file> must be at least <h> pixels tall; it is <n>.` (width minimum is 0)
- `<file> must be at least <w>x<h> pixels; it is <w>x<h>.` (both set)

Used by the `Assets` fieldtype. It adds the rule when `min_width` or `min_height` is greater than 0 and the field is restricted to images only. The `min_width` and `min_height` config fields are shown in the field's settings only when the file type restriction is not "all".

## ReplicatorMinSets

Checks that a Replicator value holds at least `min_sets` sets, counting disabled sets too. It runs only when the field is required for the current request (see [FieldRequiredHelper](#fieldrequiredhelper)). A value that is not an array is skipped. A `min_sets` that is unset, zero or negative is met by any list.

| Parameter | Type | Default | Effect |
| --- | --- | --- | --- |
| `$fieldConfig` | `array<string, mixed>` | none | Field config; reads `min_sets`, `validate` and any condition keys |

Failure messages: `validation.replicator.min_sets.one` when the minimum is 1 (`At least one set is required.`), otherwise `validation.replicator.min_sets.multiple` with a `:min` replacement (`At least :min sets are required.`).

Used by the `Replicator` fieldtype, added only when its `min_sets` config is scalar and greater than 0.

## ReplicatorRequireAtLeastOneEnabledSet

Checks that a required Replicator holds at least one set that is enabled. A set counts as enabled unless its `enabled` key is exactly `false`; a set with no `enabled` key counts as enabled. A value that is not an array is skipped, and so is a request where the field is not required.

| Parameter | Type | Default | Effect |
| --- | --- | --- | --- |
| `$fieldConfig` | `array<string, mixed>` | none | Field config; reads `validate` and any condition keys |

Failure message: `validation.replicator.at_least_one_enabled_set` (`At least one set must be enabled.`).

Used by the `Replicator` fieldtype, added whenever its `validate` config contains a rule that starts with one of the required prefixes.

## FieldRequiredHelper

`FieldRequiredHelper` is a static helper, not a rule. The two Replicator rules use it, and the `Replicator` fieldtype uses `configHasRequiredRule()` to decide whether to attach the enabled-set rule.

- `configHasRequiredRule(array $fieldConfig): bool` is true when any rule in the field's `validate` list starts with one of `FieldRequiredHelper::REQUIRED_RULE_PREFIXES`: `required`, `required_if`, `required_unless`, `required_if_accepted`, `required_if_declined`, `required_with`, `required_with_all`, `required_without`, `required_without_all`, `required_array`.
- `fieldIsRequiredForRequest(array $fieldConfig, array $data, string $attribute): bool` is true when the required rules fail against an empty value for the attribute. When `validate` contains `sometimes` and the field's conditions are not met, it is false.
- `sometimesConditionMet()` reads the condition keys in `FieldRequiredHelper::CONDITION_KEYS` (`if`, `if_any`, `show_when`, `show_when_any`, `unless`, `unless_any`, `hide_when`, `hide_when_any`) against the values beside the attribute. With no condition keys the condition is met.

Evaluating a condition needs `UAMSWeb\Aether\Forms\Settings\Support\ConditionalLogicEvaluator`. Without that class, the helper throws a `LogicException` that names the `uams-web/aether-form-settings` add-on. The evaluator is consulted only when `validate` contains `sometimes` and the config carries a condition key.

## AssetsByFileSize query scope

`UAMSWeb\Aether\Fieldtypes\Scopes\AssetsByFileSize` is a Statamic query scope with the handle `assets_by_file_size`. It filters an asset query by the asset's `size` column, in bytes: `size >= min` and `size <= max`. With no bound present it does nothing.

The bounds travel in the scope handles themselves, because the handle list is the only transport the asset browser carries. A handle of the form `assets_by_file_size_min_<bytes>` sets the minimum and `assets_by_file_size_max_<bytes>` sets the maximum. `<bytes>` must be digits only and must fit a PHP integer; any other fragment is ignored. If several handles of one kind are present, the tightest bound wins (the largest minimum, the smallest maximum). The encoded handles are deliberately not registered as aliases.

Normal use needs no code. The `Assets` fieldtype writes the handles into the field's `query_scopes` config when the field has `min_file_size` or `max_file_size` set. Those config values are in megabytes (decimal, 1 MB is 1,000,000 bytes) and become bytes in the handle. For example, a minimum of `1` and a maximum of `10` produce:

```php
['assets_by_file_size', 'assets_by_file_size_min_1000000', 'assets_by_file_size_max_10000000']
```

The same `min_file_size` and `max_file_size` values are also enforced on save by the `Assets` fieldtype's own validation rules (`min`, `max` or `between`, in kilobytes), and the scope's own docblock calls that server-side check the authoritative one. The scope only narrows what the asset browser lists.

## Internal helper

`UAMSWeb\Aether\Fieldtypes\Services\PhoneUtil` is a static helper used by the `Phone` fieldtype and the two phone rules (input cleaning, display formatting, example numbers). It is not a documented extension point and is not covered on this page.
