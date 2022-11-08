<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Nihilsen\Seeker\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::create(Schema::responsesTable, function (Blueprint $table) {
            $idColumn = Schema::responseIdColumn($table);

            $table->addColumn(
                $idColumn->type,
                $parentIdColumn = 'parent_id',
                [
                    'autoIncrement' => false,
                    'unsigned' => true,
                ]
            )->nullable();

            Schema::endpointIdColumn(
                $table,
                $endpointIdColumn = 'endpoint_id',
                autoIncrement: false
            );

            $table->nullableMorphs('seekable');

            Schema::urlColumn($table)->index();

            $table->unsignedTinyInteger('status');

            $table->longText('body')->nullable();

            $table->unsignedSmallInteger('seekable_urls')
                ->nullable()
                ->index();

            $table->timestamps();

            $table->foreign($endpointIdColumn)
                ->references('id')->on(Schema::endpointsTable)
                ->onUpdate('cascade')->onDelete('restrict');

            $table->foreign($parentIdColumn)
                ->references('id')->on(Schema::responsesTable)
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }
};
