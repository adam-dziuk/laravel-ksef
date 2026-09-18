<?php

namespace AdamDziuk\LaravelKsef\Support;

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PublicKey;
use phpseclib3\File\X509;
use RuntimeException;

final class Crypto
{
    public static function sha256Base64(string $data): string
    {
        return base64_encode(hash('sha256', $data, true));
    }

    public static function encryptAes256Cbc(string $plaintext, SymmetricKey $key): string
    {
        $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key->key, OPENSSL_RAW_DATA, $key->iv);

        if ($encrypted === false) {
            throw new RuntimeException('Unable to encrypt data with AES-256-CBC.');
        }

        return $encrypted;
    }

    /**
     * Encrypts data with RSA-OAEP (SHA-256) using the Ministry of Finance's
     * public key certificate, as required by the KSeF API.
     *
     * @param  string  $certificateBase64Der  Base64-encoded DER certificate, as returned by
     *                                        GET /security/public-key-certificates.
     */
    public static function encryptRsaOaepSha256(string $plaintext, string $certificateBase64Der): string
    {
        $pem = "-----BEGIN CERTIFICATE-----\n".chunk_split($certificateBase64Der, 64, "\n")."-----END CERTIFICATE-----\n";

        $x509 = new X509;
        $x509->loadX509($pem);

        /** @var PublicKey $publicKey */
        $publicKey = $x509->getPublicKey();

        $publicKey = $publicKey
            ->withPadding(RSA::ENCRYPTION_OAEP)
            ->withHash('sha256')
            ->withMGFHash('sha256');

        return $publicKey->encrypt($plaintext);
    }
}
