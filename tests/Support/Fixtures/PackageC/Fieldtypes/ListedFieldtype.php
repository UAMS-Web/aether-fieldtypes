<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageC\Fieldtypes;

use Statamic\Fields\Fieldtype;

/**
 * Fixture fieldtype that the provider lists and the `Fieldtypes` folder also holds, so it is
 * found twice and claimed once.
 */
final class ListedFieldtype extends Fieldtype {}
