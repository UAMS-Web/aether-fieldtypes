Aether Fieldtypes is the shared fieldtype add-on for UAMS Statamic sites. It supplies the form fieldtypes, their Control Panel components, the public form partials, the validation rules, the Alpine helper that the partials rely on, and an owner check that keeps each fieldtype handle with exactly one package.

This wiki holds the long-form documentation: a walkthrough, task guides, troubleshooting and a reference for everything release `v0.3.0` ships. The [README](https://github.com/UAMS-Web/aether-fieldtypes#readme) keeps the overview, the requirements and installation.

## Getting started

- [Installation (README)](https://github.com/UAMS-Web/aether-fieldtypes#installation): add the package to a Statamic install.
- [Walkthrough](Walkthrough.md): from a fresh install to a rendered form field.

## Guides

- [Check that every handle has one owner](Check-handle-ownership.md): run the owner check from a host's test suite.
- [Address search endpoint](Address-search-endpoint.md): the GPS address-search endpoint, its settings and the egress guard a host binds (not yet released).

## Fieldtypes

- [Text and number fieldtypes](Fieldtypes-text-and-numbers.md): `text`, `email`, `url`, `password`, `username`, `hidden`, `identifier`, `css_selector`, `xpath`, `mask_field`, `zip_code`, `phone`, `integer`, `float`, `currency`.
- [Choice fieldtypes](Fieldtypes-choices.md): `checkboxes`, `radio`, `select`, `option`, `true_false`, `consent`, `trigger`, `terms`, `terms_search_index`, `entries`, `list`.
- [Date and time fieldtypes](Fieldtypes-dates-and-times.md): `date`, `date_field_select`, `partial_date`, `month`, `week`, `time`.
- [Composite, media and layout fieldtypes](Fieldtypes-composite-media-and-layout.md): `address`, `gps`, `name`, `assets`, `audio`, `video`, `signature`, `replicator`, `section`, `subsection`, `html`, `product`, `total`.

## Reference

- [Validation rules](Validation-rules.md): every rule class and the asset file-size scope.
- [Choice submission limits](Choice-submission-limits.md): the per-choice cap and the listener that enforces it.
- [Form field partials](Form-field-partials.md): the public Antlers partials and how a host overrides one.
- [Map settings](Map-settings.md): the `maps` configuration the GPS picker reads.
- [Alpine helper](Alpine-helper.md): the entry a host imports and the engines it registers.
- [Owner check](Owner-check.md): `OwnerMap`, its results and its messages.

## Troubleshooting

- [Troubleshooting](Troubleshooting.md): one entry per symptom, with its cause and fix.

## Project

- [Project](Project.md): where the add-on is developed and how to report a problem.
