<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UnsupportedAuthenticationMethodException extends Exception
{
    /**
     * Report the exception
     */
    public function report(): void
    {
        Log::channel('auth')->error('Unsupported authentication method attempted', [
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
            ->with('error', 'Authentication method not supported');
    }
}
