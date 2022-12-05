<?php

namespace Nihilsen\Seeker\Tests\Endpoints;

use Nihilsen\Seeker\Queue;

class TestQueue extends Queue
{
    /**
     * {@inheritDoc}
     */
    public int $maxPerMinute = 2;
}
