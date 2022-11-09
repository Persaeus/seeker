<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Nihilsen\Seeker\Endpoints;
use Nihilsen\Seeker\Tests\Endpoints\ComplexEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\IterativeEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\SimpleEndpoint;
use Nihilsen\Seeker\Tests\Models\ComplexModel;
use Nihilsen\Seeker\Tests\Models\SimpleModel;

beforeEach(function () {
    Bus::fake();
});

it('can seek, analyze and assimilate data from simple endpoint', function () {
    Http::fake(['simple.invalid' => Http::response('{"results": [{"value":"first"},{"value":"second"}]}')]);

    /** @var \Nihilsen\Seeker\Response */
    $response = SimpleEndpoint::first()->seek();

    expect($decoded = $response->decode())
        ->toHaveProperty('results')
        ->and($results = $decoded->results)
        ->toBeArray()
        ->toHaveCount(2)
        ->and($results[0])
        ->toHaveProperty('value', 'first')
        ->and($results[1])
        ->toHaveProperty('value', 'second');

    expect($analyzed = $response->analyze())
        ->toBeArray()
        ->toHaveCount(2)
        ->and($analyzed[0])
        ->toBeInstanceOf(SimpleModel::class)
        ->and($analyzed[0]->value)
        ->toBe('first')
        ->and($analyzed[1])
        ->toBeInstanceOf(SimpleModel::class)
        ->and($analyzed[1]->value)
        ->toBe('second');

    $response->assimilate();

    expect(SimpleModel::query()->where('value', 'first')->exists())
        ->toBeTrue()
        ->and(SimpleModel::query()->where('value', 'second')->exists())
        ->toBeTrue();
});

it('can seek, analyze and assimilate simple data from complex endpoint', function () {
    Http::fake([
        'complex.invalid/s/42' => Http::response('{"description": "sought"}'),
    ]);

    $simpleModel = SimpleModel::firstOrCreate(['value' => 42]);

    $endpoints = ComplexEndpoint::for($simpleModel)->get();
    expect($endpoints)->toHaveCount(1);

    /** @var \Nihilsen\Seeker\Endpoint */
    $endpoint = $endpoints->first();
    expect($endpoint)->toBeInstanceOf(ComplexEndpoint::class);

    $response = $endpoint
        ->seeking($simpleModel)
        ->seek();

    $analyzed = $response->analyze();

    expect($analyzed)
        ->toHaveCount(1)
        ->and($analyzed[0]->description)
        ->toBe('sought');

    $response->assimilate();

    $assimilatedModel = SimpleModel::where('value', 42)->first();
    expect($assimilatedModel)
        ->not
        ->toBeNull()
        ->and($assimilatedModel->description)
        ->toBe('sought');
});

it('can seek, analyze and assimilate complex data from complex endpoint', function () {
    Http::fake([
        'complex.invalid/c/42' => Http::response('{"description": "seekable", "simple_value": 99}'),
    ]);

    $complexModel = new ComplexModel(['value' => 42]);
    $complexModel->simpleModel()->associate(SimpleModel::firstOrCreate(['value' => 42]));
    $complexModel->save();

    $endpoints = Endpoints::for($complexModel)->get();
    expect($endpoints)->toHaveCount(1);

    /** @var \Nihilsen\Seeker\Tests\Endpoints\ComplexEndpoint */
    $endpoint = $endpoints->first();
    expect($endpoint)->toBeInstanceOf(ComplexEndpoint::class);

    $response = $endpoint
        ->seeking($complexModel)
        ->seek();

    $analyzed = $response->analyze();

    expect($analyzed)
        ->toHaveCount(2)
        ->and($analyzed[1]->description)
        ->toBe('seekable');

    $response->assimilate();

    /** @var \Nihilsen\Seeker\Tests\Models\ComplexModel */
    $assimilatedModel = ComplexModel::where('value', 42)->first();
    expect($assimilatedModel)
        ->not
        ->toBeNull()
        ->and($assimilatedModel->description)
        ->toBe('seekable')
        ->and($assimilatedModel->simpleModel?->value)
        ->toBe('99');
});

it('can seek and assimilate iteratively', function () {
    Http::fake([
        'iterative.invalid' => Http::response('{"results": [{"value":"first"}], "next": "iterative.invalid/2"}'),
        'iterative.invalid/2' => Http::response('{"results": [{"value":"second"}]}'),
    ]);

    $response = IterativeEndpoint::get()->seek();
    $response->assimilate();

    foreach ($response->urls() as $url) {
        IterativeEndpoint::get()
            ->seek(
                $url,
                parentResponse: $response
            )
            ->assimilate();
    }

    expect($models = SimpleModel::all())
        ->toHaveCount(2)
        ->and($models->map->value)
        ->toMatchArray(['first', 'second']);

    expect($response->children()->count())->toBe(1);
});
