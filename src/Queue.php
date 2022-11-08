<?php

namespace Nihilsen\Seeker;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 */
class Queue extends Model
{
    public int $max_per_minute = 0;
}
