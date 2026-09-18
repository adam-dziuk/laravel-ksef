<?php

use AdamDziuk\LaravelKsef\Actions\Auth\AuthenticateAndPersistSession;
use AdamDziuk\LaravelKsef\Enums\KsefEnvironment;
use AdamDziuk\LaravelKsef\Http\KsefClient;
use AdamDziuk\LaravelKsef\Models\KsefAuthSession;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;

it('runs the full ksef-token authentication flow and persists the session', function () {
    ['certificateBase64Der' => $certificate, 'privateKeyPem' => $privateKeyPem] = fakeKsefCertificate();

    Http::fake([
        '*/auth/challenge' => Http::response([
            'challenge' => '20250514-CR-226FB7B000-3ACF9BE4C0-10',
            'timestamp' => '2025-07-11T12:23:56.0154302+00:00',
            'timestampMs' => 1752236636015,
        ]),
        '*/security/public-key-certificates' => Http::response([
            [
                'certificate' => $certificate,
                'certificateId' => 'certificate-id',
                'publicKeyId' => 'public-key-id',
                'validFrom' => '2024-07-11T12:23:56.0154302+00:00',
                'validTo' => '2028-07-11T12:23:56.0154302+00:00',
                'usage' => ['KsefTokenEncryption'],
            ],
        ]),
        '*/auth/ksef-token' => Http::response([
            'referenceNumber' => '20250514-AU-2DFC46C000-3AC6D5877F-D4',
            'authenticationToken' => [
                'token' => 'authentication-token',
                'validUntil' => now()->addMinutes(10)->toIso8601String(),
            ],
        ], 202),
        '*/auth/token/redeem' => Http::response([
            'accessToken' => [
                'token' => 'access-token',
                'validUntil' => now()->addHour()->toIso8601String(),
            ],
            'refreshToken' => [
                'token' => 'refresh-token',
                'validUntil' => now()->addDays(30)->toIso8601String(),
            ],
        ]),
        // Catch-all for GET /auth/{referenceNumber}, must be registered last.
        '*/auth/*' => Http::response([
            'authenticationMethod' => 'Token',
            'status' => ['code' => 200, 'description' => 'Uwierzytelnianie zakończone sukcesem'],
        ]),
    ]);

    $client = KsefClient::fromConfig(KsefEnvironment::Test);

    // NIP from the KSeF API's own OpenAPI documentation examples, not a real taxpayer.
    $session = (new AuthenticateAndPersistSession($client))->handle(
        '5265877635',
        'my-ksef-token',
        KsefEnvironment::Test,
    );

    expect($session)->toBeInstanceOf(KsefAuthSession::class)
        ->and($session->nip)->toBe('5265877635')
        ->and($session->environment)->toBe('test')
        ->and($session->access_token)->toBe('access-token')
        ->and($session->refresh_token)->toBe('refresh-token')
        ->and(KsefAuthSession::query()->count())->toBe(1);

    Http::assertSent(function (Request $request) use ($privateKeyPem) {
        if (! str_ends_with($request->url(), '/auth/ksef-token')) {
            return true;
        }

        $privateKey = RSA::load($privateKeyPem)
            ->withPadding(RSA::ENCRYPTION_OAEP)
            ->withHash('sha256')
            ->withMGFHash('sha256');

        $decrypted = $privateKey->decrypt(base64_decode($request['encryptedToken']));

        return $decrypted === 'my-ksef-token|1752236636015'
            && $request['contextIdentifier']['value'] === '5265877635';
    });
});

it('authenticates the status and redeem calls with the authenticationToken bearer', function () {
    ['certificateBase64Der' => $certificate] = fakeKsefCertificate();

    Http::fake([
        '*/auth/challenge' => Http::response(['challenge' => 'c', 'timestamp' => 'x', 'timestampMs' => 1]),
        '*/security/public-key-certificates' => Http::response([
            ['certificate' => $certificate, 'certificateId' => 'id', 'publicKeyId' => 'pk', 'validFrom' => 'x', 'validTo' => 'y', 'usage' => ['KsefTokenEncryption']],
        ]),
        '*/auth/ksef-token' => Http::response([
            'referenceNumber' => 'ref-1',
            'authenticationToken' => ['token' => 'the-authentication-token', 'validUntil' => now()->toIso8601String()],
        ], 202),
        '*/auth/token/redeem' => Http::response([
            'accessToken' => ['token' => 'a', 'validUntil' => now()->addHour()->toIso8601String()],
            'refreshToken' => ['token' => 'r', 'validUntil' => now()->addDay()->toIso8601String()],
        ]),
        '*/auth/*' => Http::response(['authenticationMethod' => 'Token', 'status' => ['code' => 200, 'description' => 'ok']]),
    ]);

    $client = KsefClient::fromConfig(KsefEnvironment::Test);

    (new AuthenticateAndPersistSession($client))->handle('123', 'token', KsefEnvironment::Test);

    Http::assertSent(function (Request $request) {
        if (! str_ends_with($request->url(), '/auth/ref-1') && ! str_ends_with($request->url(), '/auth/token/redeem')) {
            return true;
        }

        return $request->hasHeader('Authorization', 'Bearer the-authentication-token');
    });
});
