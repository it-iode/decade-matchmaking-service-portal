<?php

use App\Domains\Document\Controllers\DocumentsController;
use App\Domains\Notification\Controllers\Admin\SubscriptionController;
use App\Domains\Notification\Controllers\Admin\SystemNotificationsController;
use App\Domains\Notification\Controllers\SubscriptionController as UserSubscriptionController;
use App\Domains\Notification\Controllers\UnsubscribeController;
use App\Domains\ReferenceData\Controllers\IOCPlatformsController;
use App\Domains\ReferenceData\Controllers\OrganizationsController;
use App\Domains\Settings\Controllers\SettingsController;
use App\Domains\User\Controllers\DashboardController;
use App\Http\Controllers\Admin\SampleDataController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\UserGuideController;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Support\Facades\Route;

Route::get('/', IndexController::class)->name('index');
Route::get('organizations', [OrganizationsController::class, 'index'])->name('organizations.index');
Route::get('ioc-platforms', [IOCPlatformsController::class, 'index'])->name('ioc-platforms.index');

// Email Unsubscribe routes (public - no auth required for email links)

Route::prefix('unsubscribe')->group(function () {
    Route::get('{user}', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
    Route::post('{user}', [UnsubscribeController::class, 'unsubscribe'])->name('unsubscribe.process');
    Route::get('{user}/success', [UnsubscribeController::class, 'success'])->name('unsubscribe.success');
});

// Access denied route for direct navigation
Route::get('/access-denied', function () {
    return \Inertia\Inertia::render('auth/AccessDenied', [
        'requiredRoles' => request('roles', []),
        'contactEmail' => 'cdf@unesco.org',
        'attemptedRoute' => request('route'),
    ]);
})->name('access.denied')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('home', [HomeController::class, 'index'])->name('user.home');
});

Route::middleware(['auth', 'role:user'])->group(function () {

    // User subscription routes
    Route::get('subscriptions', [UserSubscriptionController::class, 'index'])->name('user.subscriptions.index');
    Route::post('subscriptions/subscribe', [UserSubscriptionController::class, 'subscribe'])->name('user.subscriptions.subscribe');
    Route::post('subscriptions/unsubscribe', [UserSubscriptionController::class, 'unsubscribe'])->name('user.subscriptions.unsubscribe');
    Route::get('subscriptions/status', [UserSubscriptionController::class, 'status'])->name('user.subscriptions.status');
    Route::post('offer/{id}/document', [DocumentsController::class, 'storeOfferDocument'])->name(
        'user.offer.document.store'
    );
    Route::delete('user/document/{document}', [DocumentsController::class, 'destroy'])->name(
        'user.document.destroy'
    );
    Route::get('user/document/{document}/download', [DocumentsController::class, 'download']
    )->name('user.document.download');
});

Route::middleware(['auth', 'role:administrator'])->prefix('admin')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');
    Route::get('settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::post('settings/organizations/csv-upload', [SettingsController::class, 'uploadOrganizationsCsv'])->name('admin.settings.organizations.csv-upload');

    // Admin subscription management routes
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('admin.subscriptions.index');
    Route::post('subscriptions/subscribe-user', [SubscriptionController::class, 'subscribeUser'])->name('admin.subscriptions.subscribe-user');
    Route::post('subscriptions/unsubscribe-user', [SubscriptionController::class, 'unsubscribeUser'])->name('admin.subscriptions.unsubscribe-user');
    Route::get('subscriptions/request/{request}', [SubscriptionController::class, 'requestSubscribers'])->name('admin.subscriptions.request-subscribers');
    Route::get('subscriptions/user/{user}', [SubscriptionController::class, 'userSubscriptions'])->name('admin.subscriptions.user-subscriptions');
    // User Management Routes

    Route::get('system_notifications', [SystemNotificationsController::class, 'index'])->name('admin.notifications.index');

    Route::get('notifications/{notification}', [SystemNotificationsController::class, 'show'])->name(
        'admin.notifications.show'
    );
    Route::get('notifications/{notification}/read', [SystemNotificationsController::class, 'markAsRead'])->name(
        'admin.notifications.read'
    );

    // Developer tool: reset the database to a fake dataset (local / staging only).
    if (SampleDataSeeder::isAllowedEnvironment()) {
        Route::get('sample-data', [SampleDataController::class, 'index'])->name('admin.sample-data.index');
        Route::post('sample-data', [SampleDataController::class, 'run'])->name('admin.sample-data.run');
    }
});

Route::prefix('guide')->group(function () {
    Route::get('platform-guide.pdf', [UserGuideController::class, 'download'])->name('user.guide');
});

require __DIR__.'/user.php';
require __DIR__.'/auth.php';
require __DIR__.'/request.php';
require __DIR__.'/opportunity.php';
require __DIR__.'/offer.php';
require __DIR__.'/notification-preferences.php';
