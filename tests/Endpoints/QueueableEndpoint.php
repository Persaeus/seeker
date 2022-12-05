<?php

namespace Nihilsen\Seeker\Tests\Endpoints;

use Nihilsen\Seeker\Endpoint;
use Nihilsen\Seeker\Tests\Models\SeekableModel;

class QueueableEndpoint extends Endpoint
{
    /**
     * {@inheritDoc}
     */
    public static string $defaultQueue = TestQueue::class;

    /**
     * {@inheritDoc}
     */
    public static function seeks(): string|array
    {
        return SeekableModel::class;
    }
}
