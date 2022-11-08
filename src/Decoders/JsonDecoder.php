<?php

namespace Nihilsen\Seeker\Decoders;

class JsonDecoder extends Decoder
{
    /**
     * {@inheritDoc}
     */
    public function __invoke($input)
    {
        return json_decode($input);
    }
}
