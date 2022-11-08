<?php

namespace Nihilsen\Seeker\Assimilation\Tasks\Attribute\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nihilsen\Seeker\Assimilation\Tasks\Attribute\SetRelation;

class AssociateBelongsTo extends SetRelation
{
    public function getForRelation(): ?\Closure
    {
        if (
            $this->value instanceof Model &&
            $this->relation instanceof BelongsTo
        ) {
            return fn () => $this->relation->associate($this->value);
        }
    }
}
