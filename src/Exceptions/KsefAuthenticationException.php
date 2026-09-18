<?php

namespace AdamDziuk\LaravelKsef\Exceptions;

class KsefAuthenticationException extends KsefException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $statusDescription,
        public readonly array $details = [],
    ) {
        parent::__construct(
            "KSeF authentication failed (status {$this->statusCode}): {$this->statusDescription}",
            $this->statusCode,
        );
    }
}
