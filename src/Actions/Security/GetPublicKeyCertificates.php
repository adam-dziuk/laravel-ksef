<?php

namespace AdamDziuk\LaravelKsef\Actions\Security;

use AdamDziuk\LaravelKsef\Enums\PublicKeyCertificateUsage;
use AdamDziuk\LaravelKsef\Http\KsefClient;
use RuntimeException;

class GetPublicKeyCertificates
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * @return array<int, array{certificate: string, certificateId: string, publicKeyId: string, validFrom: string, validTo: string, usage: array<int, string>}>
     */
    public function handle(): array
    {
        return $this->client->get('/security/public-key-certificates')->json();
    }

    /**
     * @return array{certificate: string, certificateId: string, publicKeyId: string, validFrom: string, validTo: string, usage: array<int, string>}
     */
    public function forUsage(PublicKeyCertificateUsage $usage): array
    {
        foreach ($this->handle() as $certificate) {
            if (in_array($usage->value, $certificate['usage'], true)) {
                return $certificate;
            }
        }

        throw new RuntimeException("No KSeF public key certificate found for usage [{$usage->value}].");
    }
}
