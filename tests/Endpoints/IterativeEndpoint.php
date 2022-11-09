<?php

namespace Nihilsen\Seeker\Tests\Endpoints;

class IterativeEndpoint extends SimpleEndpoint
{
    protected string $url = 'iterative.invalid';

    public function urlsIn($response): string|iterable
    {
        return $response->next ?? null;
    }
}
