<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;

class NotificationService
{
    public function createAndDispatch(
        string $channel,
        string $message,
        array $recipientIds,
        string $priority,
        ?string $idempotencyKey = null
    ): array {
        $notifications = [];

        foreach ($recipientIds as $recipientId) {
            $notification = Notification::create([
                'idempotency_key' => $idempotencyKey,
                'recipient_id' => $recipientId,
                'channel' => $channel,
                'message' => $message,
                'priority' => $priority,
                'status' => 'queued',
            ]);

            dispatch(new SendNotificationJob($notification)->onQueue("notifications.{$priority}"));

            $notifications[] = $notification;
        }

        return $notifications;
    }
}
