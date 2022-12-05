<?php

namespace Nihilsen\Seeker;

use Parental\HasParent;

trait Subclass
{
    use HasParent;

    /**
     * {@inheritDoc}
     */
    protected function getParentClass()
    {
        return parent::class;
    }
}
