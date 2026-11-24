Written against v0.3.0.

One entry per symptom, as it is met: the message or behavior, its cause, and the fix.

- [The GPS address search says it is unavailable](#the-gps-address-search-says-it-is-unavailable)
- [A named validation rule fails at validation time](#a-named-validation-rule-fails-at-validation-time)
- [The owner check reports a conflict or unreadable registrations](#the-owner-check-reports-a-conflict-or-unreadable-registrations)
- [An Assets field accepts a file of the wrong type or size](#an-assets-field-accepts-a-file-of-the-wrong-type-or-size)
- [A submission is refused with a choice-limit message](#a-submission-is-refused-with-a-choice-limit-message)
- [Fixed](#fixed)

## The GPS address search says it is unavailable

The GPS field shows a search box, and a search ends with:

```text
Address search is unavailable right now. Try again shortly.
```

**Cause:** the box appears only on a field with `enable_address_search` turned on. It requests the Control Panel URL `gps/nominatim-search`, and release `v0.3.0` registers no handler for that URL (`config/maps.php` describes it as an endpoint the host provides). Without one, the request fails.

**Fix:** turn `enable_address_search` off on the field (it defaults to off), or provide the endpoint in the host. The [Address search endpoint](Address-search-endpoint.md) page describes the endpoint a later release adds. The picker settings are in [Map settings](Map-settings.md).

## A named validation rule fails at validation time

A form with a CSS selector, XPath or identifier field fails validation with an error about an unknown rule, or the field never validates its format.

**Cause:** `css_selector` asks the validator for `css_selector_syntax`, `xpath` for `xpath_syntax`, and each `identifier` preset for the rule named in its definition. The add-on ships the rule classes but no registry of names. A name the host has not registered fails when the validator runs, not when the application boots.

**Fix:** register each name the host's fieldtypes use. The rule classes are listed in [Validation rules](Validation-rules.md), and the README section "Validation rules the host registers" names them.

## The owner check reports a conflict or unreadable registrations

`assertSingleOwners()` throws with one of these messages:

```text
More than one package registers the same name, and Statamic keeps only the last registration: <conflicts>.
The owner check could not read every registration, so a clean result would not cover them: <unreadable>.
```

**Cause:** the first message means two installed packages register one fieldtype handle or Control Panel component name, so which one wins depends on boot order. The second means a registration could not be read, and a pass would say nothing about it.

**Fix:** for a conflict, leave the name with one package. For an unreadable registration, read the listed subject and reason in [Owner check](Owner-check.md#exceptions-and-messages) and make that registration readable. The steps are in [Check that every handle has one owner](Check-handle-ownership.md).

## An Assets field accepts a file of the wrong type or size

An `assets` field configured with file type restrictions or size limits accepts a selection that breaks them.

**Cause:** at `v0.3.0` the type and size settings narrow what the asset browser offers through query scopes, but they are not wired into the field's validation rules. Only the minimum image dimensions are validated.

**Fix:** none in the add-on at this release; enforce the limit on the container or in the host's own validation. Details are under Assets in [Composite, media and layout fieldtypes](Fieldtypes-composite-media-and-layout.md).

## A submission is refused with a choice-limit message

A form post is refused, and the field shows its `choice_limit_message`, or by default:

```text
One or more of your selections is no longer available. Please choose another option.
```

**Cause:** a selected choice has reached its submission limit. The listener checks the stored submissions on every post, so a direct post that bypassed the disabled choice in the rendered form is refused too.

**Fix:** pick a choice that is not full, or raise or remove the limit on the field. See [Choice submission limits](Choice-submission-limits.md).

## Fixed

Problems fixed in a release stay here, with the release that fixed them, instead of being deleted. None is recorded yet.
