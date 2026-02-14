<?php

use App\Http\Controllers\Api\CohereChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatController;

Route::post('/chat', [ChatController::class, 'chat']);
Route::post('/chat-v2', [CohereChatController::class, 'chat']);
