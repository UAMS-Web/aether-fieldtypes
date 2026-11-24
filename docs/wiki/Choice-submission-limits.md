Written against v0.3.0.

Per-choice submission limits cap how many form submissions may select a given choice, for example "the first 30 sign-ups for the Tuesday session". This page covers how a limit is configured on a choice field, how a full choice is shown in the rendered form, how the cap is enforced when a form is posted, what counts toward it, and the limits of the check. The fieldtypes that carry the setting are described in [Fieldtypes: choices](Fieldtypes-choices.md), and the partial templates in [Form field partials](Form-field-partials.md).

- [Configuring a limit](#configuring-a-limit)
- [Config options](#config-options)
- [What counts toward a cap](#what-counts-toward-a-cap)
- [In the rendered form](#in-the-rendered-form)
- [When a submission is refused](#when-a-submission-is-refused)
- [Limits of the check](#limits-of-the-check)
- [Classes involved](#classes-involved)

## Configuring a limit

Three fieldtypes accept the setting: `Radio`, `Checkboxes` and `Select`. Each includes the `HasChoiceSubmissionLimits` trait, which adds three config fields to the field's settings. A blueprint turns the feature on with `enable_choice_limits` and lists the capped choices in `choice_limits`:

```yaml
-
  handle: session
  field:
    type: radio
    display: Session
    options:
      tuesday: Tuesday
      wednesday: Wednesday
    enable_choice_limits: true
    choice_limits:
      -
        choice: tuesday
        limit: 30
    choice_limit_message: 'That session is full. Please pick another.'
```

The `choice` value is the option key (the stored value), not its label. A choice left out of `choice_limits` is unlimited.

## Config options

| Option | Type | Default | Effect |
| --- | --- | --- | --- |
| `enable_choice_limits` | toggle | `false` | Turns the feature on. A string value of `true`, `1`, `yes` or `on` (any case) also counts as on, which lets imported config work. |
| `choice_limits` | grid | none | One row per capped choice. Shown only while `enable_choice_limits` is on. |
| `choice_limits[].choice` | text | none | The option key to cap. Required in the Control Panel. |
| `choice_limits[].limit` | integer | none | The cap for that choice, minimum 1. Required in the Control Panel. |
| `choice_limit_message` | text | `One or more of your selections is no longer available. Please choose another option.` | The message shown when a submission selects a full choice. Shown only while `enable_choice_limits` is on. |

A row is ignored when its `choice` is not a scalar value or its `limit` is not numeric or is zero or less. A field is treated as limited only when the feature is on and at least one valid row remains.

## What counts toward a cap

The count for a choice is the number of the form's stored submissions whose value for that field selected it:

- For `Radio` and `Select`, the stored value is one option, and it counts once toward that option.
- For `Checkboxes`, the stored value is a list, and each selected option counts once toward its own cap.
- Values that are not scalar are ignored.
- Only the choices that have a limit are counted.

A choice is full once its count is greater than or equal to its limit. The submission being posted is not stored when the check runs, so it is not part of the count: a choice with a limit of 30 accepts submissions until 30 are stored, and refuses the 31st.

## In the rendered form

When the field is rendered inside a form, the trait adds a `full_choices` list (the option keys that are full) to the field's renderable data. Outside a form (for example a collection entry field), or when limits are off, the list is empty.

The three partial templates `resources/views/forms/fields/radio.antlers.html`, `checkboxes.antlers.html` and `select.antlers.html` read `full_choices` for each option:

- The option's input (or `<option>` for a select) gets the `disabled` attribute.
- The label gets a "Full" suffix, translated through Statamic's `trans` tag with the key `Full`. Radio and checkbox labels wrap it as `<span class="choice-full">(Full)</span>`; a select option gets plain `(Full)` text.

The `Full` string has no entry in the add-on's lang files, so it falls back to the key itself unless the host provides a translation.

The disabled state is a convenience for the person filling in the form. The server check below is what enforces the cap.

## When a submission is refused

The service provider registers `EnforceChoiceSubmissionLimits` for Statamic's `FormSubmitted` event (`ServiceProvider::$listen`). The event fires before the submission is stored. For each field of the form's blueprint that has limits enabled, the listener checks the submitted value against the full choices. If any selected value is full, it throws a `ValidationException` keyed by the field's handle. The form shows the message against that field through its normal error display, and nothing is stored.

The message is the field's `choice_limit_message` when that is a non-blank string (surrounding whitespace is trimmed). Otherwise it is the default, `One or more of your selections is no longer available. Please choose another option.`, passed through `__()` as a JSON-key translation. There is no key for it in `resources/lang/en/`, so a host translates it with its own JSON lang file.

The listener works from the field config, not from the fieldtype class, so it applies to any form field whose config carries `enable_choice_limits` and `choice_limits`.

## Limits of the check

- **Not atomic.** The count is a read of the stored submissions followed by a store, with no lock. Two submissions that arrive at the same moment can each see a count below the limit and both be stored, so a cap can be exceeded by a small amount. The code documents this as an accepted gap for public sign-up forms with no seats or money at stake.
- **The form is a hint, the server decides.** The listener's check is the authoritative one. The disabled state in the rendered form is computed the same way but may differ in edge cases. The code gives the example of the Antlers `contains` check on option keys such as `'1'` and `'01'`. Because the server check decides, such a difference does not let a submission through.
- **Only stored submissions count.** A submission that was refused, or that Statamic did not store, does not use up a place.

## Classes involved

| Class | Role |
| --- | --- |
| `UAMSWeb\Aether\Fieldtypes\Traits\HasChoiceSubmissionLimits` | Adds the three config fields (`choiceSubmissionLimitConfigField()`) and the `full_choices` render data (`fullChoicesForRender()`). |
| `UAMSWeb\Aether\Fieldtypes\Support\Forms\ChoiceSubmissionLimits` | Reads the config and counts submissions: `enabledFor()`, `fullChoices()` and `selectionHitsFullChoice()`. The render and the listener both use it. |
| `UAMSWeb\Aether\Fieldtypes\Listeners\EnforceChoiceSubmissionLimits` | The `FormSubmitted` listener that throws the `ValidationException`. |
