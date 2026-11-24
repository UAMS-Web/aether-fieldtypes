Written against v0.3.0.

A Statamic install keeps one class per fieldtype handle, and the last registration wins, so two packages registering the same handle fail silently. The owner check reads what each installed add-on registers and fails when two packages register one name.

## Steps

1. Add a test to the host's own suite that calls the check:

   ```php
   use UAMSWeb\Aether\Fieldtypes\Ownership\OwnerMap;

   OwnerMap::fromInstalledAddons()->assertSingleOwners();
   ```

2. Run the host's tests. A clean run means every fieldtype handle and Control Panel component name has one owner.
3. When two packages register the same name, the call throws `OwnershipConflict` naming each handle or component name and its owners. Remove one registration, or move the handle to a single package.
4. When the check cannot read every registration, a clean result would not cover them, so the call reports the unreadable ones instead of passing. See [Troubleshooting](Troubleshooting.md#the-owner-check-reports-a-conflict-or-unreadable-registrations).

## Reference

- [Owner check](Owner-check.md): the classes, results and messages the check uses.
