<?php

use App\Domains\Document\Controllers\DocumentsController;
use App\Domains\Request\Controllers\ExportCsvController;
use App\Domains\Request\Controllers\ExportRequestPdfController;
use App\Domains\Request\Controllers\ExpressInterestController;
use App\Domains\Request\Controllers\ListController;
use App\Domains\Request\Controllers\RequestFormController;
use App\Domains\Request\Controllers\RequestManagementController;
use App\Domains\Request\Controllers\ViewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Request Routes
|--------------------------------------------------------------------------
|
| Here are all routes related to request functionality including
| request creation, editing, viewing, management, and file handling.
|
*/

// User role request routes
Route::middleware(['auth', 'role:user'])->group(function () {
    // Request CRUD operations
    Route::get('request/create', [RequestFormController::class, 'form'])->name('request.create');
    Route::get('request/{id}/edit', [RequestFormController::class, 'form'])->name('request.edit');
    Route::get('public/request/{id}/show', [ViewController::class, 'show'])->name('request.public.show');
    Route::get('me/request/{id}/show', [ViewController::class, 'show'])->name('request.me.show');
    Route::get('matched/request/{id}/show', [ViewController::class, 'show'])->name('request.matched.show');
    Route::get('subscribed/request/{id}/show', [ViewController::class, 'show'])->name('request.subscribed.show');
    Route::post('request/submit/{id?}', [RequestFormController::class, 'submit'])->name('request.submit');
    Route::delete('request/{id}', [RequestManagementController::class, 'destroy'])->name('user.request.destroy');

    Route::get('pdf/{id}', ExportRequestPdfController::class)->name('request.pdf');

    // UPDATED: Use __invoke instead of specific methods
    Route::get('request/me/list', ListController::class)->name('request.me.list');
    Route::get('request/me/matched-requests', ListController::class)->name('request.me.matched-requests');
    Route::get('request/me/subscribed-requests', ListController::class)->name('request.me.subscribed-requests');

    // Request interaction
    Route::post('request/{id}/express-interest', ExpressInterestController::class)->name(
        'request.express.interest'
    );
    Route::patch('request/{id}/update-status', [RequestManagementController::class, 'updateStatus'])->name(
        'request.update.status'
    );

    // Request document management
    Route::post('request/{request}/document', [DocumentsController::class, 'store'])->name(
        'user.request.document.store'
    );
});

// Partner role request routes
Route::middleware(['auth', 'role:partner'])->group(function () {
    // UPDATED: Use __invoke instead of publicRequests method
    Route::get('request/list', ListController::class)->name('request.list');
});

Route::middleware(['auth', 'role:administrator'])->prefix('admin')->group(function () {
    Route::post('request/{id}/update-status', [RequestManagementController::class, 'updateStatus'])->name(
        'admin.request.update-status'
    );

    // UPDATED: Use __invoke instead of list method
    Route::get('request/list', ListController::class)->name('admin.request.list');

    Route::get('request/{id}/show', [ViewController::class, 'show'])->name('admin.request.show');

    // CSV export using dedicated controller
    Route::get('request/export/csv', ExportCsvController::class)->name('admin.request.export.csv');
});
