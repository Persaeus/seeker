<?php

namespace Nihilsen\Seeker\Assimilation\Tasks\Model;

use Illuminate\Database\Eloquent\Model;
use Nihilsen\Seeker\Assimilation\Tasks\Task;

/**
 * A ModelTask is any task relating to the whole assimilatable model.
 */
abstract class ModelTask extends Task
{
    final public function __construct(
        protected Model $model
    ) {
        //
    }
}
