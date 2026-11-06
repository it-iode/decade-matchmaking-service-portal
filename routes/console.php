<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\OneTimePasswords\Models\OneTimePassword;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Queue processing schedule
Schedule::command('queue:work --queue=default database --stop-when-empty --max-jobs=100 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();


// Process OTP mail and notifications
Schedule::command('queue:work database --queue=otp-mail --stop-when-empty --max-jobs=50 --max-time=50')
    ->everyFiveSeconds()
    ->withoutOverlapping()
    ->runInBackground();

// Restart queue workers daily to prevent memory leaks
Schedule::command('queue:restart')
    ->daily()
    ->at('03:00');

// Clean up old failed jobs (older than 7 days)
Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->at('02:00');

// Prune Telescope entries (older than 7 days)
Schedule::command('telescope:prune --hours=168')
    ->daily()
    ->at('02:15');

// Prune expired one-time passwords (Spatie OTP)
Schedule::command('model:prune', [
    '--model' => [OneTimePassword::class],
])
    ->daily()
    ->at('02:30');

// Queue health check every 5 minutes
Schedule::command('queue:health-check')
    ->everyFiveMinutes()
    ->runInBackground();

// Close expired opportunities daily at 2:00 AM
Schedule::command('opportunities:close-expired --force')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/opportunity-closure.log'));

// Send weekly opportunities newsletter every Sunday at 8:00 AM
Schedule::command('newsletter:send-weekly')
    ->mondays()
    ->at('11:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/newsletter-opportunities.log'));

Schedule::command('queue:work --queue=default --stop-when-empty --max-jobs=50 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        // Send notification
    });

