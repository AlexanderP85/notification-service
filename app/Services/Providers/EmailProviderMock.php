<?php

namespace App\Services\Providers;

class EmailProviderMock implements EmailProviderInterface
{
    public function send(string $email, string $message): string
    {
        // Симуляция случайной ошибки (10% для тестирования retry)
        if (random_int(1, 100) <= 10) {
            throw new \Exception('Provider temporary unavailable');
        }

        return 'email_'.uniqid();
    }
}
