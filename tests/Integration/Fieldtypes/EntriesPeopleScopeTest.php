<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Statamic\Auth\File\User as FileUser;
use Statamic\Entries\Collection as CollectionContract;
use Statamic\Entries\Entry as EntryItem;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Statamic\Fields\Field;
use Statamic\Fieldtypes\Entries as BaseEntries;
use Statamic\Query\Builder as QueryBuilder;
use UAMSWeb\Aether\Fieldtypes\Fieldtypes\Entries;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\EntriesWithMissingScope;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\EntriesWithScopelessClass;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\EntriesWithStubScope;
use UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles\StubPeopleEditingScope;

/**
 * How the Entries picker receives the people editing scope, which lives in an add-on that requires this one.
 *
 * The picker names the scope's class, asks the host's container for it, and keeps a scoped editor to their
 * `people` entries. Without the class there is no scope to apply; a class that cannot answer is refused.
 *
 * @command  vendor/bin/pest tests/Integration/Fieldtypes/EntriesPeopleScopeTest.php
 */
covers(Entries::class);

/**
 * The ids the picker offers, after its index query runs.
 *
 * @param  class-string<BaseEntries>  $class  The picker class under test
 * @param  list<string>  $collections  The collections the picker targets
 * @return list<string> The offered ids of this file's fixtures, sorted (the query itself spans every collection)
 */
function peoplePickerIds(string $class, array $collections): array
{
    $fieldtype = new $class;
    $fieldtype->setField(new Field('picker', ['type' => 'entries', 'collections' => $collections]));

    /** @var QueryBuilder $query */
    $query = new ReflectionMethod($class, 'getIndexQuery')->invoke($fieldtype, Request::create('/'));

    $offered = [];

    /** @var iterable<EntryItem> $found */
    $found = $query->get();

    foreach ($found as $entry) {
        $id = mustBeString($entry->id(), 'an entry id');

        // Keep this file's fixtures: the query spans every collection and the Stache outlives a test
        if (\in_array($id, ['a', 'b', 'c', 'n1'], true)) {
            $offered[] = $id;
        }
    }

    sort($offered);

    return $offered;
}

beforeEach(function (): void {
    Blink::flush();
    StubPeopleEditingScope::reset();
    app()->instance(StubPeopleEditingScope::class, new StubPeopleEditingScope);

    foreach (['people' => ['a', 'b', 'c'], 'news' => ['n1']] as $handle => $ids) {
        /** @var CollectionContract $collection */
        $collection = CollectionFacade::make($handle);
        $collection->sites(['default']);
        $collection->save();

        foreach ($ids as $id) {
            /** @var EntryItem $entry */
            $entry = Entry::make();
            $entry->collection($handle);
            $entry->locale('default');
            $entry->id($id);
            $entry->slug($id);
            $entry->data(['title' => strtoupper($id)]);
            $entry->saveQuietly();
        }
    }

    $user = mustBe(FileUser::class, User::make(), 'the test user');
    $user->id('editor');
    $user->email('editor@example.test');

    test()->actingAs($user);
});

it('names the class and the collection the people add-on registers', function (): void {
    // Read the defaults the picker ships, as the host's container resolves them without a stand-in
    expect(new ReflectionClassConstant(Entries::class, 'PEOPLE_EDITING_SCOPE')->getValue())->toBe('UAMSWeb\\Aether\\People\\Support\\PeopleEditingScope')
        ->and(new ReflectionClassConstant(Entries::class, 'PEOPLE_COLLECTION')->getValue())->toBe('people');
});

it('leaves a scoped editor only their people', function (): void {
    // The scope applies to the editor and puts one person out of scope
    StubPeopleEditingScope::$outOfScope = ['b'];

    // Expect the picker to offer the other people
    expect(peoplePickerIds(EntriesWithStubScope::class, ['people']))->toBe(['a', 'c', 'n1']);
});

it('excludes out-of-scope people but keeps entries of other collections', function (): void {
    StubPeopleEditingScope::$outOfScope = ['b'];

    expect(peoplePickerIds(EntriesWithStubScope::class, ['people', 'news']))->toBe(['a', 'c', 'n1']);
});

it('offers every person when the scope does not apply to the editor', function (): void {
    StubPeopleEditingScope::$applies = false;
    StubPeopleEditingScope::$outOfScope = ['b'];

    expect(peoplePickerIds(EntriesWithStubScope::class, ['people']))->toBe(['a', 'b', 'c', 'n1'])
        ->and(StubPeopleEditingScope::$appliesCalls)->toBe(1);
});

it('applies no scope to a guest request', function (): void {
    // Sign the editor out, then let a scope that would exclude a person apply
    app('auth')->guard()->logout();
    StubPeopleEditingScope::$outOfScope = ['b'];

    expect(peoplePickerIds(EntriesWithStubScope::class, ['people']))->toBe(['a', 'b', 'c', 'n1']);
});

it('never asks the scope about a picker that cannot offer people', function (): void {
    StubPeopleEditingScope::$outOfScope = ['b'];

    expect(peoplePickerIds(EntriesWithStubScope::class, ['news']))->toBe(['a', 'b', 'c', 'n1'])
        ->and(StubPeopleEditingScope::$appliesCalls)->toBe(0);
});

it('applies no scope in a host without the people add-on', function (): void {
    expect(peoplePickerIds(EntriesWithMissingScope::class, ['people']))->toBe(['a', 'b', 'c', 'n1']);
});

it('refuses a scope class that cannot answer, rather than offering every person', function (): void {
    expect(fn (): array => peoplePickerIds(EntriesWithScopelessClass::class, ['people']))
        ->toThrow(LogicException::class, 'no longer offers appliesTo() and outOfScopeEntryIds()');
});
