<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Nihilsen\Seeker\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::create(Schema::dataTable, function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')
                ->constrained(Schema::responsesTable)
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->unsignedInteger('datable_id')->nullable();
            $table->string('datable_type');
            $table->json('data');
            $table->timestamps();

            $table->index(['datable_type', 'datable_id']);
        });
    }
};
