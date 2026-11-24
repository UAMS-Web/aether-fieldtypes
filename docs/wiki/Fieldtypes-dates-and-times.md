Written against v0.3.0.

This page covers the date and time fieldtypes the add-on ships: `date` (an extension of the Statamic core date fieldtype), its Control Panel helper `date_field_select`, `partial_date`, `month`, `week` and `time`. For each it lists the handle, where it can be selected, the Control Panel config options, the value that is stored, the validation it adds and the form partial that renders it. The server rules behind the date boundary options are summarized here and described further in [Validation rules](Validation-rules.md); the public form markup is described in [Form field partials](Form-field-partials.md).

- [Date (`date`)](#date)
- [DateFieldSelect (`date_field_select`)](#datefieldselect)
- [PartialDate (`partial_date`)](#partialdate)
- [Month (`month`)](#month)
- [Week (`week`)](#week)
- [Time (`time`)](#time)

## Date

`UAMSWeb\Aether\Fieldtypes\Fieldtypes\Date` extends `Statamic\Fieldtypes\Date`. It keeps the core date picker, range mode, time option and save formats, and adds earliest/latest date boundaries, an allowed-weekday filter, blackout dates and a choice of how datetimes are stored. The class does not set `$handle`, so Statamic derives the handle `date` from the class name. It is selectable in form blueprints (`$selectableInForms = true`).

### Config options added by this add-on

Every option the core `date` fieldtype defines (for example `mode`, `time_enabled`, `time_seconds_enabled` and `format`) is inherited unchanged and is not repeated here. The add-on injects its own fields into the existing sections of the core config.

Section "Boundaries & Limits" (the options before the core options in that section):

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `earliest_date_type` | radio: `none`, `custom`, `today`, `field` | `none` | How the earliest allowed date is determined. Required. |
| `earliest_date_custom` | date | none | The earliest allowed date. Shown and required when type is `custom`. |
| `earliest_date_field` | `date_field_select` | none | Handle of another `date` field in the same blueprint. Shown and required when type is `field`. |
| `earliest_date_field_offset` | grid, one row | none | Offset applied to the referenced field's date. Shown when type is `field`. |
| `earliest_date_exclude_before_today` | toggle | `false` | Also excludes dates before the current date. Shown when type is `custom` or `field`. |
| `earliest_date_today_offset` | grid, one row | none | Offset applied to the current date. Shown when type is `today`, or when `earliest_date_exclude_before_today` is on. |
| `latest_date_type` | radio: `none`, `custom`, `today`, `field` | `none` | How the latest allowed date is determined. Required. |
| `latest_date_custom` | date | none | The latest allowed date. Shown and required when type is `custom`. |
| `latest_date_field` | `date_field_select` | none | Handle of another `date` field in the blueprint. Shown and required when type is `field`. |
| `latest_date_field_offset` | grid, one row | none | Offset applied to the referenced field's date. Shown when type is `field`. |

The two subsections `earliest_date_section` and `latest_date_section` are headings only. The Control Panel labels the type choices "None", "Custom Date", "Current Date" and "Date From a Form Field".

Each offset grid has a single row with three columns: `operator` (`add` or `subtract`, default `add`), `number` (integer, default 0, required, minimum 1) and `unit` (`day`, `week`, `month` or `year`, default `day`). A row with a zero, negative or non-numeric number, or an unknown unit, has no effect.

Section "Boundaries & Limits" (after the core options):

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `days_of_week` | checkboxes, keys `0` (Sunday) to `6` (Saturday) | all seven | The weekdays that may be picked. Restricting to fewer than seven turns the boundary feature on. |
| `date_exceptions` | replicator | none | Blackout dates. Sets: `single_date` (one `date` field `item`) and `date_range` (one `date` field `item` in `range` mode). Ranges are inclusive. |

Section "Date & Time":

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `datetime_storage` | select: `convert`, `literal` | `convert` | Shown only when `time_enabled` is on. See [Datetime storage](#datetime-storage). |

The computed `earliest_date` and `latest_date` options are present in the schema but hidden (`visibility: hidden`). The Control Panel component derives the bounds directly from the other options, and the class docblock describes these two as vestigial.

### Boundary behavior

A boundary is "configured" when either type is not `none`, when `days_of_week` holds fewer than seven days, or when `date_exceptions` is not empty. The effective bounds resolve as follows:

- `custom`: the date in `*_date_custom`.
- `today`: the current calendar date in the display timezone, adjusted by the offset row. For the earliest boundary the offset comes from `earliest_date_today_offset`; for the latest boundary from `latest_date_today_offset`.
- `field`: the submitted or live value of the referenced sibling field, adjusted by the `*_date_field_offset` row.
- `earliest_date_exclude_before_today`: the earliest bound becomes the later of the resolved bound and today (plus `earliest_date_today_offset`).

All comparisons are by calendar day. Month and year offsets clamp to the last valid day of the target month (adding one month to January 31 gives February 28), matching the Control Panel picker.

Two surfaces enforce this:

- **Control Panel**: when a boundary is configured and `time_enabled` is off, the field uses the `custom_date` component (`resources/js/components/fieldtypes/DateFieldtype.vue`), which disables out-of-range days, disallowed weekdays and blackout dates. Every other `date` field, including every `time_enabled` field, keeps the core `date` component.
- **Server**: see [Validation](#validation).

Note that the server engine reads `latest_date_today_offset` for a latest boundary of type `today`, but this class defines no config field with that name (only `earliest_date_today_offset` exists). A latest boundary of type `today` therefore has no offset editor in the Control Panel; any offset would have to be written into the blueprint YAML by hand.

### Stored value

The stored shape follows the core fieldtype: a string for `mode: single`, and an object with `start` and `end` for `mode: range`. The save format comes from `format`; when `format` is absent it is `Y-m-d H:i`, or `Y-m-d H:i:s` when `time_seconds_enabled` is on in single mode, and `Y-m-d H:i` in range mode. A date-only field with the default format therefore stores a time component; set `format: Y-m-d` for true calendar-day storage.

#### Datetime storage

When `time_enabled` is on, `datetime_storage` chooses how a picked instant is saved:

- `convert` (default): core behavior. The picker value is read in the Statamic display timezone and stored using the app timezone.
- `literal`: the wall clock in the display timezone is stored as entered, with no conversion into the app timezone. On read the stored string is parsed in the display timezone and returned to templates as UTC Carbon instances (a range returns `start` and `end` Carbon instances). If the formatted value is purely numeric (for example a format of `Y`), it is stored as an integer.

The display timezone is `statamic.system.display_timezone`, falling back to `app.timezone`.

Date-only fields, and datetime fields stored with `convert`, are processed by the parent class.

#### Entry publish date

For an existing entry in a dated collection, the top-level field with the handle `date` and `mode: single` is pre-filled from the filename date prefix first (formats `Y-m-d`, `Y-m-d-Hi` or `Y-m-d-His`, read in the app timezone), then from the stored value (ignoring `null`, empty string and `now`), then from the entry date. A `date` field nested in a replicator or grid is not treated as the publish date. A blueprint `default: null` on the field skips this fallback, and `defaultValue()` honors an explicit `default` key even when it is null.

### Validation

- The core date validation still applies. The class converts the Control Panel's single-date array payload (`date` and optional `time`) into an ISO string before the core rule runs. If `date` is empty and the field's `validate` list has a rule starting with `required`, a `validation.required` error is raised under the field path.
- When a boundary is configured, `rules()` appends the `DateBoundary` rule (`UAMSWeb\Aether\Fieldtypes\Rules\DateBoundary`), which re-derives the bounds with `UAMSWeb\Aether\Fieldtypes\Support\DateBoundaryEngine` (the PHP mirror of `resources/js/date-boundary-engine.js`). It runs for `time_enabled` fields too, where it is the only boundary enforcement. A single value or both range endpoints are checked by calendar day; an unreadable endpoint is left to the format and required rules.
- Failure messages (English text used as the translation key): "This date must be on or after :date.", "This date must be on or before :date.", "This date falls on a day of the week that is not allowed." and "This date falls within a period that is not available.". `:date` is formatted `Y-m-d`.
- For a `field`-relative boundary the sibling value is read from the submitted data at the same nesting level, so a sibling in the same replicator or grid set resolves.
- Fields with no boundary configured get no extra rule.

### Form partial

`resources/views/forms/fields/date.antlers.html`. Single mode renders a native `<input type="date">` through the default partial. Range mode renders two date inputs named `{handle}[start]` and `{handle}[end]`. The partial applies `min` and `max` only from `earliest_date_type: custom` / `latest_date_type: custom` (falling back to plain `min` and `max` keys); the `today` and `field` boundary types are not applied in the markup, and the server rule is what enforces them on public forms. The partial notes that a stored value with time components is not accepted by a native date input.

### Example

```yaml
-
  handle: event_date
  field:
    type: date
    display: Event date
    format: Y-m-d
    earliest_date_type: today
    earliest_date_today_offset:
      -
        operator: add
        number: 1
        unit: day
    days_of_week:
      - '1'
      - '2'
      - '3'
      - '4'
      - '5'
```

## DateFieldSelect

`UAMSWeb\Aether\Fieldtypes\Fieldtypes\DateFieldSelect` has the handle `date_field_select`. It is a Control Panel config fieldtype only: it backs the `earliest_date_field` and `latest_date_field` options of [Date](#date) and is not offered in the fieldtype selector (`$selectable = false`) or in form blueprints (`$selectableInForms = false`).

The Control Panel component (`resources/js/components/fieldtypes/DateFieldSelectFieldtype.vue`, registered as `date_field_select-fieldtype`) lists the other `date` fields of the blueprint being edited. This has to happen in the browser because the config schema is built in isolation from the blueprint's other fields. The class has no config options and no server-side value handling; the stored value is a plain field handle string.

## PartialDate

`UAMSWeb\Aether\Fieldtypes\Fieldtypes\PartialDate` extends `Statamic\Fields\Fieldtype`. It stores a date whose year, month or day may be unknown (for example only a year, or a birthday without a year). The handle is `partial_date` (derived from the class name; the class sets `$component = 'partial_date'`). It is selectable in form blueprints, appears in the "special" category and has the search keywords date, year, month, day, partial and calendar. The title is read from the translation key `aether-fieldtypes::fieldtypes.partial_date.title` ("Partial Date"). Labels come from `resources/lang/en/fieldtypes.php`.

### Config options

Section "Date Parts":

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `included_parts` | select | `year_month_day` | Which parts editors see and which are stored. Values: `year_only`, `year_month`, `year_month_day`, `month_only`, `month_day`. |
| `required_parts_year_month_day` | select: `year_only`, `year_month`, `year_month_day` | `year_only` | When the field is required, which parts must be filled. Shown when `included_parts` is `year_month_day`. |
| `required_parts_year_month` | select: `year_only`, `year_month` | `year_only` | Same, shown when `included_parts` is `year_month`. |
| `required_parts_month_day` | select: `month_only`, `month_day` | `month_only` | Same, shown when `included_parts` is `month_day`. |
| `year_default` | integer | none | Pre-filled year. Shown when the mode includes a year. |
| `year_min` | integer | none | Minimum year. Shown when the mode includes a year. |
| `year_max` | integer | none | Maximum year. Shown when the mode includes a year. |
| `month_default` | select (1 to 12, localized month names), clearable | none | Pre-filled month. Shown when the mode includes a month. |
| `day_default` | integer | none | Pre-filled day. Shown when the mode includes a day. |

Section "Display":

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `subfields_layout` | radio: `inline`, `stack` | `inline` | Inline places the inputs in a row, wrapping when needed; stack places each full width on its own line. |

The `required_parts_*` options only matter when the field is required (`required` in its `validate` list). A configured granularity that is not allowed for the included mode is replaced by the coarsest allowed one (the last listed token for that mode). `month_only` always requires month only, and `year_only` always requires year only.

### Stored value

The persisted value is one canonical string, or `null` when no included part is set. Mode determines which parts are written; a part outside the mode is dropped. `XXXX` stands for an unknown year and `XX` for an unknown month.

| Parts present | Stored string |
| --- | --- |
| year, month, day | `YYYY-MM-DD` |
| year, month | `YYYY-MM` |
| year only | `YYYY` |
| year and day (month unknown) | `YYYY-XX-DD` |
| month and day (year unknown) | `XXXX-MM-DD` |
| month only (year unknown) | `XXXX-MM` |
| day only (year and month unknown) | `XXXX-XX-DD` |

`process()` accepts the Control Panel object `{ year, month, day }`, a stored string, or a legacy keyed array, and always saves the string form; a blank or unrecognized string reads as empty. `preProcess()` returns `{ year, month, day }` as nullable integers for the editor. No `augment()` override exists, so templates receive the stored string.

When the blueprint sets no `default`, the default value is built from `year_default`, `month_default` and `day_default` for the parts in the mode, and is `null` if none is set. An explicit blueprint `default` (a string or legacy array) is normalized through the same pipeline.

### Validation

- The root rules are `nullable` plus a closure that rejects a payload that is not structured data, a stored string that did not parse, a month outside 1 to 12, and a day outside 1 to the length of the month. With a known month the maximum day follows the Gregorian calendar (leap years when the year is known; February allows 29 when the year is unknown). With an unknown month the cap is 31.
- Nested rules are keyed by `{handle}.year`, `{handle}.month` and `{handle}.day`. A part outside the mode is `nullable`. Included parts are `integer`; year takes `min` and `max` from `year_min` and `year_max`, month takes `min:1` and `max:12`, and day takes `min:1`. A part is `required` only when the field is required and the effective granularity includes it.
- Error messages are in `resources/lang/en/fieldtypes.php` under `partial_date.validation.*`, including the month and day range messages and the hierarchy messages ("Select a month before choosing a day.", and similar).

### Form partial

`resources/views/forms/fields/partial_date.antlers.html` renders a number input for year, a select for month (localized names from the `month_options` variable that `extraRenderableFieldData()` supplies) and a number input for day (`min="1"` `max="31"`), depending on `included_parts`. Inputs are named `{handle}[year]`, `{handle}[month]` and `{handle}[day]`; `year_min` and `year_max` become `min` and `max` on the year input, and `year_default`, `month_default` and `day_default` pre-fill empty values. `subfields_layout` switches between a row of three columns and a stacked layout.

### Example

```yaml
-
  handle: founded
  field:
    type: partial_date
    display: Founded
    included_parts: year_month
    required_parts_year_month: year_only
    year_min: 1800
    validate:
      - required
```

## Month

`UAMSWeb\Aether\Fieldtypes\Fieldtypes\Month` extends `Statamic\Fields\Fieldtype` and uses the shared `Input` trait (the same config and preload machinery used by the text-style fieldtypes; see [Text and number fieldtypes](Fieldtypes-text-and-numbers.md)). The handle is `month`. It is selectable in the Control Panel fieldtype selector (category "special", keywords calendar, date and month) but is not selectable in form blueprints, because `$selectableInForms` is not set. The Control Panel component is `month`, and the icon is the core date icon.

### Config options

The options below are the ones the Control Panel shows for this fieldtype; the large set of HTML attribute options from the `Input` trait is hidden for it.

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `min` | text | none | Earliest allowed month, format `YYYY-MM`. The option itself must match that format. |
| `max` | text | none | Latest allowed month, format `YYYY-MM`. The option itself must match that format. |
| `default` | text | none | Default value. |
| `calculation_query` | toggle | `false` | Enables a dynamic default value computed from other fields. |
| `calculation_formula_text` | text template | none | The dynamic default text, shown when `calculation_query` is on. The hidden `calculation_type` defaults to `text`. |

The hidden `input_type` option defaults to `month`.

### Stored value

A string in the form `YYYY-MM`, with the month `01` to `12`. No conversion is applied on save.

### Validation

- `regex:/^(\d{4})-(0[1-9]|1[0-2])$/`.
- When `min` is set, a value earlier than `min` fails with "The month must be after or equal to :min.". When `max` is set, a later value fails with "The month must be before or equal to :max.". The message shows the bound as "Month yyyy" (for example "January 2020"). A value with no `-` separator is not compared and fails only the format rule.

### Form partial

None. The fieldtype is not selectable in form blueprints and there is no `month` partial under `resources/views/forms/fields`.

## Week

`UAMSWeb\Aether\Fieldtypes\Fieldtypes\Week` is built like [Month](#month): it extends `Statamic\Fields\Fieldtype` and uses the `Input` trait. The handle is `week`. It is selectable in the Control Panel (category "special", keywords calendar, date and week) and not selectable in form blueprints. The Control Panel component is `week`, and the icon is the core date icon.

### Config options

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `min` | text | none | Earliest allowed week, format `YYYY-Www` (for example `2020-W01`). The option itself must match that format. |
| `max` | text | none | Latest allowed week, format `YYYY-Www`. The option itself must match that format. |
| `default` | text | none | Default value. |
| `calculation_query` | toggle | `false` | Enables a dynamic default value computed from other fields. |
| `calculation_formula_text` | text template | none | The dynamic default text, shown when `calculation_query` is on. The hidden `calculation_type` defaults to `text`. |

The hidden `input_type` option defaults to `week`.

### Stored value

A string in the form `YYYY-Www`, with the week `01` to `53` (for example `2026-W07`). No conversion is applied on save.

### Validation

- `regex:/^(\d{4})-W(0[1-9]|[1-4]\d|5[0-3])$/`. Whether week 53 exists in the given ISO year is not checked.
- `min` and `max` are compared by year, then week number. Failures read "The week must be after or equal to :min." and "The week must be before or equal to :max.", with the bound shown as "Week xx, yyyy". A value with no `-W` separator is not compared and fails only the format rule.

### Form partial

None, for the same reason as [Month](#month).

## Time

`UAMSWeb\Aether\Fieldtypes\Fieldtypes\Time` extends `Statamic\Fieldtypes\Time` and uses the `Input` trait. The handle is `time`. It is selectable in form blueprints (`$selectableInForms = true`). The Control Panel component is `time`, and the icon is the core icon. Because the trait supplies the config schema, the core `time` config options are replaced by the trait's set.

### Config options

Most of the trait's HTML attribute options are hidden, including `min`, `max` and `step` (the code notes the Statamic 6 time component does not support them). The options that remain visible:

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `default` | time | none | Default value (the field's type is overridden to `time`). |
| `calculation_query` | toggle | `false` | Enables a dynamic default value computed from other fields. |
| `calculation_type` | radio: `math`, `text` | `math` | How the dynamic default is evaluated. Shown when `calculation_query` is on. |
| `calculation_formula_math` | code | none | Math expression, shown when the type is `math`. |
| `calculation_formula_text` | code | none | Text template, shown when the type is `text`. |
| `calculation_decimals` | integer | none | Decimal places for a math result. |
| `antlers` | toggle | none | "Allow Antlers". |

The hidden `input_type` defaults to `time`, `inputmode` to `numeric` and `autocomplete_query` to `false`.

### Stored value

A string in `H:i` form (hours and minutes, 24-hour, for example `14:30`). Seconds are not accepted by the validation rule.

### Validation

`rules()` returns only `date_format:H:i`.

### Form partial

`resources/views/forms/fields/time.antlers.html` renders a native `<input type="time">` through the default partial (`input_type="time"`).
