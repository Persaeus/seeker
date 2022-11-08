<?php

namespace Nihilsen\Seeker;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Nihilsen\Seeker\Contracts\Seedable;
use Nihilsen\Seeker\Scopes\MergeWithRegistered;
use Parental\HasChildren;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * @method static \Illuminate\Database\Eloquent\Builder for(\Illuminate\Database\Eloquent\Model $seekable)
 */
class Endpoints extends Model
{
    use HasChildren;

    /**
     * {@inheritDoc}
     */
    protected $childColumn = 'class';

    /**
     * The registered endpoint classes.
     *
     * @var string[]
     */
    protected static array $classes;

    /**
     * {@inheritDoc}
     */
    protected $fillable = ['class'];

    /**
     * {@inheritDoc}
     */
    protected $table = Schema::endpointsTable;

    /**
     * {@inheritDoc}
     */
    public $timestamps = false;

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new MergeWithRegistered());

        static::retrieved(function (self $endpoint) {
            $endpoint->exists = (bool) $endpoint->getKey();

            if (! $endpoint->exists) {
                $endpoint->save();
            }
        });
    }

    /**
     * Get all the registered endpoint classes
     *
     * @return class-string<\Nihilsen\Seeker\Endpoint>[]
     */
    public static function classes(): array
    {
        return static::$classes ??= Config::endpoints() ?? static::findClasses();
    }

    /**
     * {@inheritDoc}
     */
    public function classFromAlias($alias)
    {
        return Str::start(
            $alias,
            Config::namespace().'\\'
        );
    }

    public function classToAlias($className)
    {
        return Str::after($className, Config::namespace().'\\');
    }

    /**
     * Get the data retrieved using the endpoint.
     */
    public function data(): HasManyThrough
    {
        return $this->hasManyThrough(
            Data::class,
            Response::class,
            'endpoint_id', // Foreign key on the responses table
            'response_id', // Foreign key on the data table
            'id',          // Local key on the endpoints table
            'id'           // Local key on the responses table
        );
    }

    protected static function findClasses(): array
    {
        $appNamespace = app()->getNamespace();

        if (! $endpointNamespace = Str::after(Config::namespace(), $appNamespace)) {
            throw new \UnexpectedValueException('Could not resolve namespace for auto-registration of endpoint classes');
        }

        $endpointDirectory = app_path(str_replace('\\', '/', $endpointNamespace));

        try {
            $finder = Finder::create()->in($endpointDirectory);
        } catch (DirectoryNotFoundException) {
            return [];
        }

        return collect($finder)
            ->map(
                fn ($path) => Str::of($path)
                    ->after(app_path().DIRECTORY_SEPARATOR)
                    ->replace(
                        ['/', '.php'],
                        ['\\', ''],
                    )
                    ->prepend($appNamespace)
                    ->toString()
            )
            ->filter(
                fn ($class) => (
                    is_subclass_of($class, Endpoint::class) &&
                    ! (new \ReflectionClass($class))->isAbstract()
                )
            )
            ->values()
            ->all();
    }

    /**
     * Get the responses obtained from the endpoint.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * Scope the query to include endpoints for the given seekable.
     */
    public function scopeFor(Builder $query, Model $seekable): Builder
    {
        $query->where(function ($query) use ($seekable) {
            foreach (Seekables::get($seekable::class) as $class => $closure) {
                $query->orWhere(function ($query) use ($seekable, $class, $closure) {
                    $query->whereClass($this->classToAlias($class));

                    /** @var \Closure[] $closures */
                    $subquery = $seekable->newQuery()
                        ->whereKey($seekable->getKey())
                        ->where($closure);

                    $query->whereRaw("exists ({$subquery->toSql()})");
                    $query->addBinding($subquery->getBindings(), 'where');

                    return $query;
                });
            }
        });

        return $query;
    }

    /**
     * Get all the "seedable" endpoints.
     *
     * @return array
     */
    public static function seedable(): array
    {
        return array_filter(
            static::classes(),
            fn ($class) => is_subclass_of($class, Seedable::class)
        );
    }
}
