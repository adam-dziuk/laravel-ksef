<?php

namespace AdamDziuk\LaravelKsef\Actions\Auth;

use AdamDziuk\LaravelKsef\Http\KsefClient;

class RedeemAccessToken
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * Client must be authenticated with the authenticationToken Bearer token.
     * Tokens can only be redeemed once per authentication operation.
     *
     * @return array{accessToken: array{token: string, validUntil: string}, refreshToken: array{token: string, validUntil: string}}
     */
    public function handle(): array
    {
        return $this->client->post('/auth/token/redeem')->json();
    }
}
