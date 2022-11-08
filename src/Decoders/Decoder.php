<?php

namespace Nihilsen\Seeker\Decoders;

abstract class Decoder
{
    /**
     * Decode the given input
     *
     * @param  mixed  $input
     * @return mixed
     */
    abstract public function __invoke($input);
}
