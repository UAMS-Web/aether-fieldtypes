<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Fieldtypes\Tests\Support\Doubles;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use UAMSWeb\Aether\Fieldtypes\Contracts\EgressGuard;

/**
 * An egress guard a test controls: it records what it was asked, allows or refuses on demand, and
 * answers with a canned response or throws.
 */
class RecordingEgressGuard implements EgressGuard
{
    /**
     * Every URL the controller asked about.
     *
     * @var list<string>
     */
    public array $asked = [];

    /**
     * Every `[method, url]` pair the controller fetched.
     *
     * @var list<array{string, string}>
     */
    public array $fetched = [];

    /**
     * The request the controller's configure hook produced for the last fetch.
     */
    public ?PendingRequest $configured = null;

    /**
     * Create the double.
     *
     * @param  bool  $allow  Whether the URL is allowed
     * @param  mixed  $body  The JSON body of the canned response
     * @param  int  $status  The status of the canned response
     * @param  bool  $throw  Whether a fetch throws instead of answering
     */
    public function __construct(
        private readonly bool $allow = true,
        private readonly mixed $body = [],
        private readonly int $status = 200,
        private readonly bool $throw = false,
    ) {}

    /**
     * The value of a header the controller's configure hook set on the last fetch.
     *
     * @param  string  $name  The header name
     * @return mixed The header value, or null when none was set
     */
    public function header(string $name): mixed
    {
        $headers = $this->configured?->getOptions()['headers'] ?? null;

        return is_array($headers) ? ($headers[$name] ?? null) : null;
    }

    #[\Override]
    public function isAllowedUrl(string $url): bool
    {
        $this->asked[] = $url;

        return $this->allow;
    }

    #[\Override]
    public function fetch(string $method, string $url, ?Closure $configure = null): Response
    {
        $this->fetched[] = [$method, $url];

        if ($this->throw) {
            throw new RuntimeException('The double was told to fail.');
        }

        // Run the controller's hook over a fresh request so a test can read what it set
        $request = Http::createPendingRequest();
        $this->configured = $configure instanceof Closure ? $configure($request) : $request;

        $body = is_array($this->body) || is_string($this->body) ? $this->body : null;
        Http::fake(['*' => Http::response($body, $this->status)]);

        return Http::get($url);
    }
}
