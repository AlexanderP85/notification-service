<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class IdempotencyService
{
    private const TTL = 86400; // 24 часа

    public function isProcessed(string $key): bool
    {
        return Cache::has("idempotency:{$key}");
    }

    public function store(string $key, array $data): void
    {
        Cache::put("idempotency:{$key}", $data, self::TTL);
    }

    public function getResult(string $key): array
    {
        return Cache::get("idempotency:{$key}", []);
    }
}
