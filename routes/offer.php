<?php

use App\Domains\Offer\Controllers\AcceptOfferController;
use App\Domains\Offer\Controllers\ClarificationRequestController;
use App\Domains\Offer\Controllers\DeleteDocumentController;
use App\Domains\Offer\Controllers\DestroyController;
use App\Domains\Offer\Controllers\DownloadDocumentController;
use App\Domains\Offer\Controllers\FormController;
use App\Domains\Offer\Controllers\ListController;
use App\Domains\Offer\Controllers\ShowController;
use App\Domains\Offer\Controllers\StoreController;
use App\Domains\Offer\Controllers\UpdateController;
use App\Domains\Offer\Controllers\UpdateStatusController;
use App\Domains\Offer\Controllers\UploadDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:administrator'])->prefix('admin')->group(function () {
    Route::get('offer', ListController::class)->name('admin.offer.list');
    Route::get('offer/create', FormController::class)->name('admin.offer.create');
    Route::get('offer/{id}', ShowController::class)->name('admin.offer.show');
    Route::get('offer/{id}/edit', FormController::class)->name('admin.offer.edit');
    Route::post('offer', StoreController::class)->name('admin.offer.store');
    Route::put('offer/{id}', UpdateController::class)->name('admin.offer.update');
    Route::delete('offer/{id}', DestroyController::class)->name('admin.offer.destroy');

    Route::post('offer/{id}/update-status', UpdateStatusController::class)->name(
        'admin.offer.update-status'
    );
});

// Public routes for offer acceptance (authenticated users only)
Route::middleware(['auth'])->group(function () {
    Route::post('offer/{id}/accept', AcceptOfferController::class)->name('offer.accept');
    Route::post('offer/{id}/request-clarification', ClarificationRequestController::class)->name('offer.clarification-request');

    Route::post('offer/{id}/documents/{type}', UploadDocumentController::class)
        ->name('offer.documents.upload');
    Route::get('offer/{id}/documents/{document}/download', DownloadDocumentController::class)
        ->name('offer.documents.download');
    Route::delete('offer/{id}/documents/{document}', DeleteDocumentController::class)
        ->name('offer.documents.destroy');
});
