<?php

namespace Nihilsen\Seeker;

use Illuminate\Support\Str;
use Parental\HasChildren;

trait Superclass
{
    use HasChildren;

    /**
     * {@inheritDoc}
     */
    protected $childColumn = 'class';

    /**
     * {@inheritDoc}
     */
    public function classFromAlias($alias)
    {
        return Str::start(
            $alias,
            Endpoints::namespace().'\\'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function classToAlias($className)
    {
        return Str::after($className, Endpoints::namespace().'\\');
    }

    /**
     * Get the fillable attributes for the model.
     *
     * @return array<string>
     */
    public function getFillable()
    {
        return ['class'];
    }
}
