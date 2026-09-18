<?php

namespace AdamDziuk\LaravelKsef\Http;

use AdamDziuk\LaravelKsef\Enums\KsefEnvironment;
use AdamDziuk\LaravelKsef\Exceptions\KsefApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class KsefClient
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly int $timeout = 30,
        public readonly int $retryTimes = 0,
        public readonly int $retrySleepMs = 100,
        private readonly ?string $bearerToken = null,
    ) {}

    public static function fromConfig(?KsefEnvironment $environment = null): self
    {
        $environment ??= KsefEnvironment::from(config('ksef.environment'));

        return new self(
            baseUrl: config("ksef.base_urls.{$environment->value}"),
            timeout: config('ksef.http.timeout', 30),
            retryTimes: config('ksef.http.retry_times', 0),
            retrySleepMs: config('ksef.http.retry_sleep_ms', 100),
        );
    }

    public function withBearerToken(string $token): self
    {
        return new self($this->baseUrl, $this->timeout, $this->retryTimes, $this->retrySleepMs, $token);
    }

    public function get(string $uri, array $query = []): Response
    {
        return $this->assertSuccessful($this->pendingRequest()->get($uri, $query));
    }

    public function post(string $uri, array $json = []): Response
    {
        return $this->assertSuccessful($this->pendingRequest()->post($uri, $json));
    }

    private function pendingRequest(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson();

        if ($this->retryTimes > 0) {
            $request = $request->retry($this->retryTimes, $this->retrySleepMs);
        }

        if ($this->bearerToken !== null) {
            $request = $request->withToken($this->bearerToken);
        }

        return $request;
    }

    private function assertSuccessful(Response $response): Response
    {
        if ($response->failed()) {
            throw KsefApiException::fromResponse($response);
        }

        return $response;
    }
}
