<?php

namespace Nihilsen\Seeker\Assimilation\Tasks\Attribute;

use Illuminate\Database\Eloquent\Collection;

abstract class SetIterableRelation extends SetRelation
{
    protected Collection $models;

    public function get(): ?\Closure
    {
        if ($this->value instanceof Collection) {
            $this->models = $this->value;

            return parent::get();
        }

        return null;
    }
}
