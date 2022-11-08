<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Nihilsen\Seeker\Schema;

return new class() extends Migration
{
    public function up()
    {
        // TODO: make optional

        Schema::create('urls', function (Blueprint $table) {
            $table->id();

            Schema::urlColumn($table)
                ->unique()
                ->nullable();
        });
    }
};
