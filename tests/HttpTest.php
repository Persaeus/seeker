<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('can make http requests', function () {
    Http::fake(function (Request $request) {
        return Http::response('ok');
    });

    $response = Http::get('domain.invalid');

    expect($response->status())->toBe(200);
});
