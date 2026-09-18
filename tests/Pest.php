<?php

use AdamDziuk\LaravelKsef\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Generates a throwaway self-signed RSA certificate for tests, returning the
 * certificate's base64-encoded DER body (exactly the shape the KSeF API
 * returns in `/security/public-key-certificates`) alongside the matching
 * PEM private key, so tests can decrypt what the package encrypts.
 *
 * @return array{certificateBase64Der: string, privateKeyPem: string}
 */
function fakeKsefCertificate(): array
{
    $config = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];

    $privateKey = openssl_pkey_new($config);
    $csr = openssl_csr_new(['commonName' => 'KSeF Test'], $privateKey, $config);
    $cert = openssl_csr_sign($csr, null, $privateKey, 365, $config);

    openssl_x509_export($cert, $certificatePem);
    openssl_pkey_export($privateKey, $privateKeyPem);

    $certificateBase64Der = str_replace(
        ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"],
        '',
        $certificatePem,
    );

    return [
        'certificateBase64Der' => $certificateBase64Der,
        'privateKeyPem' => $privateKeyPem,
    ];
}
