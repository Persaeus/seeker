<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Nihilsen\Seeker\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::create('complex_models', function (Blueprint $table) {
            $table->tinyIncrements('id');

            $table->string('value')->unique();

            $table->string('description')->nullable();

            $table->foreignId('simple_model_id')->constrained();
        });
    }
};
