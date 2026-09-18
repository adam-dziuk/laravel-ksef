<?php

namespace AdamDziuk\LaravelKsef\Actions\Sessions;

use AdamDziuk\LaravelKsef\Http\KsefClient;
use AdamDziuk\LaravelKsef\Support\Crypto;
use AdamDziuk\LaravelKsef\Support\SymmetricKey;

class SendInvoice
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * Encrypts and sends a single invoice XML document within an already
     * open online session, using the session's symmetric key.
     *
     * @return array{referenceNumber: string}
     */
    public function handle(string $sessionReferenceNumber, string $invoiceXml, SymmetricKey $symmetricKey): array
    {
        $encryptedInvoiceContent = Crypto::encryptAes256Cbc($invoiceXml, $symmetricKey);

        return $this->client->post("/sessions/online/{$sessionReferenceNumber}/invoices", [
            'invoiceHash' => Crypto::sha256Base64($invoiceXml),
            'invoiceSize' => strlen($invoiceXml),
            'encryptedInvoiceHash' => Crypto::sha256Base64($encryptedInvoiceContent),
            'encryptedInvoiceSize' => strlen($encryptedInvoiceContent),
            'encryptedInvoiceContent' => base64_encode($encryptedInvoiceContent),
        ])->json();
    }
}
