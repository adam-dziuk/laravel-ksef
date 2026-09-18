<?php

// config for AdamDziuk/LaravelKsef
return [

    /*
     * Which KSeF environment to talk to: 'test', 'demo' or 'production'.
     */
    'environment' => env('KSEF_ENVIRONMENT', 'test'),

    'base_urls' => [
        'test' => 'https://api-test.ksef.mf.gov.pl/v2',
        'demo' => 'https://api-demo.ksef.mf.gov.pl/v2',
        'production' => 'https://api.ksef.mf.gov.pl/v2',
    ],

    'context' => [
        'nip' => env('KSEF_NIP'),
    ],

    'ksef_token' => env('KSEF_TOKEN'),

    'auth' => [
        // How long to wait between polls of GET /auth/{referenceNumber} while
        // the authentication operation is still in progress (status code 100).
        'poll_interval_ms' => (int) env('KSEF_AUTH_POLL_INTERVAL_MS', 500),
        'poll_max_attempts' => (int) env('KSEF_AUTH_POLL_MAX_ATTEMPTS', 40),
    ],

    'form_code' => [
        'system_code' => 'FA (3)',
        'schema_version' => '1-0E',
        'value' => 'FA',
    ],

    'http' => [
        'timeout' => (int) env('KSEF_HTTP_TIMEOUT', 30),
        'retry_times' => (int) env('KSEF_HTTP_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('KSEF_HTTP_RETRY_SLEEP_MS', 200),
    ],

];
