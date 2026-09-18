<?php

namespace AdamDziuk\LaravelKsef\Actions\Invoices;

use AdamDziuk\LaravelKsef\Http\KsefClient;

class QueryInvoiceMetadata
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * @param  array  $filters  InvoiceQueryFilters as defined by the KSeF OpenAPI spec,
     *                          e.g. ['subjectType' => 'Subject1', 'dateRange' => ['dateType' => 'PermanentStorage', 'from' => ..., 'to' => ...]]
     * @return array{invoices: array<int, array<string, mixed>>, hasMore?: bool}
     */
    public function handle(array $filters, int $pageOffset = 0, int $pageSize = 10, string $sortOrder = 'Asc'): array
    {
        $query = http_build_query([
            'pageOffset' => $pageOffset,
            'pageSize' => $pageSize,
            'sortOrder' => $sortOrder,
        ]);

        return $this->client->post("/invoices/query/metadata?{$query}", $filters)->json();
    }
}
