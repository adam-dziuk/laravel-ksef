<?php

namespace AdamDziuk\LaravelKsef\Actions\Sessions;

use AdamDziuk\LaravelKsef\Http\KsefClient;

class GetInvoiceStatus
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * @return array{ordinalNumber: int, referenceNumber: string, status: array{code: int, description: string, details?: array<int, string>}}
     */
    public function handle(string $sessionReferenceNumber, string $invoiceReferenceNumber): array
    {
        return $this->client->get("/sessions/{$sessionReferenceNumber}/invoices/{$invoiceReferenceNumber}")->json();
    }
}
