<?php

namespace AdamDziuk\LaravelKsef\Actions\Auth;

use AdamDziuk\LaravelKsef\Http\KsefClient;

class GetAuthenticationStatus
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * @return array{authenticationMethod: string, status: array{code: int, description: string, details?: array<int, string>}}
     */
    public function handle(string $referenceNumber): array
    {
        return $this->client->get("/auth/{$referenceNumber}")->json();
    }
}
