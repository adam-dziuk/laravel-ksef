<?php

namespace AdamDziuk\LaravelKsef\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AdamDziuk\LaravelKsef\Enums\KsefEnvironment environment()
 * @method static \AdamDziuk\LaravelKsef\Http\KsefClient authenticate(?string $nip = null)
 * @method static array sendInvoice(string $invoiceXml, ?string $nip = null)
 * @method static array invoiceStatus(string $sessionReferenceNumber, string $invoiceReferenceNumber, ?string $nip = null)
 * @method static array sessionStatus(string $sessionReferenceNumber, ?string $nip = null)
 * @method static string downloadInvoice(string $ksefNumber, ?string $nip = null)
 * @method static array queryInvoices(array $filters, int $pageOffset = 0, int $pageSize = 10, ?string $nip = null)
 *
 * @see \AdamDziuk\LaravelKsef\Ksef
 */
class Ksef extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AdamDziuk\LaravelKsef\Ksef::class;
    }
}
