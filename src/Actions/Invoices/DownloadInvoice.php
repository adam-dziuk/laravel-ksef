<?php

namespace AdamDziuk\LaravelKsef\Actions\Invoices;

use AdamDziuk\LaravelKsef\Http\KsefClient;

class DownloadInvoice
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * Returns the raw invoice XML for the given KSeF number.
     */
    public function handle(string $ksefNumber): string
    {
        return $this->client->get("/invoices/ksef/{$ksefNumber}")->body();
    }
}
