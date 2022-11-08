<?php

namespace Nihilsen\Seeker;

use Illuminate\Database\Eloquent\Model;
use Nihilsen\Seeker\Contracts\Datable;
use Nihilsen\Seeker\Contracts\ShouldSeekContinually;

class Url extends Model implements Datable, ShouldSeekContinually
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = ['url'];

    /**
     * {@inheritDoc}
     */
    public $timestamps = false;

    public static function data(?string $url = null): static
    {
        return Data::trace(firstOrNew: true);
    }
}
