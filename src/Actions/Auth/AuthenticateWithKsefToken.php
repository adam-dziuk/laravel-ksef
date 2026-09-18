<?php

namespace AdamDziuk\LaravelKsef\Actions\Auth;

use AdamDziuk\LaravelKsef\Actions\Security\GetPublicKeyCertificates;
use AdamDziuk\LaravelKsef\Enums\PublicKeyCertificateUsage;
use AdamDziuk\LaravelKsef\Http\KsefClient;
use AdamDziuk\LaravelKsef\Support\Crypto;

class AuthenticateWithKsefToken
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * Starts a KSeF authentication operation using a previously generated KSeF token.
     * The returned authenticationToken must be used as the Bearer token for
     * GetAuthenticationStatus and, once successful, RedeemAccessToken.
     *
     * @return array{referenceNumber: string, authenticationToken: array{token: string, validUntil: string}}
     */
    public function handle(string $nip, string $ksefToken): array
    {
        $challenge = (new RequestAuthChallenge($this->client))->handle();

        $certificate = (new GetPublicKeyCertificates($this->client))
            ->forUsage(PublicKeyCertificateUsage::KsefTokenEncryption);

        // Per the KSeF API contract, the plaintext is "{ksefToken}|{timestampMs}",
        // encrypted with RSA-OAEP (SHA-256) using the Ministry's public key.
        $encryptedToken = base64_encode(Crypto::encryptRsaOaepSha256(
            "{$ksefToken}|{$challenge['timestampMs']}",
            $certificate['certificate'],
        ));

        return $this->client->post('/auth/ksef-token', [
            'challenge' => $challenge['challenge'],
            'contextIdentifier' => [
                'type' => 'Nip',
                'value' => $nip,
            ],
            'encryptedToken' => $encryptedToken,
            'publicKeyId' => $certificate['publicKeyId'],
        ])->json();
    }
}
