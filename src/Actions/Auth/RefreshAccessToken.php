<?php

namespace AdamDziuk\LaravelKsef\Actions\Auth;

use AdamDziuk\LaravelKsef\Http\KsefClient;

class RefreshAccessToken
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * Client must be authenticated with the refreshToken Bearer token.
     *
     * @return array{accessToken: array{token: string, validUntil: string}}
     */
    public function handle(): array
    {
        return $this->client->post('/auth/token/refresh')->json();
    }
}
