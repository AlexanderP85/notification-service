<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Services\Providers\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // Мокаем провайдера для успешной отправки
        $this->mock(SmsProviderInterface::class, function ($mock) {
            $mock->shouldReceive('send')
                ->andReturn('sms_test_' . uniqid());
        });
    }

    public function test_sms_notification_is_created(): void
    {
        $response = $this->postJson('/api/notifications', [
            'channel' => 'sms',
            'message' => 'Test SMS',
            'recipient_ids' => ['+79991234567'],
            'priority' => 'high',
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => '+79991234567',
            'message' => 'Test SMS',
            'channel' => 'sms',
            'priority' => 'high',
        ]);
    }

    public function test_email_notification_is_created(): void
    {
        $response = $this->postJson('/api/notifications', [
            'channel' => 'email',
            'message' => 'Test Email',
            'recipient_ids' => ['user@example.com'],
            'priority' => 'medium',
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => 'user@example.com',
            'message' => 'Test Email',
            'channel' => 'email',
            'priority' => 'medium',
        ]);
    }

    public function test_messages_with_different_priorities(): void
    {
        $this->postJson('/api/notifications', [
            'channel' => 'sms',
            'message' => 'High priority',
            'recipient_ids' => ['+79990000001'],
            'priority' => 'high',
        ]);

        $this->postJson('/api/notifications', [
            'channel' => 'sms',
            'message' => 'Medium priority',
            'recipient_ids' => ['+79990000002'],
            'priority' => 'medium',
        ]);

        $this->postJson('/api/notifications', [
            'channel' => 'sms',
            'message' => 'Low priority',
            'recipient_ids' => ['+79990000003'],
            'priority' => 'low',
        ]);

        $this->assertDatabaseCount('notifications', 3);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => '+79990000001',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => '+79990000002',
            'priority' => 'medium',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => '+79990000003',
            'priority' => 'low',
        ]);
    }

    public function test_idempotency_with_same_key_returns_cached_response(): void
    {
        $idempotencyKey = 'test-idempotency-' . uniqid();

        $firstResponse = $this->postJson('/api/notifications', [
            'channel' => 'sms',
            'message' => 'Idempotency test',
            'recipient_ids' => ['+79991234567'],
            'priority' => 'high',
        ], ['Idempotency-Key' => $idempotencyKey]);

        $firstResponse->assertStatus(202);

        $secondResponse = $this->postJson('/api/notifications', [
            'channel' => 'sms',
            'message' => 'Different message',
            'recipient_ids' => ['+79991234567'],
            'priority' => 'high',
        ], ['Idempotency-Key' => $idempotencyKey]);

        $secondResponse->assertStatus(200);
        $this->assertEquals($firstResponse->json(), $secondResponse->json());

        $count = Notification::where('idempotency_key', $idempotencyKey)->count();
        $this->assertEquals(1, $count);
    }

    public function test_bulk_notification_to_multiple_recipients(): void
    {
        $recipients = ['+79991111111', '+79992222222', '+79993333333'];

        $response = $this->postJson('/api/notifications', [
            'channel' => 'sms',
            'message' => 'Bulk test message',
            'recipient_ids' => $recipients,
            'priority' => 'low',
        ]);

        $response->assertStatus(202);
        $response->assertJsonCount(3, 'notifications');

        foreach ($recipients as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'recipient_id' => $recipient,
                'message' => 'Bulk test message',
            ]);
        }
    }

    public function test_notification_history_for_recipient(): void
    {
        // Создаём тестовые данные
        for ($i = 0; $i < 3; $i++) {
            Notification::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'recipient_id' => '+79991234567',
                'channel' => 'sms',
                'message' => "History test message {$i}",
                'priority' => 'high',
                'status' => 'delivered',
                'retry_count' => 0,
                'created_at' => now()->subMinutes($i),
                'updated_at' => now(),
            ]);
        }

        $response = $this->getJson('/api/notifications/recipient/+79991234567');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'recipient_id',
            'total',
            'notifications' => [
                ['id', 'channel', 'message', 'status', 'created_at']
            ]
        ]);

        $this->assertEquals(3, $response->json('total'));
    }

    public function test_request_validation(): void
    {
        $response = $this->postJson('/api/notifications', [
            'channel' => 'invalid',
            'message' => 'Test',
            'recipient_ids' => ['+79991234567'],
            'priority' => 'high',
        ]);
        $response->assertStatus(422);

        $response = $this->postJson('/api/notifications', [
            'channel' => 'sms',
            'message' => 'Test',
            'recipient_ids' => [],
            'priority' => 'high',
        ]);
        $response->assertStatus(422);

        $response = $this->postJson('/api/notifications', [
            'channel' => 'sms',
            'message' => 'Test',
            'recipient_ids' => ['+79991234567'],
            'priority' => 'invalid',
        ]);
        $response->assertStatus(422);
    }
}
