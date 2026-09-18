# Laravel KSeF

[![Tests](https://github.com/adam-dziuk/laravel-ksef/actions/workflows/run-tests.yml/badge.svg)](https://github.com/adam-dziuk/laravel-ksef/actions/workflows/run-tests.yml)
[![Code Style](https://github.com/adam-dziuk/laravel-ksef/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/adam-dziuk/laravel-ksef/actions/workflows/fix-php-code-style-issues.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)

A Laravel package for integrating with **KSeF 2.0** (Krajowy System e-Faktur) — Poland's national e-invoicing system. It wraps the official KSeF REST API with typed, single-purpose Actions (authentication, sending invoices, querying/downloading invoices) plus a convenience facade, so you don't have to hand-roll the HTTP calls, encryption, or token lifecycle yourself.

> **Status:** early-stage MVP. It covers KSeF-token authentication and the interactive (single-invoice) sending session. Certificate/XAdES authentication, batch sessions, and invoice XML generation are not implemented yet — see [Roadmap](#roadmap).

## Requirements

- PHP 8.3+
- Laravel 12.x or 13.x
- `ext-openssl`
- A KSeF token, generated from the [KSeF test/demo/production portal](https://ksef.mf.gov.pl) for the NIP you want to integrate

## Installation (not available for now)

Install the package via composer:

```bash
composer require adam-dziuk/laravel-ksef
```

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-ksef-config"
```

Publish and run the migration (creates the `ksef_auth_sessions` table, used to persist access/refresh tokens per NIP and environment):

```bash
php artisan vendor:publish --tag="laravel-ksef-migrations"
php artisan migrate
```

## Configuration

Set these in your `.env`:

```
KSEF_ENVIRONMENT=test   # test | demo | production
KSEF_NIP=5265877635
KSEF_TOKEN=your-ksef-token
```

`KSEF_ENVIRONMENT` picks which KSeF API to talk to — always use `test` while developing. See `config/ksef.php` for HTTP timeouts, retry behaviour, and the invoice form code (defaults to FA(3)).

## Usage

### Quick start: the `Ksef` facade

The facade handles authentication for you — it reuses a persisted session, transparently refreshes an expired access token, and only logs in from scratch when it has to.

```php
use AdamDziuk\LaravelKsef\Facades\Ksef;

// Sends a single invoice: opens an online session, sends the invoice, closes the session.
$result = Ksef::sendInvoice($invoiceXml);
// ['referenceNumber' => '...', 'sessionReferenceNumber' => '...']

Ksef::invoiceStatus($result['sessionReferenceNumber'], $result['referenceNumber']);
Ksef::sessionStatus($result['sessionReferenceNumber']);

Ksef::downloadInvoice($ksefNumber);

Ksef::queryInvoices([
    'subjectType' => 'Subject1',
    'dateRange' => [
        'dateType' => 'PermanentStorage',
        'from' => now()->subMonth()->toIso8601String(),
        'to' => now()->toIso8601String(),
    ],
]);
```

By default the facade uses `KSEF_NIP` from config; pass a NIP explicitly as the last argument (e.g. `Ksef::sendInvoice($xml, $nip)`) if your app handles multiple contexts.

### Advanced: composing Actions directly

`Ksef::sendInvoice()` opens and closes a session around a single invoice. To send several invoices within the same session, compose the underlying Actions yourself:

```php
use AdamDziuk\LaravelKsef\Actions\Sessions\{OpenOnlineSession, SendInvoice, CloseOnlineSession};
use AdamDziuk\LaravelKsef\Facades\Ksef;

$client = Ksef::authenticate();

$session = (new OpenOnlineSession($client))->handle();

foreach ($invoices as $invoiceXml) {
    (new SendInvoice($client))->handle($session['referenceNumber'], $invoiceXml, $session['symmetricKey']);
}

(new CloseOnlineSession($client))->handle($session['referenceNumber']);
```

Every KSeF operation is available as its own Action under `AdamDziuk\LaravelKsef\Actions\{Auth,Sessions,Invoices,Security}`, each with a single `handle()` method — see the source for the full list.

## How authentication works

`Ksef::authenticate()` (used internally by every other facade method):

1. Looks for a valid, persisted `KsefAuthSession` for the given NIP + environment.
2. If the access token is still valid, reuses it as-is.
3. If it expired but the refresh token hasn't, silently refreshes it.
4. Otherwise, runs the full KSeF-token login flow (challenge → encrypt token with the Ministry's public key → poll authentication status → redeem access/refresh tokens) and persists the result.

## Testing

```bash
composer test
composer analyse
composer format
```

## Roadmap

- [x] KSeF-token authentication (challenge, encrypted token, status polling, access/refresh tokens)
- [x] Persisted auth sessions with automatic access token refresh
- [x] Interactive (online) sending session — open, send invoice, close
- [x] Invoice status & session status lookups
- [x] Invoice metadata query & download
- [x] Convenience `Ksef` facade
- [ ] Batch sending sessions (ZIP-packaged, multi-part upload)
- [ ] Certificate / XAdES authentication
- [ ] FA(3) invoice XML generation (this package expects a ready-made XML string)
- [ ] QR code generation
- [ ] Permissions management

Contributions towards the unchecked items are welcome.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Adam Dziuk](https://github.com/adam-dziuk)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
