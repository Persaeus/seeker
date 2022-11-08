<?php

namespace Nihilsen\Seeker\Tests\Models;

use Nihilsen\Seeker\Data;

class SeedableModel extends TestModel
{
    public static function data(
        ?string $value = null
    ): static {
        return Data::trace();
    }
}
