<?php

namespace Nihilsen\Seeker\Tests\Models;

use Nihilsen\Seeker\Data;

/**
 * @property string $description
 * @property string $value
 */
class SimpleModel extends TestModel
{
    public static function data(
        ?string $value = null,
        ?string $description = null
    ): static {
        return Data::trace(firstOrNew: ['value']);
    }
}
