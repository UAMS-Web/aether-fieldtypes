<?php

declare(strict_types=1);
use Statamic\Forms\Form;

/**
 * Control-flow type narrowing for the tests.
 *
 * `\assert()` can be compiled out and names no cause when it trips, and it makes the analyzer take
 * its word for a type instead of verifying it. These helpers narrow by control flow, so PHPStan
 * verifies the declared type and a regression reports what actually went wrong.
 */
if (! function_exists('mustBe')) {
    /**
     * Narrow a value to an expected class, failing by name when it is something else.
     *
     * @template TExpected of object
     *
     * @param  class-string<TExpected>  $expected  The class or interface required.
     * @param  mixed  $value  The value to narrow.
     * @param  string  $subject  What the value is, named for the failure message.
     * @return TExpected The value, typed as the expected class.
     */
    function mustBe(string $expected, mixed $value, string $subject): object
    {
        if (! $value instanceof $expected) {
            throw new RuntimeException(sprintf(
                'Expected %s to be a %s, got %s.',
                $subject,
                $expected,
                get_debug_type($value),
            ));
        }

        return $value;
    }
}

if (! function_exists('mustBeArray')) {
    /**
     * Narrow a value to an array, failing by name when it is not one.
     *
     * @param  mixed  $value  The value to narrow.
     * @param  string  $subject  What the value is, named for the failure message.
     * @return array<array-key, mixed> The value, typed as an array.
     */
    function mustBeArray(mixed $value, string $subject): array
    {
        if (! is_array($value)) {
            throw new RuntimeException(sprintf(
                'Expected %s to be an array, got %s.',
                $subject,
                get_debug_type($value),
            ));
        }

        return $value;
    }
}

if (! function_exists('mustBeStringKeyed')) {
    /**
     * Narrow a value to an array keyed only by strings, failing by name when it is not one.
     *
     * @param  mixed  $value  The value to narrow.
     * @param  string  $subject  What the value is, named for the failure message.
     * @return array<string, mixed> The value, typed as a string-keyed array.
     */
    function mustBeStringKeyed(mixed $value, string $subject): array
    {
        $array = mustBeArray($value, $subject);
        $narrowed = [];

        foreach ($array as $key => $item) {
            if (! is_string($key)) {
                throw new RuntimeException(sprintf('Expected every key of %s to be a string, got %s.', $subject, get_debug_type($key)));
            }

            $narrowed[$key] = $item;
        }

        return $narrowed;
    }
}

if (! function_exists('makeForm')) {
    /**
     * Build a handle-bound form, resolving the facade's nullable return.
     *
     * `Statamic\Facades\Form::make()` is declared to return `Form|null`, so a typed helper narrows it once
     * rather than every test doing it.
     *
     * @param  string  $handle  The form handle.
     * @return Form The form, bound to that handle.
     */
    function makeForm(string $handle): Form
    {
        return mustBe(Form::class, Statamic\Facades\Form::make($handle), sprintf('form "%s"', $handle));
    }
}

if (! function_exists('mustBeString')) {
    /**
     * Narrow a value to a string, failing by name when it is not one.
     *
     * Replaces `\assert(\is_string($x))`, which typically guards a getter whose
     * union includes `null` (`Email::getHtmlBody()`, `Entry::slug()`).
     *
     * @param  mixed  $value  The value to narrow.
     * @param  string  $subject  What the value is, named for the failure message.
     * @return string The value, typed as a string.
     */
    function mustBeString(mixed $value, string $subject): string
    {
        if (! is_string($value)) {
            throw new RuntimeException(sprintf(
                'Expected %s to be a string, got %s.',
                $subject,
                get_debug_type($value),
            ));
        }

        return $value;
    }
}

if (! function_exists('mustExist')) {
    /**
     * Narrow a nullable value to its non-null type, failing by name when absent.
     *
     * Replaces `\assert($x !== null)`. Several call sites paired that with an
     * `expect($x)->not->toBeNull()` immediately above — the expectation was the
     * real check and the assertion existed only to satisfy PHPStan, so both
     * collapse into this one call.
     *
     * @template TValue
     *
     * @param  TValue|null  $value  The value to narrow.
     * @param  string  $subject  What the value is, named for the failure message.
     * @return TValue The value, typed as non-null.
     */
    function mustExist(mixed $value, string $subject): mixed
    {
        if ($value === null) {
            throw new RuntimeException(sprintf('Expected %s to exist, got null.', $subject));
        }

        return $value;
    }
}
