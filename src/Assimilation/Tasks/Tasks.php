<?php

namespace Nihilsen\Seeker\Assimilation\Tasks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Nihilsen\Seeker\Assimilation\Tasks\Attribute\AttributeTask;
use Nihilsen\Seeker\Assimilation\Tasks\Attribute\Relations\AssociateBelongsTo;
use Nihilsen\Seeker\Assimilation\Tasks\Attribute\Relations\SaveHasOneOrMany;
use Nihilsen\Seeker\Assimilation\Tasks\Attribute\Relations\SyncBelongsToMany;
use Nihilsen\Seeker\Assimilation\Tasks\Attribute\SetAttribute;
use Nihilsen\Seeker\Assimilation\Tasks\Model\ModelTask;
use Nihilsen\Seeker\Assimilation\Tasks\Model\SaveModel;
use Nihilsen\Seeker\Data;

/**
 * This class is responsible for enumerating and managing
 * all the tasks related to assimilating sought data into
 * actual Eloquent model.
 */
class Tasks
{
    /**
     * The task classes, in the order that each should run.
     *
     * @var class-string<\Nihilsen\Seeker\Assimilation\Tasks\Task>[]
     */
    public const CLASSES = [
        // Tasks that should run before saving the model...
        AssociateBelongsTo::class,
        SetAttribute::class,

        // The save model task.
        SaveModel::class,

        // Tasks that should run after saving the model...
        SyncBelongsToMany::class,
        SaveHasOneOrMany::class,
    ];

    /**
     * @var \Illuminate\Support\Collection<\Nihilsen\Seeker\Assimilation\Tasks\Attribute\AttributeTask>
     */
    protected static $attributeTasks;

    /**
     * @var \Illuminate\Support\Collection<\Nihilsen\Seeker\Assimilation\Tasks\Model\ModelTask>
     */
    protected static $modelTasks;

    /**
     * @var \Illuminate\Support\Collection<\Nihilsen\Seeker\Assimilation\Tasks\Task>
     */
    protected static $tasks;

    /**
     * @return \Illuminate\Support\Collection<class-string<\Nihilsen\Seeker\Assimilation\Tasks\Task>>
     */
    protected static function classes()
    {
        return collect(static::CLASSES);
    }

    /**
     * Collect and organize all the assimilation tasks for the given $data object,
     * and sort them by the order in which they should run.
     *
     * @param  \Nihilsen\Seeker\Data  $data
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Closure[]
     */
    public static function for(Data $data, ?Model &$model): array
    {
        /** @var \Illuminate\Database\Eloquent\Model */
        $prototype = $data->unpack();

        /** @var \Illuminate\Database\Eloquent\Model */
        $model = $prototype->exists
            ? $prototype::query()->find($prototype->getKey())
            : new $prototype();

        $attributes = array_merge(
            $prototype->getAttributes(),
            $prototype->getRelations()
        );

        return collect($attributes)
            ->map(fn ($value, $key) => static::forAttributeOnModel($value, $key, $model))
            ->merge(static::forModel($model))
            ->flatten()
            ->mapToGroups(fn ($closure) => [
                (new \ReflectionFunction($closure))->getClosureScopeClass()->getName() => $closure,
            ])
            ->sortKeysUsing(fn ($a, $b) => array_search($a, static::CLASSES) <=> array_search($b, static::CLASSES))
            ->flatten()
            ->all();
    }

    /**
     * @param  mixed  $value
     * @param  string  $key
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Support\Collection<\Closure>
     */
    protected static function forAttributeOnModel($value, string $key, Model $model): Collection
    {
        static::$attributeTasks ??= static::classes()->filter(fn ($class) => is_subclass_of($class, AttributeTask::class));

        return static::$attributeTasks
            ->map(fn ($class) => new $class($value, $key, $model))
            ->map(fn (AttributeTask $task) => $task->get())
            ->filter();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Support\Collection<\Closure>
     */
    protected static function forModel(Model $model): Collection
    {
        static::$modelTasks ??= static::classes()->filter(fn ($class) => is_subclass_of($class, ModelTask::class));

        return static::$modelTasks
            ->map(fn ($class) => new $class($model))
            ->map(fn (ModelTask $task) => $task->get())
            ->filter();
    }
}
