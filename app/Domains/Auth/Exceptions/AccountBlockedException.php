<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountBlockedException extends Exception
{
    /**
     * Report the exception
     */
    public function report(): void
    {
        Log::channel('auth')->warning('Blocked account login attempt', [
            'message' => $this->getMessage(),
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): RedirectResponse
    {
        return redirect()
            ->route('sign.in')
            ->with('error', 'Your account has been blocked. Please contact support.');
    }
}
