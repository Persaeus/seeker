<?php

namespace Nihilsen\Seeker;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property int $id
 * @property int $max_per_minute
 * @property string $name
 */
abstract class Queue extends Queues
{
    use Subclass;

    /**
     * Set the default $max_per_minute.
     *
     * @var int
     */
    public const MAX_PER_MINUTE = 0;

    /**
     * Get or create model.
     *
     * @return static
     */
    public static function get(): static
    {
        return static::first() ?? tap(new static(), fn (self $queue) => $queue->save());
    }

    /**
     * Get the $max_per_limit attribute if the database value has been set,
     * or otherwise fallback to the class-encoded value.
     *
     * @param  int  $value
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function maxPerMinute(): Attribute
    {
        return new Attribute(fn ($value) => $value ?? static::MAX_PER_MINUTE);
    }
}
