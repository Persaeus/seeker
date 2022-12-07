<?php

namespace Nihilsen\Seeker;

use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Nihilsen\Seeker\Decoders\Decoder;
use Nihilsen\Seeker\Decoders\JsonDecoder;
use Nihilsen\Seeker\Decoders\NullDecoder;
use Nihilsen\Seeker\Exceptions\UnexpectedValueException;
use Nihilsen\Seeker\Exceptions\UnsuccessfulSeekAttempt;

abstract class Endpoint extends Endpoints
{
    use Subclass;

    /**
     * The maximum number of times we should attempt to
     * request data at the endpoint for a given seek job.
     *
     * @var int
     */
    public const MAX_ATTEMPTS = 3;

    /**
     * The delay in seconds after which a failed seek job
     * should be reattempted, if it hasn't yet exceeded
     * static::MAX_ATTEMPTS.
     */
    public const REATTEMPT_DELAY = 60 * 60; // one hour

    /**
     * The Decoder class responsible for decoding the raw response,
     * assuming decode() or decoder() has not been overwritten.
     *
     * @var null|class-string<\Nihilsen\Seeker\Decoders\Decoder>
     */
    protected ?string $decoder = JsonDecoder::class;

    /**
     * The default Queue class (for rate-limiting purposes)
     *
     * @var class-string<\Nihilsen\Seeker\Queue>
     */
    public static string $defaultQueue;

    /**
     * The http request method to use.
     *
     * @var string
     */
    protected string $method = 'GET';

    /**
     * The http options to use in the request.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * The seekable model, which will be set during the seek procedure.
     *
     * @var mixed
     */
    protected $seekable;

    /**
     * The url to use in the http request.
     *
     * @var string
     */
    protected string $url = '';

    /**
     * Analyze the decoded $part, with given $key of the response.
     *
     * @param  mixed  $part
     * @param  string  $key
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Model[]
     */
    public function analyze($part, string $key): Model|array
    {
        return [];
    }

    /**
     * @return void
     *
     * @throws ReflectionException
     */
    public static function booted(): void
    {
        // Change HasParent scope to use unqualified
        // column name, so as to not exclude results
        // injected via the MergeEndpoint scope.
        unset(static::$globalScopes[static::class]);
        parent::booted();
        static::addGlobalScope(fn (Builder $query) => $query->where(
            ($instance = new static())->getInheritanceColumn(),
            $instance->classToAlias(get_class($instance))
        ));
    }

    /**
     * Perform any pre-seeking checks and adjustments.
     *
     * Any exception thrown will be caught and logged by the seek job.
     *
     * @throws \Throwable
     */
    public function check()
    {
        //
    }

    /**
     * Decode the given response
     *
     * @param  mixed  $encoded
     * @return mixed
     */
    public function decode($encoded)
    {
        return $this->decoder()($encoded);
    }

    /**
     * Get the decoder.
     *
     * @return \Nihilsen\Seeker\Decoders\Decoder
     */
    protected function decoder(): Decoder
    {
        $this->decoder ??= NullDecoder::class;

        return new $this->decoder();
    }

    /**
     * Get the concrete endpoint.
     *
     * @return static
     */
    public static function get(): static
    {
        return static::first();
    }

    /**
     * Get the http method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get the http options array.
     *
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * Partition the given decoded response.
     * Each part will be analyzed separately.
     *
     * @param  mixed  $decodedResponse
     */
    public function partition($decodedResponse)
    {
        return $decodedResponse;
    }

    /**
     * Get the query closures keyed by the class of the model for which they apply.
     *
     * @return array<class-string<\Illuminate\Database\Eloquent\Model>,\Closure>
     */
    public static function queries(): array
    {
        return collect(static::seeks())
            ->mapWithKeys(function ($value, $key) {
                // If the $value is string, we take that to mean the endpoint
                // should apply to all models of the given $value class, and
                // substitute a null query.
                if (is_string($value)) {
                    return [$value => fn ($query) => $query];
                }

                if (! $value instanceof \Closure) {
                    throw new UnexpectedValueException('Unexpected value in seeks() method of '.static::class);
                }

                return [$key => $value];
            })
            ->all();
    }

    /**
     * Get the http PendingRequest
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    public function request(): PendingRequest
    {
        return Http::withUserAgent($this->userAgent());
    }

    /**
     * Get the sought responses.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * Seek using given parameters, and optionally using the provided $url.
     *
     * @param  string|null  $url
     * @param  bool  $final Whether this attempt is the final of multiple attempts,
     *                       in which case a "failed" response model is created to
     *                       signal that the seekable has been sought unsuccessfully.
     * @return Response|null
     *
     * @throws \Throwable
     */
    public function seek(
        ?string $url = null,
        bool $final = false,
        ?Response $parentResponse = null,
    ): ?Response {
        $this->url = $url ?? $this->url();

        try {
            $response = $this->request()->send(
                $this->method(),
                $this->url,
                $this->options()
            );
        } catch (\Throwable $th) {
            $failed = $th;
        }

        if ($failed ??= ! $response->successful()) {
            if ($final) {
                Response::failed(
                    $this,
                    $this->seekable,
                    $parentResponse,
                    $this->url,
                    $response?->status()
                );
            }

            throw new UnsuccessfulSeekAttempt($failed);
        }

        return Response::from(
            $response,
            $this,
            $this->seekable,
            $parentResponse,
            $this->url,
            $parentResponse
        );
    }

    /**
     * Set the $seekable model.
     *
     * @param  mixed  $seekable
     * @return static
     */
    public function seeking($seekable): static
    {
        $this->seekable = $seekable;

        return $this;
    }

    /**
     * Get the seekable classes that can be sought with this seeker.
     *
     * @return string|array<string|int,string|\Closure>
     */
    abstract public static function seeks(): string|array;

    /**
     * Get the URL for the http request.
     *
     * @return string
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * Determine url(s) for the given decoded $response from which to continue seeking.
     *
     * @param  mixed  $response
     * @return string|string[]
     */
    public function urlsIn($response): string|iterable
    {
        return [];
    }

    /**
     * Get the user agent for the http request.
     *
     * By default, we construct a user agent of
     * the format <agent>/<version> derived from
     * the namespace and the composer package version.
     *
     * @return string
     */
    public function userAgent(): string
    {
        $agent = class_basename(__NAMESPACE__);

        $package = str(__NAMESPACE__)
            ->replace('\\', '/')
            ->lower()
            ->toString();

        $version = InstalledVersions::getVersion($package);

        return $agent.'/'.$version;
    }
}
