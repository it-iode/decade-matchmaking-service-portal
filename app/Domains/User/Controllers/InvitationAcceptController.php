<?php

declare(strict_types=1);

namespace App\Domains\User\Controllers;

use App\Domains\User\Services\Invitation\InvitationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class InvitationAcceptController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService
    ) {}

    /**
     * Show the invitation acceptance page
     */
    public function show(string $token): Response
    {
        $invitation = $this->invitationService->validateToken($token);

        if (! $invitation) {
            return Inertia::render('auth/InvitationExpired', [
                'message' => 'This invitation link is invalid or has expired.',
            ]);
        }

        return Inertia::render('auth/InvitationAccept', [
            'invitation' => [
                'email' => $invitation->email,
                'inviter_name' => $invitation->inviter->name ?? 'Ocean Decade Portal Admin',
                'expires_at' => $invitation->expires_at->format('F j, Y'),
            ],
            'token' => $token,
        ]);
    }

    /**
     * Accept the invitation and redirect to OTP login
     */
    public function accept(string $token): RedirectResponse
    {
        $invitation = $this->invitationService->validateToken($token);

        if (! $invitation) {
            return redirect()->route('sign.in')
                ->with('error', 'This invitation link is invalid or has expired.');
        }

        try {
            // Accept invitation (marks as accepted, user already exists)
            $user = $this->invitationService->accept($invitation);

            // Store email in session for OTP flow
            session(['otp_email' => $user->email]);

            // Redirect to OTP verification page
            return redirect()->route('otp.request')
                ->with('success', 'Welcome! Please verify your identity to continue.');
        } catch (Throwable $e) {
            Log::error('Failed to accept invitation', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('sign.in')
                ->with('error', 'An error occurred while accepting the invitation. Please try again.');
        }
    }
}
