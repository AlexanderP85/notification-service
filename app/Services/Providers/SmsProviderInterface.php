<?php

namespace App\Services\Providers;

interface SmsProviderInterface
{
    public function send(string $phone, string $message): string;

    public function getStatus(string $messageId): string;
}
