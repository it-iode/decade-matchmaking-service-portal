<?php

declare(strict_types=1);

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Exceptions\OceanExpertAuthenticationException;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authService
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('auth/SignIn', [
            'status' => $request->session()->get('status'),
            'banner' => [
                'title' => 'Sign in to Ocean Decade Portal',
                'description' => 'Use your OceanExpert credentials to sign in, Or use your Google account or LinkedIn account.',
                'image' => '/assets/img/sidebar.png',
            ],
            'breadcrumbs' => [
                ['name' => 'Sign In'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $this->authService->authenticateWithCredentials(
                $credentials['email'],
                $credentials['password']
            );

            return redirect()
                ->intended('/home')
                ->with('status', 'You are logged in successfully.');
        } catch (OceanExpertAuthenticationException|Exception) {
            return to_route('sign.in')
                ->with('error', 'Login failed. Your Ocean Expert account may not yet be approved, or your ID or password is incorrect.')
                ->withInput($request->only('email'));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout();

        return to_route('index')
            ->with('status', 'You have been logged out successfully.');
    }
}
