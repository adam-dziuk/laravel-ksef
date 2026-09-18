<?php

namespace AdamDziuk\LaravelKsef\Exceptions;

use Illuminate\Http\Client\Response;

class KsefApiException extends KsefException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?int $exceptionCode,
        public readonly string $exceptionDescription,
        public readonly array $details = [],
        public readonly ?string $referenceNumber = null,
    ) {
        parent::__construct(
            "KSeF API error (HTTP {$this->httpStatus}, code {$this->exceptionCode}): {$this->exceptionDescription}",
            $this->exceptionCode ?? $this->httpStatus,
        );
    }

    public static function fromResponse(Response $response): self
    {
        $body = $response->json();

        $detail = $body['exception']['exceptionDetailList'][0] ?? null;

        return new self(
            httpStatus: $response->status(),
            exceptionCode: $detail['exceptionCode'] ?? null,
            exceptionDescription: $detail['exceptionDescription'] ?? ($response->body() !== '' ? $response->body() : 'Unknown KSeF API error'),
            details: $detail['details'] ?? [],
            referenceNumber: $body['exception']['referenceNumber'] ?? null,
        );
    }
}
