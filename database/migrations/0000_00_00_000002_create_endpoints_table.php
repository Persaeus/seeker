<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Nihilsen\Seeker\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::create(Schema::endpointsTable, function (Blueprint $table) {
            Schema::endpointIdColumn($table);

            $table->string('class')
                ->collation('latin1_general_ci')
                ->unique();

            Schema::queueIdColumn(
                $table,
                $queueIdColumn = 'queue_id',
                autoIncrement: false
            )->nullable();

            $table->foreign($queueIdColumn)
                ->references('id')->on(Schema::queuesTable)
                ->onUpdate('cascade')->onDelete('set null');
        });
    }
};
