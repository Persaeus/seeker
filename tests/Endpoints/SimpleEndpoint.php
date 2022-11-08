<?php

namespace Nihilsen\Seeker\Tests\Endpoints;

use Illuminate\Database\Eloquent\Model;
use Nihilsen\Seeker\Endpoint;
use Nihilsen\Seeker\Tests\Models\SimpleModel;

class SimpleEndpoint extends Endpoint
{
    protected string $url = 'simple.invalid';

    public function analyze($part, string $key): Model|array
    {
        return SimpleModel::data(value: $part->value);
    }

    public function partition($decodedResponse)
    {
        return $decodedResponse->results;
    }

    public static function seeks(): string|array
    {
        return SimpleModel::class;
    }
}
