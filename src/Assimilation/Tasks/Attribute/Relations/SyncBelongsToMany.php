<?php

namespace Nihilsen\Seeker\Assimilation\Tasks\Attribute\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Nihilsen\Seeker\Assimilation\Tasks\Attribute\SetIterableRelation;

class SyncBelongsToMany extends SetIterableRelation
{
    public function getForRelation(): ?\Closure
    {
        if ($this->relation instanceof BelongsToMany) {
            return fn () => $this->relation->sync($this->models);
        }

        return null;
    }
}
