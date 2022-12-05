<?php

use Illuminate\Database\Eloquent\Collection;
use Nihilsen\Seeker\Endpoints;
use Nihilsen\Seeker\Tests\Endpoints\ComplexEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\ComplexSeedableEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\IterativeEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\SimpleEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\SimpleSeedableEndpoint;
use Nihilsen\Seeker\Tests\Models\ComplexModel;
use Nihilsen\Seeker\Tests\Models\SimpleModel;

it('can auto-discover endpoints', function () {
    expect(Endpoints::classes())->not->toBeEmpty();
});

it('can enumerate endpoints', function () {
    $endpoints = Endpoints::all();

    expect($endpoints)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(6)
        ->and($endpoints->map(fn ($endpoint) => $endpoint::class))
        ->toContain(
            SimpleEndpoint::class,
            ComplexEndpoint::class,
            SimpleSeedableEndpoint::class,
            ComplexSeedableEndpoint::class,
            IterativeEndpoint::class
        );
});

it('can query endpoints by the models they seek', function () {
    $simpleModelTwo = SimpleModel::create(['value' => 2]);

    $simpleModelFourtyTwo = SimpleModel::create(['value' => 42]);

    $complexModelTwo = new ComplexModel(['value' => 2]);
    $complexModelTwo->simpleModel()->associate($simpleModelTwo);
    $complexModelTwo->save();

    $complexModelFourtyTwo = new ComplexModel(['value' => 42]);
    $complexModelFourtyTwo->simpleModel()->associate($simpleModelFourtyTwo);
    $complexModelFourtyTwo->save();

    expect($endpoints = Endpoints::for($simpleModelTwo)->get())
        ->toHaveCount(2)
        ->and($endpoints->first())
        ->toBeInstanceOf(SimpleEndpoint::class);

    expect($endpoints = Endpoints::for($simpleModelFourtyTwo)->get())
        ->toHaveCount(3)
        ->and($endpoints->map(fn ($endpoint) => $endpoint::class))
        ->toContain(
            ComplexEndpoint::class,
            IterativeEndpoint::class,
            SimpleEndpoint::class,
        );

    expect(Endpoints::for($complexModelTwo)->get())->toBeEmpty();

    expect($endpoints = Endpoints::for($complexModelFourtyTwo)->get())
        ->toHaveCount(1)
        ->and($endpoints->first())
        ->toBeInstanceOf(ComplexEndpoint::class);
});
