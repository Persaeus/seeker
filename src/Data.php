<?php

namespace Nihilsen\Seeker;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Nihilsen\Seeker\Assimilation\Tasks\Tasks;
use Nihilsen\Seeker\Contracts\Datable;
use Nihilsen\Seeker\Exceptions\UnexpectedValueException;

/**
 * @property array $data
 * @property-read \Illuminate\Database\Eloquent\Model|null $datable
 * @property string $datable_type
 */
class Data extends Model
{
    public const PRIVATE_ATTRIBUTES = [
        self::CREATED_AT,
        self::UPDATED_AT,
        'datable_id',
        'datable_type',
        'datable',
        'id',
        'response_id',
        'response',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = self::PRIVATE_ATTRIBUTES;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = self::PRIVATE_ATTRIBUTES;

    /**
     * Indicates whether attributes are snake cased on arrays.
     *
     * @var bool
     */
    public static $snakeAttributes = false;

    /**
     * {@inheritDoc}
     */
    protected $table = Schema::dataTable;

    /**
     * Assimilate the data to matching models.
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \PDOException
     */
    public function assimilate(): Model
    {
        // Ensure that data model is saved before proceeding with assimilation.
        $this->saveOrFail();

        return DB::transaction(function () {
            foreach (Tasks::for($this, $model) as $task) {
                $task();
            }

            $this->datable()->associate($model);

            $this->save();

            return $model;
        });
    }

    public static function booted()
    {
        static::retrieved(function (self $data) {
            if (is_null($data->datable)) {
                $data->setRelation(
                    'datable',
                    $data->unpack(
                        $data->data,
                        new $data->datable_type()
                    )
                );
            }
        });

        static::saving(function (self $data) {
            $attributes = array_intersect_key(
                $data->getAttributes(),
                array_flip($data->getHidden())
            );

            $array = $data->pack();

            $data->setRawAttributes($attributes);

            $data->data = $array;
        });
    }

    /**
     * Get the relation for the datable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function datable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a Data object for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $datable
     * @return static
     */
    public static function for(Model $datable): static
    {
        return (new static())->datable()->associate($datable);
    }

    /**
     * Pack the given $datable element recursively
     * into a plain, serializable array format.
     *
     * @param  mixed  $datable
     * @return mixed
     */
    public function pack(mixed $datable = null): mixed
    {
        if (is_null($datable)) {
            return $this->pack($this->datable);
        }

        if (is_iterable($datable)) {
            return array_map(
                $this->pack(...),
                is_array($datable) ? $datable : iterator_to_array($datable)
            );
        }

        if (! $datable instanceof Arrayable) {
            return $datable;
        }

        if (! $datable instanceof Model || ! $datable instanceof Datable) {
            return $datable->toArray();
        }

        // To pack a datable model, we first attempt to filter
        // and pack existing attributes and relations based on the
        // parameters in the datable(...) instantiator method.

        $attributes = array_merge(
            $datable->getAttributes(),
            $datable->getRelations()
        );

        $parameters = collect((new \ReflectionFunction($datable::data(...)))->getParameters())
            ->map(fn (\ReflectionParameter $parameter) => $parameter->name);

        $collection = $parameters
            ->filter(fn (string $parameter) => array_key_exists($parameter, $attributes))
            ->mapWithKeys(fn (string $parameter) => [$parameter => $attributes[$parameter]]);

        // If the filtering yields an empty array, we try to supplement
        // missing instantiator parameters using dynamic attributes.
        if ($collection->isEmpty()) {
            $collection = $parameters
                ->mapWithKeys(fn (string $parameter) => [$parameter => rescue(fn () => $datable->$parameter)])
                ->filter();
        }

        return $collection
            ->map($this->pack(...))
            ->all();
    }

    /**
     * Get the response that yielded this data.
     */
    public function response()
    {
        return $this->belongsTo(Response::class, 'response_id');
    }

    /**
     * Make a datable prototype model, and backtrace to infer its
     * attributes from the arguments passed to the calling method.
     *
     * @param  array<string|int,mixed|callable>  $append Attributes to be appended.
     * @param  array|bool  $firstOrNew Whether or not we should attempt "firstOrNew" instantiation.
     *                                 If an array is given, attempt to retrieve the record
     *                                 using those columns, otherwise use all attributes.
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function trace(
        array|string $append = [],
        array|bool $firstOrNew = false
    ): Model {
        /**
         * @var class-string<\Illuminate\Database\Eloquent\Model> $class
         * @var string $function
         * @var array $args
         */
        extract(Arr::last(debug_backtrace(0, limit: 2)));

        $method = new \ReflectionMethod($class, $function);
        $parameters = $method->getParameters();

        $attributes = collect($args)
            ->reject(fn ($arg) => is_null($arg))

            // Convert model arrays to Eloquent collections
            ->map(
                fn ($arg) => (
                    is_array($arg) &&
                    is_null(Arr::first($arg, fn ($e) => ! $e instanceof Model))
                )
                    ? Collection::make($arg)
                    : $arg
            )

            ->mapWithKeys(fn ($value, $key) => [$parameters[$key]->name => $value])
            ->all();

        /** @var \Illuminate\Database\Eloquent\Model */
        $model = $class::unguarded(
            fn () => $firstOrNew !== false
                ? $class::query()->firstOrNew(
                    is_array($firstOrNew)
                        ? array_intersect_key(
                            $attributes,
                            array_flip($firstOrNew)
                        )
                        : $attributes
                )
                : new $class()
        );

        // Append primary key if model exists, as it may
        // be needed for subsequent assimilation tasks.
        if ($model->exists) {
            $attributes[$model->getKeyName()] = $model->getKey();
        }

        $model->setRawAttributes($attributes);

        foreach (Arr::wrap($append) as $key => $value) {
            if (is_string($value) && is_int($key)) {
                $key = $value;
                $value = $model->$key;
            }

            if (is_int($key)) {
                throw new UnexpectedValueException('Cannot append attributes with numeric keys.');
            }

            if (is_callable($value)) {
                $value = $value($model);
            }

            $model->$key = $value;
        }

        return $model;
    }

