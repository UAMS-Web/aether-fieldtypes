<?php

declare(strict_types=1);

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
