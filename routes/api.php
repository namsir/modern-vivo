<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::post('/aws/mediaconvert', [WebhookController::class, 'handleMediaConvert']);

Route::post('/webhooks/3play', [WebhookController::class, 'handle3Play'])->name('webhooks.3play');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
