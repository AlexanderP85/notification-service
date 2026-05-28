<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\IdempotencyService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private IdempotencyService $idempotencyService
    ) {}

    public function send(Request $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey && $this->idempotencyService->isProcessed($idempotencyKey)) {
            return response()->json(
                $this->idempotencyService->getResult($idempotencyKey),
                200
            );
        }

        $validated = $request->validate([
            'channel' => 'required|in:sms,email',
            'message' => 'required|string|max:5000',
            'recipient_ids' => 'required|array|min:1|max:1000',
            'recipient_ids.*' => 'required|string|max:255',
            'priority' => 'required|in:high,medium,low',
        ]);

        $notifications = $this->notificationService->createAndDispatch(
            channel: $validated['channel'],
            message: $validated['message'],
            recipientIds: $validated['recipient_ids'],
            priority: $validated['priority'],
            idempotencyKey: $idempotencyKey
        );

        $result = [
            'status' => 'accepted',
            'notifications' => collect($notifications)->map(fn($n) => [
                'id' => $n->id,
                'recipient_id' => $n->recipient_id,
                'status' => $n->status,
            ]),
        ];

        if ($idempotencyKey) {
            $this->idempotencyService->store($idempotencyKey, $result);
        }

        return response()->json($result, 202);
    }

    /**
     * Получить историю уведомлений для конкретного подписчика
     */
    public function history(string $recipientId): JsonResponse
    {
        $notifications = Notification::where('recipient_id', $recipientId)
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'channel',
                'message',
                'status',
                'retry_count',
                'created_at',
                'sent_at',
                'delivered_at',
                'failed_at',
                'provider_response',
            ]);

        if ($notifications->isEmpty()) {
            return response()->json([
                'message' => 'No notifications found for this recipient',
                'recipient_id' => $recipientId,
                'notifications' => []
            ], 200);
        }

        return response()->json([
            'recipient_id' => $recipientId,
            'total' => $notifications->count(),
            'notifications' => $notifications
        ]);
    }
}
