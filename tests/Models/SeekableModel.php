<?php

namespace Nihilsen\Seeker\Tests\Models;

use Nihilsen\Seeker\Contracts\ShouldSeekOnce;
use Nihilsen\Seeker\Data;

/**
 * @property string $value
 */
class SeekableModel extends TestModel implements ShouldSeekOnce
{
    public static function data(
        ?string $value = null
    ): static {
        return Data::trace();
    }
}
