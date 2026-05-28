<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\Providers\SmsProviderInterface;
use App\Services\Providers\EmailProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 30, 120];
    public $queue = null;

    public function __construct(protected Notification $notification) {}

    public function onQueue($queue): static
    {
        $this->queue = $queue;
        return $this;
    }

    public function handle(
        SmsProviderInterface $smsProvider,
        EmailProviderInterface $emailProvider
    ): void {
        Log::info('🔵 1. НАЧАЛО handle()', ['id' => $this->notification->id, 'time' => now()->toDateTimeString()]);

        // Добавлены 10 секунд для более наглядного примера.
        // Без этой задержки статус сообщения в БД почти мгновенно меняется на delivered.
        sleep(10);

        Log::info('🟡 2. ПОСЛЕ sleep(10)', ['id' => $this->notification->id, 'time' => now()->toDateTimeString()]);

        // 1. Статус "отправляется"
        $this->notification->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Log::info('🟢 3. Статус обновлён на sent', ['id' => $this->notification->id]);

        try {
            $provider = match ($this->notification->channel) {
                'sms' => $smsProvider,
                'email' => $emailProvider,
            };

            $response = $provider->send(
                $this->notification->recipient_id,
                $this->notification->message
            );

            // 2. Успешно отправлено провайдеру
            $this->notification->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'provider_response' => $response,
                'retry_count' => $this->attempts(),
            ]);

            Log::info('✅ 4. Статус обновлён на delivered', ['id' => $this->notification->id]);

        } catch (\Exception $e) {
            Log::error('❌ Ошибка в handle()', ['id' => $this->notification->id, 'error' => $e->getMessage()]);

            $this->notification->increment('retry_count');

            // Определяем тип ошибки
            $isPermanent = $this->isPermanentError($e->getMessage());

            if ($isPermanent || $this->attempts() >= $this->tries) {
                $finalStatus = $isPermanent ? 'rejected' : 'failed';

                $this->notification->update([
                    'status' => $finalStatus,
                    'failed_at' => now(),
                    'provider_response' => $e->getMessage(),
                ]);
                return;
            }

            // Временная ошибка — будет повтор
            $this->release($this->backoff[$this->attempts() - 1]);
        }
    }

    private function isPermanentError(string $message): bool
    {
        $permanentErrors = [
            'invalid phone number',
            'invalid email',
            'does not exist',
            'not found',
            'blocked',
            'bounced',
            'invalid recipient',
        ];

        foreach ($permanentErrors as $error) {
            if (stripos($message, $error) !== false) {
                return true;
            }
        }

        return false;
    }
}
