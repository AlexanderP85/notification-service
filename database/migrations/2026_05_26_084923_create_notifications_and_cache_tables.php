<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================
        // 1. Основная таблица уведомлений
        // ============================================
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key')->nullable()->index();
            $table->string('recipient_id');
            $table->string('channel', 10); // sms, email
            $table->text('message');
            $table->string('priority', 10); // high, medium, low
            $table->string('status', 20)->default('queued'); // queued, sent, delivered, failed
            $table->text('provider_response')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Индексы для производительности
            $table->index('recipient_id');
            $table->index('status');
            $table->index('priority');
            $table->index(['recipient_id', 'created_at']);
        });

        // ============================================
        // 2. Таблица кэша (для идемпотентности)
        // ============================================
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        // ============================================
        // 3. Таблица блокировок кэша (опционально)
        // ============================================
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
