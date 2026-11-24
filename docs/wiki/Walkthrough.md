Written against v0.3.0.

By the end of this page a Statamic form has a field from this add-on in its blueprint, and the field renders through the add-on's public partial.

It assumes the add-on is installed (see [Installation (README)](https://github.com/UAMS-Web/aether-fieldtypes#installation)) in a Statamic 6.35 or later site on PHP 8.4 or later.

## 1. Check which fieldtypes the add-on supplies

Statamic finds the add-on's fieldtype classes in `src/Fieldtypes` by their class names, so each handle is the snake-case class name: `text`, `email`, `phone`, `date`, `checkboxes`, `gps` and the rest. The full lists are on the fieldtype pages: [text and numbers](Fieldtypes-text-and-numbers.md), [choices](Fieldtypes-choices.md), [dates and times](Fieldtypes-dates-and-times.md) and [composite, media and layout](Fieldtypes-composite-media-and-layout.md).

## 2. Use a handle in a form blueprint

A form's blueprint names a fieldtype by its handle. A field of the `phone` type looks like this in the blueprint file:

```yaml
sections:
  main:
    display: Main
    fields:
      -
        handle: contact_phone
        field:
          type: phone
          display: Phone
```

The options a field accepts are listed on the fieldtype's page, for example under Phone in [Text and number fieldtypes](Fieldtypes-text-and-numbers.md). Only a fieldtype that is selectable in forms appears in a form blueprint's field picker; each page says which.

## 3. Render the field

The add-on offers its partials under Statamic's own `statamic::forms.fields.<handle>` names, so the form tag finds `statamic::forms.fields.phone` without any host setup. A host file of the same name in `resources/views/vendor/statamic/forms/fields` is searched first and wins. The partials and their attributes are in [Form field partials](Form-field-partials.md).

## 4. Load the Alpine helper where a partial needs it

Partials that calculate or toggle values mark their inputs with data attributes, and an Alpine engine fills them. The host imports the add-on's Alpine entry in its JavaScript before `Alpine.start()`:

```js
import { registerAlpineEngine, registerCalculationEngine } from '../../vendor/uams-web/aether-fieldtypes/resources/js/alpine.js'

registerCalculationEngine()
```

The entry is a source module that the host's own build bundles. What each export does is in [Alpine helper](Alpine-helper.md).

**Note:** the host's build must resolve `@alpinejs/csp`, which the calculation engine imports.

## Go further

- [Check that every handle has one owner](Check-handle-ownership.md): guard against two packages registering one handle.
- [Choice submission limits](Choice-submission-limits.md): cap how many submissions may select a choice.
