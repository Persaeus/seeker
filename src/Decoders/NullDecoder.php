<?php

namespace Nihilsen\Seeker\Decoders;

class NullDecoder extends Decoder
{
    /**
     * {@inheritDoc}
     */
    public function __invoke($input)
    {
        return $input;
    }
}
