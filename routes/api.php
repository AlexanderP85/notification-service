<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/notifications', [NotificationController::class, 'send']);
Route::get('/notifications/recipient/{recipientId}', [NotificationController::class, 'history']);
