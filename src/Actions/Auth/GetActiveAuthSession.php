<?php

namespace AdamDziuk\LaravelKsef\Actions\Auth;

use AdamDziuk\LaravelKsef\Enums\KsefEnvironment;
use AdamDziuk\LaravelKsef\Http\KsefClient;
use AdamDziuk\LaravelKsef\Models\KsefAuthSession;
use Carbon\CarbonImmutable;

class GetActiveAuthSession
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * Returns a persisted session with a valid access token, transparently
     * refreshing it via the refresh token if needed. Returns null if no
     * session exists yet, or if both tokens have expired and a fresh
     * AuthenticateAndPersistSession call is required.
     */
    public function handle(string $nip, KsefEnvironment $environment): ?KsefAuthSession
    {
        $session = KsefAuthSession::query()
            ->where('nip', $nip)
            ->where('environment', $environment->value)
            ->first();

        if ($session === null) {
            return null;
        }

        if ($session->accessTokenIsValid()) {
            return $session;
        }

        if (! $session->refreshTokenIsValid()) {
            return null;
        }

        $tokens = (new RefreshAccessToken($this->client->withBearerToken($session->refresh_token)))->handle();

        $session->update([
            'access_token' => $tokens['accessToken']['token'],
            'access_token_valid_until' => CarbonImmutable::parse($tokens['accessToken']['validUntil']),
        ]);

        return $session;
    }
}
