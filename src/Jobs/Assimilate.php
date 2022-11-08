<?php

namespace Nihilsen\Seeker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Nihilsen\Seeker\Data;
use Nihilsen\Seeker\Response;

class Assimilate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected Response $response,
        protected string $key
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        try {
            $models = Arr::wrap($this->response->analyze($this->key));

            foreach ($models as $model) {
                Data::for($model)->assimilate();
            }
        } catch (\Throwable $exception) {
            $this->fail($exception);
        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        // WithoutOverlapping needed to avoid two processes parsing the same data.
        return [(new WithoutOverlapping())->expireAfter(60 * 3)];
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addDay(2);
    }
}
