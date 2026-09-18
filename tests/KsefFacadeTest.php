<?php

use AdamDziuk\LaravelKsef\Facades\Ksef;
use Illuminate\Support\Facades\Http;

it('sends an invoice end-to-end through the Ksef facade', function () {
    ['certificateBase64Der' => $certificate] = fakeKsefCertificate();

    // NIP from the KSeF API's own OpenAPI documentation examples, not a real taxpayer.
    config()->set('ksef.context.nip', '5265877635');
    config()->set('ksef.ksef_token', 'my-ksef-token');

    Http::fake([
        '*/auth/challenge' => Http::response(['challenge' => 'c', 'timestamp' => 'x', 'timestampMs' => 1]),
        '*/security/public-key-certificates' => Http::response([
            [
                'certificate' => $certificate,
                'certificateId' => 'id',
                'publicKeyId' => 'pk',
                'validFrom' => 'x',
                'validTo' => 'y',
                'usage' => ['KsefTokenEncryption', 'SymmetricKeyEncryption'],
            ],
        ]),
        '*/auth/ksef-token' => Http::response([
            'referenceNumber' => 'ref-1',
            'authenticationToken' => ['token' => 'auth-token', 'validUntil' => now()->toIso8601String()],
        ], 202),
        '*/auth/token/redeem' => Http::response([
            'accessToken' => ['token' => 'access-token', 'validUntil' => now()->addHour()->toIso8601String()],
            'refreshToken' => ['token' => 'refresh-token', 'validUntil' => now()->addDay()->toIso8601String()],
        ]),
        '*/sessions/online/session-1/invoices' => Http::response(['referenceNumber' => 'invoice-1'], 202),
        '*/sessions/online/session-1/close' => Http::response(null, 204),
        '*/sessions/online' => Http::response([
            'referenceNumber' => 'session-1',
            'validUntil' => now()->addHours(12)->toIso8601String(),
        ], 201),
        '*/auth/*' => Http::response([
            'authenticationMethod' => 'Token',
            'status' => ['code' => 200, 'description' => 'ok'],
        ]),
    ]);

    $result = Ksef::sendInvoice('<Faktura></Faktura>');

    expect($result)->toBe([
        'referenceNumber' => 'invoice-1',
        'sessionReferenceNumber' => 'session-1',
    ]);

    // A second call must reuse the persisted session instead of logging in again.
    Ksef::sendInvoice('<Faktura>2</Faktura>');

    expect(Http::recorded(fn ($request) => str_ends_with($request->url(), '/auth/ksef-token')))->toHaveCount(1)
        ->and(Http::recorded(fn ($request) => str_ends_with($request->url(), '/sessions/online/session-1/invoices')))->toHaveCount(2);
});
