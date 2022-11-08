<?php

namespace Nihilsen\Seeker\Assimilation\Tasks\Attribute;

use Illuminate\Database\Eloquent\Relations\Relation;

abstract class SetRelation extends AttributeTask
{
    protected Relation $relation;

    public function get(): ?\Closure
    {
        if (
            $this->model->isRelation($this->key) &&
            ($this->relation = $this->model->{$this->key}()) instanceof Relation
        ) {
            return $this->getForRelation();
        }

        return null;
    }

    abstract public function getForRelation(): ?\Closure;
}
