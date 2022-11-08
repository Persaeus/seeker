<?php

namespace Nihilsen\Seeker\Assimilation\Tasks\Model;

class SaveModel extends ModelTask
{
    public function get(): ?\Closure
    {
        return fn () => $this->model->save();
    }
}