    /**
     * Unpack the given $datable using given $data.
     *
     * @param  mixed  $data
     * @param  object|null  $datable
     */
    public function unpack(mixed $data = null, ?object $datable = null)
    {
        if (is_null($data)) {
            return $this->unpack($this->pack(), $datable);
        }

        if (is_null($datable)) {
            return $this->unpack($data, $this->datable);
        }

        if (Arr::isList($data)) {
            return array_map(
                fn ($value) => $this->unpack($value, $datable),
                $data
            );
        }

        if (! $datable instanceof Model) {
            return $data;
        }

        if (! $datable instanceof Datable) {
            return $datable::query()->firstOrNew($data);
        }

        $instantiator = new \ReflectionFunction($datable::data(...));

        $args = array_map(
            function (\ReflectionParameter $parameter) use ($datable, $data) {
                $data = $data[$parameter->name] ?? null;

                if (is_null($data)) {
                    return $data;
                }

                $type = $parameter->getType();

                if (! $type instanceof \ReflectionNamedType) {
                    return $data;
                }

                $typeName = $type->getName();

                if (
                    $typeName == 'array' &&
                    $datable->isRelation($relationName = $parameter->name) &&
                    ($relation = $datable->$relationName()) instanceof Relation
                ) {
                    return array_map(
                        fn ($data) => $this->unpack(
                            $data,
                            $relation->getRelated()
                        ),
                        $data
                    );
                }

                if (
                    ! $type->isBuiltin() &&
                    ! $data instanceof $typeName
                ) {
                    return $this->unpack(
                        $data,
                        new $typeName()
                    );
                }

                return $data;
            },
            $instantiator->getParameters()
        );

        return $instantiator->invoke(...$args);
    }
}
