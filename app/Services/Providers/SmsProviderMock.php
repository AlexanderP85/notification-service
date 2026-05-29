<?php

namespace App\Services\Providers;

class SmsProviderMock implements SmsProviderInterface
{
    private array $statuses = [];

    public function send(string $phone, string $message): string
    {
        $messageId = 'sms_'.uniqid();

        // Симулируем задержку доставки
        $this->statuses[$messageId] = [
            'status' => 'pending',
            'created_at' => time(),
        ];

        return $messageId;
    }

    public function getStatus(string $messageId): string
    {
        if (! isset($this->statuses[$messageId])) {
            return 'failed';
        }

        $data = $this->statuses[$messageId];

        // Через 10 секунд считаем доставленным
        if (time() - $data['created_at'] > 10) {
            return 'delivered';
        }

        return 'pending';
    }
}
