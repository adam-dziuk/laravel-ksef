<?php

namespace AdamDziuk\LaravelKsef;

use AdamDziuk\LaravelKsef\Actions\Auth\AuthenticateAndPersistSession;
use AdamDziuk\LaravelKsef\Actions\Auth\GetActiveAuthSession;
use AdamDziuk\LaravelKsef\Actions\Invoices\DownloadInvoice;
use AdamDziuk\LaravelKsef\Actions\Invoices\QueryInvoiceMetadata;
use AdamDziuk\LaravelKsef\Actions\Sessions\CloseOnlineSession;
use AdamDziuk\LaravelKsef\Actions\Sessions\GetInvoiceStatus;
use AdamDziuk\LaravelKsef\Actions\Sessions\GetSessionStatus;
use AdamDziuk\LaravelKsef\Actions\Sessions\OpenOnlineSession;
use AdamDziuk\LaravelKsef\Actions\Sessions\SendInvoice;
use AdamDziuk\LaravelKsef\Enums\KsefEnvironment;
use AdamDziuk\LaravelKsef\Http\KsefClient;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Ergonomic entry point over the package's Actions, bound as a singleton and
 * exposed through the Ksef facade. Handles wiring the HTTP client and
 * authentication so callers don't have to; the underlying Actions remain
 * available directly for flows this class doesn't cover (e.g. sending
 * several invoices within a single online session).
 */
class Ksef
{
    public function __construct(private readonly ConfigRepository $config) {}

    public function environment(): KsefEnvironment
    {
        return KsefEnvironment::from($this->config->get('ksef.environment'));
    }

    /**
     * Returns a client authenticated for the given (or default, config-provided)
     * NIP, transparently logging in and persisting the session if there isn't
     * already a valid one.
     */
    public function authenticate(?string $nip = null): KsefClient
    {
        $nip ??= $this->config->get('ksef.context.nip');
        $environment = $this->environment();
        $client = KsefClient::fromConfig($environment);

        $session = (new GetActiveAuthSession($client))->handle($nip, $environment)
            ?? (new AuthenticateAndPersistSession($client))->handle($nip, $this->config->get('ksef.ksef_token'), $environment);

        return $client->withBearerToken($session->access_token);
    }

    /**
     * Sends a single invoice XML document: opens an online session, sends the
     * invoice and closes the session. To send several invoices within the
     * same session, compose OpenOnlineSession/SendInvoice/CloseOnlineSession
     * directly instead.
     *
     * @return array{referenceNumber: string, sessionReferenceNumber: string}
     */
    public function sendInvoice(string $invoiceXml, ?string $nip = null): array
    {
        $client = $this->authenticate($nip);

        $session = (new OpenOnlineSession($client))->handle();

        $result = (new SendInvoice($client))->handle(
            $session['referenceNumber'],
            $invoiceXml,
            $session['symmetricKey'],
        );

        (new CloseOnlineSession($client))->handle($session['referenceNumber']);

        return [
            'referenceNumber' => $result['referenceNumber'],
            'sessionReferenceNumber' => $session['referenceNumber'],
        ];
    }

    /**
     * @return array{ordinalNumber: int, referenceNumber: string, status: array{code: int, description: string}}
     */
    public function invoiceStatus(string $sessionReferenceNumber, string $invoiceReferenceNumber, ?string $nip = null): array
    {
        return (new GetInvoiceStatus($this->authenticate($nip)))->handle($sessionReferenceNumber, $invoiceReferenceNumber);
    }

    /**
     * @return array{status: array{code: int, description: string}, dateCreated: string, dateUpdated: string}
     */
    public function sessionStatus(string $sessionReferenceNumber, ?string $nip = null): array
    {
        return (new GetSessionStatus($this->authenticate($nip)))->handle($sessionReferenceNumber);
    }

    public function downloadInvoice(string $ksefNumber, ?string $nip = null): string
    {
        return (new DownloadInvoice($this->authenticate($nip)))->handle($ksefNumber);
    }

    /**
     * @param  array  $filters  InvoiceQueryFilters, e.g. ['subjectType' => 'Subject1', 'dateRange' => [...]]
     * @return array{invoices: array<int, array<string, mixed>>}
     */
    public function queryInvoices(array $filters, int $pageOffset = 0, int $pageSize = 10, ?string $nip = null): array
    {
        return (new QueryInvoiceMetadata($this->authenticate($nip)))->handle($filters, $pageOffset, $pageSize);
    }
}
