<?php

use Illuminate\Support\Facades\DB;
use Nihilsen\Seeker\Schema;

it('can run migrations', function () {
    expect(fn () => DB::table(Schema::endpointsTable)->get())->not->toThrow(\Exception::class);

    // Suppress "no assertions" warning
    expect(true)->toBeTrue();
});
