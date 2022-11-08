<?php

namespace Nihilsen\Seeker;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\Schema as Base;

class Schema extends Base
{
    private const PREFIX = 'seeker_';

    public const dataTable = self::PREFIX.'data';

    public const endpointsTable = self::PREFIX.'endpoints';

    public const responsesTable = self::PREFIX.'responses';

    public const queuesTable = self::PREFIX.'queues';

    /**
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public static function responseIdColumn(
        Blueprint $table,
        string $column = 'id',
        bool $autoIncrement = true
    ): ColumnDefinition {
        return $table->unsignedBigInteger(
            $column,
            $autoIncrement
        );
    }

    /**
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public static function endpointIdColumn(
        Blueprint $table,
        string $column = 'id',
        bool $autoIncrement = true
    ): ColumnDefinition {
        return $table->unsignedSmallInteger(
            $column,
            $autoIncrement
        );
    }

    /**
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public static function queueIdColumn(
        Blueprint $table,
        string $column = 'id',
        bool $autoIncrement = true
    ): ColumnDefinition {
        return $table->unsignedTinyInteger(
            $column,
            $autoIncrement,
        );
    }

    /**
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     * @param  string  $column
     * @param  int  $length
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public static function urlColumn(
        Blueprint $table,
        string $column = 'url',
        int $length = 2083
    ): ColumnDefinition {
        return $table->string($column, $length)->collation('latin1_general_ci');
    }
}
