<?php

declare(strict_types=1);

use App\Http\Controllers\Api;
use App\Http\Controllers\Api\MollieController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Plazacam submission
Route::put('plazacam/{user}/{webcam}', [Api\WebcamController::class, 'store'])
    ->middleware('signed')
    ->name('webcam.store');

// Plazacam viewing via API
Route::get('plazacam/{user}/{webcam}', [Api\WebcamController::class, 'show'])
    ->middleware('signed')
    ->name('webcam.view');

// Register API for Stripe endpoints
Route::stripeWebhooks('payments/stripe/handle');

// Register Telegram webhooks
Route::post('/bots/telegram', [TelegramBotController::class, 'handle'])->name('bots.telegram');

// Register Mollie webhook URL
Route::post('/mollie/hooks/activities', [MollieController::class, 'activity'])->name('webhooks.shop');
Route::post('/mollie/hooks/shop', [MollieController::class, 'shop'])->name('webhooks.shop');
