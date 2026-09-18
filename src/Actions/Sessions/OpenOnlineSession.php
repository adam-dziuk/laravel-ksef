<?php

namespace AdamDziuk\LaravelKsef\Actions\Sessions;

use AdamDziuk\LaravelKsef\Actions\Security\GetPublicKeyCertificates;
use AdamDziuk\LaravelKsef\Enums\PublicKeyCertificateUsage;
use AdamDziuk\LaravelKsef\Http\KsefClient;
use AdamDziuk\LaravelKsef\Support\Crypto;
use AdamDziuk\LaravelKsef\Support\SymmetricKey;

class OpenOnlineSession
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * Opens an interactive (online) session for sending single invoices.
     * The returned symmetricKey must be reused for every SendInvoice call
     * within this session — the API never exposes it again.
     *
     * @return array{referenceNumber: string, validUntil: string, symmetricKey: SymmetricKey}
     */
    public function handle(): array
    {
        $certificate = (new GetPublicKeyCertificates($this->client))
            ->forUsage(PublicKeyCertificateUsage::SymmetricKeyEncryption);

        $symmetricKey = SymmetricKey::random();

        $encryptedSymmetricKey = base64_encode(Crypto::encryptRsaOaepSha256(
            $symmetricKey->key,
            $certificate['certificate'],
        ));

        $formCode = config('ksef.form_code');

        $response = $this->client->post('/sessions/online', [
            'formCode' => [
                'systemCode' => $formCode['system_code'],
                'schemaVersion' => $formCode['schema_version'],
                'value' => $formCode['value'],
            ],
            'encryption' => [
                'encryptedSymmetricKey' => $encryptedSymmetricKey,
                'initializationVector' => $symmetricKey->ivBase64(),
                'publicKeyId' => $certificate['publicKeyId'],
            ],
        ])->json();

        return [...$response, 'symmetricKey' => $symmetricKey];
    }
}
