<?php

use Nihilsen\Seeker\Seekables;
use Nihilsen\Seeker\Tests\Endpoints\ComplexSeedableEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\SimpleSeedableEndpoint;
use Nihilsen\Seeker\Tests\Models\SeedableModel;

it('can seed models from seedable endpoints', function () {
    expect($seeded = Seekables::seed())
        ->toHaveCount(2)
        ->toHaveKeys([
            SimpleSeedableEndpoint::class,
            ComplexSeedableEndpoint::class,
        ]);

    /** @var \Illuminate\Database\Eloquent\Collection */
    $seededSimple = $seeded->get(SimpleSeedableEndpoint::class);
    expect($seededSimple->first())->toBeInstanceOf(SeedableModel::class);

    /** @var \Illuminate\Database\Eloquent\Collection */
    $seededComplex = $seeded->get(ComplexSeedableEndpoint::class);
    expect($seededComplex)
        ->toHaveCount(5)
        ->and($seededComplex->map->value)
        ->toContain(1, 2, 3, 4, 5);
});

it('can save seeded models', function () {
    Seekables::seed(save: true);

    expect(SeedableModel::query()->exists())->toBeTrue();
});
