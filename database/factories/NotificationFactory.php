<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'recipient_id' => $this->faker->phoneNumber(),
            'channel' => $this->faker->randomElement(['sms', 'email']),
            'message' => $this->faker->sentence(),
            'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
            'status' => 'queued',
            'retry_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
