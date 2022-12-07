<?php

namespace Nihilsen\Seeker\Tests\Endpoints;

use Nihilsen\Seeker\Queue;

class TestQueue extends Queue
{
    /**
     * Set the default $max_per_minute.
     *
     * @var int
     */
    public const MAX_PER_MINUTE = 2;
}
