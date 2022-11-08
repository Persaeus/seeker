<?php

namespace Nihilsen\Seeker;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class Seekables
{
    /**
     * @var array
     */
    protected static array $seekables;

    /**
     * Get all the seekables with their endpoints and corresponding query closures.
     *
     * @return array<class-string<\Illuminate\Database\Eloquent\Model>,array<class-string<\Nihilsen\Seeker\Endpoint>,\Closures[]>>
     */
    public static function all(): array
    {
        if (! isset(static::$seekables)) {
            static::load();
        }

        return static::$seekables;
    }

    /**
     * Get the models that are seekable via the given $endpoint object or class.
     *
     * @param  class-string<\Nihilsen\Seeker\Endpoint>|\Nihilsen\Seeker\Endpoint  $endpoint
     * @param  bool  $seed
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function for(string|Endpoint $endpoint, bool $seed = false): Collection
    {
        $collection = collect($endpoint::queries())
            ->map(fn ($closure, $class) => static::query($class, $closure, seed: $seed))
            ->flatten()
            ->values();

        return Collection::make($collection);
    }

    /**
     * Get the endpoints and their matching query closures for the given seekable class.
     *
     * @param  string  $seekable
     * @return array[]
     */
    public static function get(string $seekable): array
    {
        return static::all()[$seekable];
    }

    /**
     * Load the endpoints and enumerate their seekable models and closures.
     */
    protected static function load()
    {
        static::$seekables = [];

        foreach (Endpoints::classes() as $endpoint) {
            foreach ($endpoint::queries() as $seekable => $closure) {
                static::$seekables[$seekable][$endpoint] = $closure;
            }
        }
    }

    protected static function make(string $class, Builder $query): Collection|false
    {
        // Filter out any complex queries.
        if (
            $query->joins ||
            $query->unions ||
            $query->havings ||

            // Filter out any with operators not "="
            array_filter(
                $query->wheres,
                fn ($where) => (
                    isset($where['operator']) &&
                    $where['operator'] !== '='
                )
            )
        ) {
            return false;
        }

        /**
         * If the query encodes only supported WHERE AND conditions,
         * instantiate the model using each WHERE constraint to fill
         * the corresponding attributes.
         */
        $validWhereAndTypes = ['Basic', 'In', 'Null'];
        if (
            count($andConstraints = array_filter(
                $query->wheres,
                fn ($where) => (
                    $where['boolean'] == 'and' &&
                    in_array($where['type'], $validWhereAndTypes)
                )
            )) == count($query->wheres) ||
            empty($query->wheres)
        ) {
            /** @var \Illuminate\Database\Eloquent\Model */
            $model = new $class();

            foreach (array_filter(
                $andConstraints,
                fn ($where) => in_array(
                    $where['type'],
                    ['Basic', 'Null']
                )
            ) as $where) {
                $model->{$where['column']} = $where['type'] == 'Null'
                    ? null
                    : $where['value'];
            }

            if ($inConstraints = array_values(array_filter(
                $andConstraints,
                fn ($where) => $where['type'] == 'In'
            ))) {
                $cartesianProduct = Arr::crossJoin(...array_map(
                    fn ($where) => $where['values'],
                    $inConstraints
                ));

                $models = array_map(
                    fn (array $columns) => (clone $model)->forceFill($columns),
                    array_map(
                        fn ($columns) => Arr::keyBy(
                            $columns,
                            fn ($_, $index) => $inConstraints[$index]['column']
                        ),
                        $cartesianProduct
                    )
                );
            } else {
                $models = [$model];
            }

            return Collection::make($models);
        }

        /**
         * If the query encodes only WHERE OR conditions, treat each
         * condition as its own query and make recursively.
         */
        $wheres = collect($query->wheres);
        if (
            $wheres->sum(fn ($where) => $where['type'] == 'and') < 2 &&
            $wheres->every(fn ($where) => (
                in_array($where['type'], [...$validWhereAndTypes, 'Nested']) &&
                in_array($where['boolean'], ['and', 'or'])
            ))
        ) {
            $models = $wheres
                ->map(fn ($orConstraint) => static::make(
                    $class,
                    match ($orConstraint['type']) {
                        'Basic', 'Null' => DB::query()->where(
                            $orConstraint['column'],
                            $orConstraint['value'] ?? null,
                        ),
                        'In' => DB::query()->whereIn(
                            $orConstraint['column'],
                            $orConstraint['values']
                        ),
                        'Nested' => $orConstraint['query']
                    }
                ))
                ->flatten();

            return Collection::make($models);
        }

        return false;
    }

    /**
     * @param  bool  $save
     * @return \Illuminate\Support\Collection<class-string<\Nihilsen\Seeker\Tests\Endpoints\SimpleSeedableEndpoint>,\Illuminate\Database\Eloquent\Collection>
     */
    public static function seed($save = false)
    {
        return collect(Endpoints::seedable())
            ->mapWithKeys(fn ($class) => [$class => static::for($class, seed: true)])
            ->when($save)->each(
                fn (Collection $models) => $models->each(fn (Model $model) => $model->save())
            );
    }

    protected static function query(
        string $class,
        $closure = null,
        bool $seed = false
    ): Collection {
        /** @var \Illuminate\Database\Eloquent\Model $class */
        $query = $class::query();

        if ($closure instanceof \Closure) {
            $closure($query);
        }

        $results = (clone $query)->get();

        if ($seed && $results->isEmpty()) {
            return static::make($class, $query->getQuery()) ?: $results;
        }

        return $results;
    }
}
