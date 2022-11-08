<?php

namespace Nihilsen\Seeker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nihilsen\Seeker\Config;
use Nihilsen\Seeker\Endpoint;
use Nihilsen\Seeker\Endpoints;
use Nihilsen\Seeker\Exceptions\UnsuccessfulSeekAttempt;

class Seek implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $attempts = 0;

    public function __construct(
        protected Model $seekable,
        protected ?Endpoint $endpoint = null,
        protected ?string $url = null
    ) {
        //
    }

    public function endpoint(): ?Endpoint
    {
        return $this->endpoint ??= Endpoints::for($this->seekable)->first();
    }

    public function handle()
    {
        $final = ++$this->attempts >= $this->endpoint::MAX_ATTEMPTS;

        try {
            $this->endpoint
                ->seeking($this->seekable)
                ->seek(
                    $this->url,
                    $final
                );
        } catch (\Throwable $th) {
            if (
                $final ||
                ! $th instanceof UnsuccessfulSeekAttempt
            ) {
                return $this->fail($th);
            }

            return $this->release($this->endpoint::REATTEMPT_DELAY);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function middleware()
    {
        return [
            /**
             * Ensure that endpoint is fetchable
             */
            new class()
            {
                public function handle(Seek $job, $next)
                {
                    if (! $job->endpoint()) {
                        $job->delete();

                        return;
                    }

                    return $next($job);
                }
            },
            ...$this->rateLimitingMiddleware(),
        ];
    }

    protected function rateLimitingMiddleware(): iterable
    {
        $class = Config::rateLimiter();

        if (! $class) {
            return [];
        }

        return [new $class(self::class)];
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addMinutes(60 * 24 * 7); // seven days
    }
}
