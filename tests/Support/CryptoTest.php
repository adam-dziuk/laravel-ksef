<?php

use AdamDziuk\LaravelKsef\Support\Crypto;
use AdamDziuk\LaravelKsef\Support\SymmetricKey;
use phpseclib3\Crypt\RSA;

it('encrypts and decrypts a payload with AES-256-CBC', function () {
    $key = SymmetricKey::random();
    $plaintext = '<Faktura>test content</Faktura>';

    $encrypted = Crypto::encryptAes256Cbc($plaintext, $key);

    expect($encrypted)->not->toBe($plaintext);

    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key->key, OPENSSL_RAW_DATA, $key->iv);

    expect($decrypted)->toBe($plaintext);
});

it('computes a base64-encoded SHA-256 hash', function () {
    $hash = Crypto::sha256Base64('hello');

    expect($hash)->toBe(base64_encode(hash('sha256', 'hello', true)));
});

it('encrypts a payload with RSA-OAEP-SHA256 that the certificate owner can decrypt', function () {
    ['certificateBase64Der' => $certificate, 'privateKeyPem' => $privateKeyPem] = fakeKsefCertificate();

    $plaintext = 'some-ksef-token|1752236636015';

    $encrypted = Crypto::encryptRsaOaepSha256($plaintext, $certificate);

    $privateKey = RSA::load($privateKeyPem)
        ->withPadding(RSA::ENCRYPTION_OAEP)
        ->withHash('sha256')
        ->withMGFHash('sha256');

    expect($privateKey->decrypt($encrypted))->toBe($plaintext);
});
