<?php

namespace Nihilsen\Seeker\Assimilation\Tasks;

abstract class Task
{
    /**
     * Get a closure for the task, if one can be determined.
     *
     * @return \Closure|null
     */
    abstract public function get(): ?\Closure;
}
