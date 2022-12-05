<?php

use Illuminate\Support\Facades\Bus;
use Nihilsen\Seeker\Endpoints;
use Nihilsen\Seeker\Jobs\Seek;
use Nihilsen\Seeker\Jobs\SeekAll;
use Nihilsen\Seeker\Tests\Endpoints\TestQueue;
use Nihilsen\Seeker\Tests\Models\SeekableModel;

it('can schedule seek jobs', function () {
    Bus::fake();

    // Seed endpoints.
    Endpoints::all();

    SeekableModel::create(['value' => 23]);

    (new SeekAll())->handle();

    Bus::assertDispatched(
        SeekAll::class,
        fn (SeekAll $job) => isset($job->endpointQueue)
    );

    (new SeekAll(new TestQueue()))->handle();

    Bus::assertDispatched(
        Seek::class,
        fn (Seek $job) => $job->seekable->value == 23
    );
});
