<?php

namespace Nihilsen\Seeker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Client\Response as Base;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Nihilsen\Seeker\Jobs\Assimilate;

/**
 * @property string $body
 * @property-read \Illuminate\Support\Collection $children
 * @property-read \Illuminate\Database\Eloquent\Model $seekable
 * @property int|null $seekable_urls
 * @property-read \Nihilsen\Seeker\Endpoint $endpoint
 * @property string $url
 */
class Response extends Model
{
    /**
     * {@inheritDoc}
     */
    protected $table = Schema::responsesTable;

    /**
     * Analyze the response, or part of the response with given $key.
     *
     * @param  string|null  $key
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    public function analyze(?string $key = null): array
    {
        if (isset($key)) {
            $part = $this->parts()[$key];

            $analyzed = $this->endpoint
                ->seeking($this->seekable)
                ->analyze($part, $key, $this);

            return Arr::wrap($analyzed);
        }

        return collect($this->parts())
            ->keys()
            ->flatMap(fn ($key) => $this->analyze($key))
            ->all();
    }

    /**
     * Assimilate the response, or part of the response with the given $key
     *
     * @param  string|null  $key
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    public function assimilate(?string $key = null): array
    {
        if (isset($key)) {
            return array_map(
                fn (Model $model) => Data::for($model)
                    ->response()->associate($this)
                    ->assimilate(),
                $this->analyze($key)
            );
        }

        return collect($this->parts())
            ->keys()
            ->flatMap(fn ($key) => $this->assimilate($key))
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public static function booted()
    {
        static::created(function (self $response) {
            foreach (array_keys($response->parts()) as $key) {
                Assimilate::dispatch($response, $key);
            }
        });
    }

    public function count()
    {
        return count($this->parts());
    }

    /**
     * Get the child responses.
     */
    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function decode()
    {
        return $this->endpoint->decode($this->body);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(\Nihilsen\Seeker\Endpoints::class);
    }

    /**
     * Create a "failed" response row.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $seekable
     * @param  Endpoint  $endpoint
     * @param  string  $url
     */
    public static function failed(
        Model $seekable,
        Endpoint $endpoint,
        string $url,
        ?int $status = null
    ) {
        // CONNECTION CLOSED WITHOUT RESPONSE
        $status ??= 444;

        $response = static::new(
            $seekable,
            $endpoint,
            $url,
            $status
        );

        $response->save();

        return $response;
    }

    public static function from(
        Base $base,
        ?Model $seekable,
        Endpoint $endpoint,
        string $url
    ): ?static {
        $response = static::new(
            $seekable,
            $endpoint,
            $url,
            $base->status()
        );

        $response->body = $base->body();

        $response->save();

        return $response;
    }

    /**
     * Get the urls from which to continue seeking.
     *
     * @return \Illuminate\Support\Collection
     */
    public function iterableUrls(): Collection
    {
        return collect($this->endpoint->iterate($this->decode()));
    }

    protected static function new(
        ?Model $seekable,
        Endpoint $endpoint,
        string $url,
        int $status
    ) {
        $response = new static();

        if ($seekable) {
            $response->seekable()->associate($seekable);
        }

        $response->endpoint()->associate($endpoint);

        $response->url = $url;

        $response->status = $status;

        return $response;
    }

    public function parts(): array
    {
        return Arr::wrap($this->endpoint->partition($this->decode()));
    }

    public function seekable(): MorphTo
    {
        return $this->morphTo();
    }
}
