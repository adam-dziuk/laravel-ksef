<?php

namespace AdamDziuk\LaravelKsef\Actions\Sessions;

use AdamDziuk\LaravelKsef\Http\KsefClient;

class GetSessionStatus
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * @return array{
     *     status: array{code: int, description: string},
     *     dateCreated: string,
     *     dateUpdated: string,
     *     invoiceCount?: int,
     *     successfulInvoiceCount?: int,
     *     failedInvoiceCount?: int,
     *     upo?: array{pages: array<int, array{referenceNumber: string, downloadUrl: string, downloadUrlExpirationDate: string}>}
     * }
     */
    public function handle(string $sessionReferenceNumber): array
    {
        return $this->client->get("/sessions/{$sessionReferenceNumber}")->json();
    }
}
