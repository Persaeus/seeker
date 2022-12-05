<?php

namespace Nihilsen\Seeker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Nihilsen\Seeker\Contracts\ShouldSeekContinually;
use Nihilsen\Seeker\Contracts\ShouldSeekOnce;
use Nihilsen\Seeker\Data;
use Nihilsen\Seeker\Queue;
use Nihilsen\Seeker\Queues;
use Nihilsen\Seeker\Response;
use Nihilsen\Seeker\Seekables;

class SeekAll implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 60 * 10; // 10 minutes

    public function __construct(
        public readonly ?Queue $endpointQueue = null
    ) {
        //
    }

    /**
     * Determine all the jobs that should be dispatched.
     *
     * @return iterable
     */
    protected function getJobs(): iterable
    {
        // If $this->endpointQueue has not been set, we take that to mean
        // that we should seek through all queues. In that case,
        // emit a corresponding SeekAll job for every queue.
        if (! $this->endpointQueue) {
            foreach (Queues::all() as $queue) {
                yield new static($queue);
            }

            return;
        }

        if ($this->endpointQueue->max_per_minute == 0) {
            return;
        }

        $jobsDispatched = 0;

        /** @var \Nihilsen\Seeker\Jobs\Seek */
        foreach ($this->getJobsForQueue() as $job) {
            if ($jobsDispatched++ >= $this->endpointQueue->max_per_minute) {
                return;
            }

            yield $job;
        }
    }

    /**
     * Determine the jobs that should be dispatched for $this->queue.
     *
     * @return iterable
     */
    protected function getJobsForQueue(): iterable
    {
        foreach ($this->getSeekIterativeJobs() as $job) {
            yield $job;
        }

        foreach ($this->getSeekJobs(seekOnce: true) as $job) {
            yield $job;
        }

        foreach ($this->getSeekJobs(seekOnce: false) as $job) {
            yield $job;
        }
    }

    /**
     * Determine the responses whose endpoints belong to $this->queue,
     * and for which the number of seekable urls has not yet been determined,
     * or for which we have not yet sought the corresponding number of urls.
     *
     * For each such response, determine which urls have yet to be followed,
     * and emit corresponding Seek job.
     *
     * @return iterable
     */
    protected function getSeekIterativeJobs(): iterable
    {
        /** @var \Illuminate\Database\Eloquent\Builder */
        $responses = Response::query()
            ->whereHas(
                'endpoint',
                fn (Builder $query) => $query->whereHas(
                    'queue',
                    fn (Builder $query) => $query->whereId($this->endpointQueue->id)
                )
            )
            ->where(
                fn (Builder $query) => $query
                    ->whereNull('seekable_urls')
                    ->orWhere(
                        fn (Builder $query) => $query
                            ->where(
                                'seekable_urls',
                                '>',
                                0
                            )
                            ->whereHas(
                                'children',
                                operator: '<',
                                count: new Expression($query->qualifyColumn('seekable_urls'))
                            )
                    )
            );

        /** @var \Nihilsen\Seeker\Response */
        foreach ($responses->cursor() as $response) {
            /** @var string */
            foreach ($response->urls() as $url) {
                yield new Seek(
                    $response->endpoint,
                    $response->seekable,
                    $response,
                    $url
                );
            }
        }
    }

    protected function getSeekJobs(bool $seekOnce): iterable
    {
        $seekables = Seekables::all();

        $seekables = Arr::where(
            $seekables,
            fn ($_, $class) => $seekOnce
                ? (
                    is_subclass_of($class, ShouldSeekOnce::class) &&
                    ! is_subclass_of($class, ShouldSeekContinually::class)
                )
                : is_subclass_of($class, ShouldSeekContinually::class)
        );

        foreach ($seekables as $class => $endpoints) {
            $class::resolveRelationUsing('soughtData', function (Model $model) {
                return $model->morphMany(
                    Data::class,
                    'datable',
                );
            });

            /** @var \Illuminate\Database\Eloquent\Builder */
            $baseQuery = $class::query();

            if ($seekOnce) {
                $baseQuery->whereDoesntHave('soughtData');
            }

            $keys = [];
            foreach ($endpoints as $class => $closure) {
                /** @var \Illuminate\Database\Eloquent\Model|null */
                $seekable = (clone $baseQuery)
                    ->where($closure)
                    ->when($keys)->whereKeyNot($keys)
                    ->first();

                if ($key = $seekable?->getKey()) {
                    $keys[] = $key;

                    yield new Seek(
                        endpoint: new $class(),
                        seekable: $seekable,
                    );
                }
            }
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->getJobs() as $job) {
            dispatch($job);
        }
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return static::class.$this->endpointQueue?->name;
    }
}
