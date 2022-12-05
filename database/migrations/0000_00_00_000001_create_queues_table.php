<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Nihilsen\Seeker\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::create(Schema::queuesTable, function (Blueprint $table) {
            Schema::queueIdColumn($table);

            $table->string('class')
                ->collation('latin1_general_ci')
                ->unique();

            $table->unsignedSmallInteger('max_per_minute')->nullable();
        });
    }
};
