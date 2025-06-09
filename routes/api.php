<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VideoDetailsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/payments-providers/stripe/webhook', [
    App\Http\Controllers\PaymentProviders\StripeController::class,
    'handleWebhook',
])->name('payments-providers.stripe.webhook');

Route::post('/payments-providers/paddle/webhook', [
    App\Http\Controllers\PaymentProviders\PaddleController::class,
    'handleWebhook',
])->name('payments-providers.paddle.webhook');

Route::post('/payments-providers/lemon-squeezy/webhook', [
    App\Http\Controllers\PaymentProviders\LemonSqueezyController::class,
    'handleWebhook',
])->name('payments-providers.lemon-squeezy.webhook');


// Dub.co conversion tracking - accessible from external client websites
Route::options('/dub/track-conversion', [
    App\Http\Controllers\DubController::class,
    'trackConversion',
])->name('dub.track-conversion.options');

Route::post('/dub/track-conversion', [
    App\Http\Controllers\DubController::class,
    'trackConversion',
])->name('dub.track-conversion');

// Lead tracking endpoint - accessible from external client websites
Route::options('/track-lead', function () {
    return response('')
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
});

Route::post('/track-lead', function (\Illuminate\Http\Request $request) {
    \Log::info('Lead tracked:', $request->all());
    
    return response()->json(['status' => 'success'])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
})->name('track.lead');

// Video details endpoint
Route::post('/video-details', [VideoDetailsController::class, 'handleDetails']);

