<?php

use AdamDziuk\LaravelKsef\Actions\Sessions\OpenOnlineSession;
use AdamDziuk\LaravelKsef\Actions\Sessions\SendInvoice;
use AdamDziuk\LaravelKsef\Enums\KsefEnvironment;
use AdamDziuk\LaravelKsef\Http\KsefClient;
use AdamDziuk\LaravelKsef\Support\SymmetricKey;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;

it('opens an online session and sends an invoice that decrypts back to the original XML', function () {
    ['certificateBase64Der' => $certificate, 'privateKeyPem' => $privateKeyPem] = fakeKsefCertificate();

    Http::fake([
        '*/security/public-key-certificates' => Http::response([
            [
                'certificate' => $certificate,
                'certificateId' => 'certificate-id',
                'publicKeyId' => 'public-key-id',
                'validFrom' => 'x',
                'validTo' => 'y',
                'usage' => ['SymmetricKeyEncryption'],
            ],
        ]),
        '*/sessions/online' => Http::response([
            'referenceNumber' => 'session-ref-1',
            'validUntil' => now()->addHours(12)->toIso8601String(),
        ], 201),
        '*/sessions/online/session-ref-1/invoices' => Http::response([
            'referenceNumber' => 'invoice-ref-1',
        ], 202),
    ]);

    $client = KsefClient::fromConfig(KsefEnvironment::Test)->withBearerToken('access-token');

    $session = (new OpenOnlineSession($client))->handle();

    expect($session['referenceNumber'])->toBe('session-ref-1');

    // NIP from the KSeF API's own OpenAPI documentation examples, not a real taxpayer.
    $invoiceXml = '<Faktura><Podmiot1><NIP>5265877635</NIP></Podmiot1></Faktura>';

    $result = (new SendInvoice($client))->handle($session['referenceNumber'], $invoiceXml, $session['symmetricKey']);

    expect($result['referenceNumber'])->toBe('invoice-ref-1');

    $privateKey = RSA::load($privateKeyPem)
        ->withPadding(RSA::ENCRYPTION_OAEP)
        ->withHash('sha256')
        ->withMGFHash('sha256');

    Http::assertSent(function (Request $request) use ($privateKey) {
        if (! str_ends_with($request->url(), '/sessions/online')) {
            return true;
        }

        $symmetricKey = $privateKey->decrypt(base64_decode($request['encryption']['encryptedSymmetricKey']));
        $iv = base64_decode($request['encryption']['initializationVector']);

        expect(strlen($symmetricKey))->toBe(32)
            ->and(strlen($iv))->toBe(16);

        return true;
    });

    Http::assertSent(function (Request $request) use ($session, $invoiceXml) {
        if (! str_ends_with($request->url(), '/invoices')) {
            return true;
        }

        /** @var SymmetricKey $symmetricKey */
        $symmetricKey = $session['symmetricKey'];

        $decrypted = openssl_decrypt(
            base64_decode($request['encryptedInvoiceContent']),
            'aes-256-cbc',
            $symmetricKey->key,
            OPENSSL_RAW_DATA,
            $symmetricKey->iv,
        );

        return $decrypted === $invoiceXml
            && $request['invoiceHash'] === base64_encode(hash('sha256', $invoiceXml, true))
            && $request['invoiceSize'] === strlen($invoiceXml);
    });
});
