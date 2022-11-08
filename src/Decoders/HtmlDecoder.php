<?php

namespace Nihilsen\Seeker\Decoders;

use Symfony\Component\DomCrawler\Crawler;

class HtmlDecoder extends Decoder
{
    /**
     * {@inheritDoc}
     */
    public function __invoke($input)
    {
        return new Crawler($input);
    }
}
