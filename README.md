# Aether Fieldtypes

The shared fieldtype add-on for UAMS Statamic sites. It holds the traits, the Control Panel JavaScript and the public Alpine helper that every fieldtype family shares, and the owner check that keeps each fieldtype handle and Control Panel component name with exactly one package. The fieldtype families move in behind this foundation, one slice at a time; the input family is the first.

## What is in it

- **The input family**, under `UAMSWeb\Aether\Fieldtypes\Fieldtypes`: `CssSelector`, `Currency`, `Email`, `Floatval`, `Hidden`, `Identifier`, `Integer`, `Month`, `Password`, `Phone`, `Text`, `Time`, `Url`, `Username`, `Week`, `Xpath` and `ZipCode`, each registered under the handle it replaces, with its Control Panel component and public form partial. `Phone` brings the `InternationalPhone`, `NationalPhone` and `SmsShortCode` rules and `PhoneUtil`; `Identifier` brings `IdentifierRegex`; `ZipCode` brings its rule. The rules and the services are under `UAMSWeb\Aether\Fieldtypes\Rules` and `UAMSWeb\Aether\Fieldtypes\Services`.
- **Public form partials** in `resources/views/forms/fields`, registered under Statamic's own `statamic::forms.fields.<handle>` names. A host's `resources/views/vendor/statamic/forms/fields` file of the same name is searched first and wins.
- **Shared traits** under `UAMSWeb\Aether\Fieldtypes\Traits`: the input, validation, subfield, config-section, mask and default-value helpers the fieldtypes build on.
- **Shared Control Panel JavaScript** in `resources/js/components/fieldtypes`: `CustomTextInputFieldtypeMixin`, `GroupFieldtypeShell`, `subfieldId`, and the "Other" option mixin, utility and partial.
- **The public Alpine helper**, `registerAlpineEngine`, from `resources/js/alpine.js`. A host application imports it before it calls `Alpine.start()` (see [Usage](#usage)), so each engine registers while Alpine is booting.
- **The owner check**, `UAMSWeb\Aether\Fieldtypes\Ownership\OwnerMap`, which reads the fieldtype handles and Control Panel component names that each installed add-on registers and fails when two packages register one.
- **The add-on's own lang namespace**, `aether-fieldtypes::messages` and `aether-fieldtypes::validation`. A host overrides a message by publishing the file to `lang/vendor/aether-fieldtypes`.

## Requirements

PHP 8.4 or later and Statamic 6.35 or later.

## Installation

```bash
composer require uams-web/aether-fieldtypes
```

Statamic discovers the add-on through `extra.statamic` in `composer.json`, and Laravel boots its service provider through `extra.laravel.providers`.

### Validation rules the host registers

`CssSelector` asks the validator for `css_selector_syntax`, `Xpath` for `xpath_syntax`, and each `Identifier` preset for the rule named in its definition (`npi`, `orcid`, `epic_dep_id` and the rest). The add-on ships `IdentifierRegex`, `InternationalPhone`, `NationalPhone`, `SmsShortCode` and the ZIP Code rule, and no named-rule registry: the host registers every name, and a name it has not registered fails at validation time, not at boot.

## Usage

### Import the Alpine helper

In the host's JavaScript entry, import the helper before `Alpine.start()`:

```js
import { registerAlpineEngine } from '../../vendor/uams-web/aether-fieldtypes/resources/js/alpine.js'
```

The helper is a source module and is not built into the add-on's committed Control Panel bundle: the host's own build includes it.

### Assert that every name has one owner

A host runs the owner check from its test suite:

```php
use UAMSWeb\Aether\Fieldtypes\Ownership\OwnerMap;

OwnerMap::fromInstalledAddons()->assertSingleOwners();
```

It throws `OwnershipConflict`, naming each handle or component name and its owners, when two packages register the same one. Statamic itself cannot report this, because its registry keeps one class per handle and the last registration wins.

## Development

Developing against a host application is covered in [the development guide](docs/DEVELOPMENT.md), and the Packagist and Marketplace steps in [the submission checklist](docs/SUBMISSION.md). `composer ci` runs the gate; `npm run test:scripts` runs the JavaScript tests.

## License

MIT. See [`LICENSE`](LICENSE).
