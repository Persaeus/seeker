<?php

namespace Nihilsen\Seeker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Client\Response as Base;
use Illuminate\Support\Arr;
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
     * @param  \Illuminate\Database\Eloquent\Model|null  $seekable
     * @param  Endpoint  $endpoint
     * @param  string  $url
     * @param  int|null  $status
     */
    public static function failed(
        Endpoint $endpoint,
        ?Model $seekable,
        ?self $parent,
        string $url,
        ?int $status = null
    ) {
        // CONNECTION CLOSED WITHOUT RESPONSE
        $status ??= 444;

        $response = static::new(
            $endpoint,
            $seekable,
            $parent,
            $url,
            $status
        );

        $response->save();

        return $response;
    }

    public static function from(
        Base $base,
        Endpoint $endpoint,
        ?Model $seekable,
        ?self $parent,
        string $url,
    ): ?static {
        $response = static::new(
            $endpoint,
            $seekable,
            $parent,
            $url,
            $base->status()
        );

        $response->body = $base->body();

        $response->save();

        return $response;
    }

    protected static function new(
        Endpoint $endpoint,
        ?Model $seekable,
        ?self $parent,
        string $url,
        int $status
    ) {
        $response = new static();

        if ($seekable) {
            $response->seekable()->associate($seekable);
        }

        if ($parent) {
            $response->parent()->associate($parent);
        }

        $response->endpoint()->associate($endpoint);

        $response->url = $url;

        $response->status = $status;

        return $response;
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function parts(): array
    {
        return Arr::wrap($this->endpoint->partition($this->decode()));
    }

    public function seekable(): MorphTo
    {
        return $this->morphTo();
    }

    public function urls(): iterable
    {
        $urls = collect($this->endpoint->urlsIn($this->decode()));

        if (is_null($this->seekable_urls)) {
            $this->seekable_urls = $urls->count();
            $this->save();
        }

        if ($urls->isEmpty()) {
            return [];
        }

        /** @var \Illuminate\Support\Collection */
        $alreadySoughtUrls = $this
            ->load('children:url')
            ->children
            ->pluck('url');

        foreach ($urls->reject(fn ($url) => $alreadySoughtUrls->contains($url)) as $url) {
            yield $url;
        }
    }
}
