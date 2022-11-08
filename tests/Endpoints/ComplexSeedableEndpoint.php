<?php

namespace Nihilsen\Seeker\Tests\Endpoints;

use Illuminate\Database\Eloquent\Builder;
use Nihilsen\Seeker\Contracts\Seedable;
use Nihilsen\Seeker\Endpoint;
use Nihilsen\Seeker\Tests\Models\SeedableModel;

class ComplexSeedableEndpoint extends Endpoint implements Seedable
{
    public static function seeks(): string|array
    {
        return [
            SeedableModel::class => fn (Builder $query) => $query
                ->where('value', 1)
                ->orWhere('value', 2)
                ->orWhereIn(
                    'value',
                    [3, 4, 5]
                ),
        ];
    }
}
