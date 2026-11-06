<?php

declare(strict_types=1);

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Exceptions\OtpAuthenticationException;
use App\Domains\Auth\Requests\OtpRequestRequest;
use App\Domains\Auth\Requests\OtpVerifyRequest;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authService
    ) {}

    /**
     * Display the OTP request form (email entry).
     */
    public function showRequestForm(Request $request): Response
    {
        return Inertia::render('auth/OtpRequest', [
            'status' => $request->session()->get('status'),
            'banner' => [
                'title' => 'Sign in with Email',
                'description' => 'Enter your email address to receive a one-time password.',
                'image' => '/assets/img/sidebar.png',
            ],
            'breadcrumbs' => [
                ['name' => 'Sign In', 'href' => route('sign.in')],
                ['name' => 'Email OTP'],
            ],
        ]);
    }

    /**
     * Handle OTP request (send OTP to email).
     */
    public function sendOtp(OtpRequestRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->validated('email')));
        $ipAddress = $request->ip();

        // Find user
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->logOtpAction($email, 'user_not_found', $ipAddress);

            // Return success to prevent email enumeration
            return response()->json([
                'success' => true,
                'message' => 'If an account exists with this email, an OTP has been sent.',
            ]);
        }

        // Check if user is blocked
        if ($user->isBlocked()) {
            $this->logOtpAction($email, 'user_blocked', $ipAddress);

            return response()->json([
                'success' => false,
                'message' => 'This account has been blocked. Please contact support.',
                'error' => 'user_blocked',
            ], 403);
        }

        // Send OTP using Spatie's native method
        $user->sendOneTimePassword();

        $this->logOtpAction($email, 'requested', $ipAddress);

        // Store email in session for verification step
        $request->session()->put('otp_email', $email);

        return response()->json([
            'success' => true,
            'message' => 'If an account exists with this email, an OTP has been sent.',
        ]);
    }

    /**
     * Display the OTP verification form.
     */
    public function showVerifyForm(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('otp_email');

        if (! $email) {
            return redirect()->route('otp.request')
                ->with('error', 'Please enter your email first.');
        }

        return Inertia::render('auth/OtpVerify', [
            'email' => $email,
            'maskedEmail' => $this->maskEmail($email),
            'banner' => [
                'title' => 'Enter Verification Code',
                'description' => 'We sent a 6-digit code to your email.',
                'image' => '/assets/img/sidebar.png',
            ],
            'breadcrumbs' => [
                ['name' => 'Sign In', 'href' => route('sign.in')],
                ['name' => 'Email OTP', 'href' => route('otp.request')],
                ['name' => 'Verify'],
            ],
        ]);
    }

    /**
     * Handle OTP verification.
     */
    public function verify(OtpVerifyRequest $request): JsonResponse
    {
        // Session email validation stays in controller
        $email = $request->session()->get('otp_email') ?? $request->validated('email');

        if (! $email) {
            return response()->json([
                'success' => false,
                'message' => 'No email address in session. Please start over.',
                'error_code' => 'no_email',
            ], 400);
        }

        $email = strtolower(trim($email));

        try {
            // Delegate ALL authentication logic to the service
            $this->authService->authenticateWithOtp(
                email: $email,
                code: $request->validated('code'),
                ipAddress: $request->ip()
            );

            // Clear session on success
            $request->session()->forget('otp_email');

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully.',
                'redirect' => route('user.home'),
            ]);
        } catch (OtpAuthenticationException $e) {
            // Laravel auto-renders via exception's render() method
            throw $e;
        }
    }

    /**
     * Resend OTP to the stored email.
     */
    public function resend(Request $request): JsonResponse
    {
        $email = $request->session()->get('otp_email');

        if (! $email) {
            return response()->json([
                'success' => false,
                'message' => 'No email address in session.',
            ], 400);
        }

        $email = strtolower(trim($email));
        $ipAddress = $request->ip();

        // Find user
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->logOtpAction($email, 'user_not_found', $ipAddress);

            // Return success to prevent email enumeration
            return response()->json([
                'success' => true,
                'message' => 'A new OTP has been sent to your email.',
            ]);
        }

        // Check if user is blocked
        if ($user->isBlocked()) {
            $this->logOtpAction($email, 'user_blocked', $ipAddress);

            return response()->json([
                'success' => false,
                'message' => 'This account has been blocked. Please contact support.',
                'error' => 'user_blocked',
            ], 403);
        }

        // Send OTP using Spatie's native method
        $user->sendOneTimePassword();

        $this->logOtpAction($email, 'resent', $ipAddress);

        return response()->json([
            'success' => true,
            'message' => 'A new OTP has been sent to your email.',
        ]);
    }

    /**
     * Mask email for display (e.g., tes***@example.com)
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        $visibleLength = min(3, strlen($name));
        $masked = substr($name, 0, $visibleLength).
            str_repeat('*', max(0, strlen($name) - $visibleLength));

        return $masked.'@'.$domain;
    }

    /**
     * Log OTP action for audit trail.
     */
    private function logOtpAction(string $email, string $action, ?string $ipAddress = null): void
    {
        Log::channel('auth')->info('OTP action', [
            'email_hash' => hash('sha256', $email),
            'action' => $action,
            'ip_address' => $ipAddress,
        ]);
    }
}
