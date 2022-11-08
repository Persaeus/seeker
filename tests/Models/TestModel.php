<?php

namespace Nihilsen\Seeker\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Nihilsen\Seeker\Contracts\Datable;

abstract class TestModel extends Model implements Datable
{
    protected static $unguarded = true;

    public $timestamps = false;
}
