<?php

use App\Domains\Auth\Controllers\OtpController;
use App\Domains\Auth\Controllers\SessionController;
use App\Domains\Auth\Controllers\SocialController;
use App\Domains\User\Controllers\InvitationAcceptController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('signin', [SessionController::class, 'create'])
        ->name('sign.in');

    Route::post('signin', [SessionController::class, 'store'])
        ->middleware('throttle:authentication')
        ->name('sign.in.post');

    // LinkedIn OAuth Routes
    Route::get('auth/linkedin', [SocialController::class, 'linkedinRedirect'])
        ->name('auth.linkedin');

    Route::get('auth/linkedin/callback', [SocialController::class, 'linkedinCallback'])
        ->middleware('throttle:oauth-callback')
        ->name('auth.linkedin.callback');

    // Google OAuth Routes
    Route::get('auth/google', [SocialController::class, 'googleRedirect'])
        ->name('auth.google');

    Route::get('auth/google/callback', [SocialController::class, 'googleCallback'])
        ->middleware('throttle:oauth-callback')
        ->name('auth.google.callback');

    // OTP Authentication Routes
    Route::get('otp', [OtpController::class, 'showRequestForm'])
        ->name('otp.request');

    Route::post('otp/send', [OtpController::class, 'sendOtp'])
        ->middleware('throttle:5,1')
        ->name('otp.send');

    Route::get('otp/verify', [OtpController::class, 'showVerifyForm'])
        ->name('otp.verify');

    Route::post('otp/verify', [OtpController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('otp.verify.submit');

    Route::post('otp/resend', [OtpController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('otp.resend');

    // Invitation Routes
    Route::get('invitation/{token}', [InvitationAcceptController::class, 'show'])
        ->middleware('throttle:10,1')
        ->name('invitation.show');

    Route::post('invitation/{token}/accept', [InvitationAcceptController::class, 'accept'])
        ->middleware('throttle:5,1')
        ->name('invitation.accept');
});

Route::middleware('auth')->group(function () {
    Route::post('signout', [SessionController::class, 'destroy'])
        ->name('sign.out');
});
