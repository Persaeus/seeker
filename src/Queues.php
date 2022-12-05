<?php

namespace Nihilsen\Seeker;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $max_per_minute
 * @property string $name
 */
class Queues extends Model
{
    use Superclass;

    /**
     * {@inheritDoc}
     */
    protected $table = Schema::queuesTable;

    /**
     * {@inheritDoc}
     */
    public $timestamps = false;

    public function endpoints()
    {
        return $this->hasMany(Endpoints::class, 'queue_id');
    }
}
