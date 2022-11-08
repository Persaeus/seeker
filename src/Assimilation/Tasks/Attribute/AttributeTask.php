<?php

namespace Nihilsen\Seeker\Assimilation\Tasks\Attribute;

use Illuminate\Database\Eloquent\Model;
use Nihilsen\Seeker\Assimilation\Tasks\Task;

/**
 * An AttributeTask is any task that relates to a single
 * attribute on the assimilatable model.
 */
abstract class AttributeTask extends Task
{
    final public function __construct(
        protected mixed $value,
        protected string $key,
        protected Model $model
    ) {
        //
    }
}
