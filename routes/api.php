<?php

use App\Domains\Opportunity\Controllers\Api\OpportunityController;
use App\Infrastructure\Email\Http\MandrillWebhookController;
use Illuminate\Support\Facades\Route;

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

// Mandrill Webhook Route (No authentication - uses signature verification)
Route::post('/webhooks/mandrill', [MandrillWebhookController::class, 'handle'])
    ->name('webhooks.mandrill');

// API v1 Routes
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public endpoints
    Route::get('/opportunities', [OpportunityController::class, 'index'])
        ->name('opportunities.index');
});
