<?php

namespace AdamDziuk\LaravelKsef\Support;

final class SymmetricKey
{
    private function __construct(
        public readonly string $key,
        public readonly string $iv,
    ) {}

    public static function random(): self
    {
        return new self(
            key: random_bytes(32),
            iv: random_bytes(16),
        );
    }

    public function keyBase64(): string
    {
        return base64_encode($this->key);
    }

    public function ivBase64(): string
    {
        return base64_encode($this->iv);
    }
}
