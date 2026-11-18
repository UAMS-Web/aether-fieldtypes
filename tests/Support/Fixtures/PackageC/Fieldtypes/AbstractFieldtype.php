<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Fixtures\PackageC\Fieldtypes;

use Statamic\Fields\Fieldtype;

/**
 * Fixture abstract fieldtype, which the provider does not register and the reader skips.
 */
abstract class AbstractFieldtype extends Fieldtype {}
