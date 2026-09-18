<?php

namespace AdamDziuk\LaravelKsef\Actions\Auth;

use AdamDziuk\LaravelKsef\Enums\KsefEnvironment;
use AdamDziuk\LaravelKsef\Exceptions\KsefAuthenticationException;
use AdamDziuk\LaravelKsef\Http\KsefClient;
use AdamDziuk\LaravelKsef\Models\KsefAuthSession;
use Carbon\CarbonImmutable;

class AuthenticateAndPersistSession
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * Runs the full KSeF-token authentication flow (challenge, encrypted token
     * submission, status polling, token redeem) and persists the resulting
     * access/refresh tokens for the given NIP + environment.
     */
    public function handle(string $nip, string $ksefToken, KsefEnvironment $environment): KsefAuthSession
    {
        $initiation = (new AuthenticateWithKsefToken($this->client))->handle($nip, $ksefToken);

        $authenticationClient = $this->client->withBearerToken($initiation['authenticationToken']['token']);

        $this->waitForSuccessfulStatus($authenticationClient, $initiation['referenceNumber']);

        $tokens = (new RedeemAccessToken($authenticationClient))->handle();

        return KsefAuthSession::query()->updateOrCreate(
            ['nip' => $nip, 'environment' => $environment->value],
            [
                'access_token' => $tokens['accessToken']['token'],
                'access_token_valid_until' => CarbonImmutable::parse($tokens['accessToken']['validUntil']),
                'refresh_token' => $tokens['refreshToken']['token'],
                'refresh_token_valid_until' => CarbonImmutable::parse($tokens['refreshToken']['validUntil']),
            ],
        );
    }

    private function waitForSuccessfulStatus(KsefClient $client, string $referenceNumber): void
    {
        $maxAttempts = config('ksef.auth.poll_max_attempts', 40);
        $intervalMs = config('ksef.auth.poll_interval_ms', 500);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $status = (new GetAuthenticationStatus($client))->handle($referenceNumber)['status'];

            if ($status['code'] === 200) {
                return;
            }

            if ($status['code'] >= 400) {
                throw new KsefAuthenticationException(
                    statusCode: $status['code'],
                    statusDescription: $status['description'],
                    details: $status['details'] ?? [],
                );
            }

            usleep($intervalMs * 1000);
        }

        throw new KsefAuthenticationException(
            statusCode: 0,
            statusDescription: "KSeF authentication timed out after {$maxAttempts} attempts.",
        );
    }
}
