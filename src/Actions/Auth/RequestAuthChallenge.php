<?php

namespace AdamDziuk\LaravelKsef\Actions\Auth;

use AdamDziuk\LaravelKsef\Http\KsefClient;

class RequestAuthChallenge
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * @return array{challenge: string, timestamp: string, timestampMs: int}
     */
    public function handle(): array
    {
        return $this->client->post('/auth/challenge')->json();
    }
}
