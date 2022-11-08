<?php

namespace Nihilsen\Seeker\Tests\Endpoints;

use Nihilsen\Seeker\Contracts\Seedable;
use Nihilsen\Seeker\Endpoint;
use Nihilsen\Seeker\Tests\Models\SeedableModel;

class SimpleSeedableEndpoint extends Endpoint implements Seedable
{
    public static function seeks(): string|array
    {
        return SeedableModel::class;
    }
}
