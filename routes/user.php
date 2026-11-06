<?php

use App\Domains\User\Controllers\Admin\InvitationController;
use App\Domains\User\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:administrator'])->prefix('admin')->group(function () {
    Route::get('users/search', [UserController::class, 'search'])->name('admin.users.search');
    Route::get('users/', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('users/export/csv', [UserController::class, 'exportCsv'])->name('admin.users.export.csv');
    Route::post('users/{user}/roles', [UserController::class, 'assignRoles'])->name('users.roles.assign');
    Route::post('users/{user}/block', [UserController::class, 'toggleBlock'])->name('users.block.toggle');

    // Invitation routes
    Route::get('invitations', [InvitationController::class, 'index'])->name('admin.invitations.index');
    Route::post('users/invite', [InvitationController::class, 'store'])->name('admin.users.invite');
    Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('admin.invitations.resend');
    Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('admin.invitations.destroy');
});
