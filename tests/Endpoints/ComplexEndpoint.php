<?php

namespace Nihilsen\Seeker\Tests\Endpoints;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Nihilsen\Seeker\Endpoint;
use Nihilsen\Seeker\Tests\Models\ComplexModel;
use Nihilsen\Seeker\Tests\Models\SimpleModel;

/**
 * @property \Nihilsen\Seeker\Tests\Models\SimpleModel|\Nihilsen\Seeker\Tests\Models\ComplexModel $seekable
 */
class ComplexEndpoint extends Endpoint
{
    public function analyze($part, string $key): Model|array
    {
        if ($this->seekable instanceof SimpleModel) {
            return SimpleModel::data(
                value: $this->seekable->value,
                description: $part->description
            );
        } else {
            return [
                $simpleModel = SimpleModel::data(value: $part->simple_value),
                ComplexModel::data(
                    value: $this->seekable->value,
                    description: $part->description,
                    simpleModel: $simpleModel
                ),
            ];
        }
    }

    public static function seeks(): string|array
    {
        return [
            SimpleModel::class => fn (Builder $query) => $query->where('value', 42),
            ComplexModel::class => fn (Builder $query) => $query->whereHas(
                'simpleModel',
                fn (Builder $query) => $query->where('value', 42)
            ),
        ];
    }

    public function url(): string
    {
        return 'complex.invalid/'.($this->seekable instanceof SimpleModel ? 's' : 'c').'/'.$this->seekable->value;
    }
}
