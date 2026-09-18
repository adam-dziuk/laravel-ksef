<?php

namespace AdamDziuk\LaravelKsef\Actions\Sessions;

use AdamDziuk\LaravelKsef\Http\KsefClient;

class CloseOnlineSession
{
    public function __construct(private readonly KsefClient $client) {}

    /**
     * Closes the session and triggers asynchronous generation of the
     * collective UPO, available afterwards via GetSessionStatus.
     */
    public function handle(string $sessionReferenceNumber): void
    {
        $this->client->post("/sessions/online/{$sessionReferenceNumber}/close");
    }
}
