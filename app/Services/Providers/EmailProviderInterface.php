<?php

namespace App\Services\Providers;

interface EmailProviderInterface
{
    public function send(string $email, string $message): string;
}
