<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Exceptions\OceanExpertAuthenticationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OceanExpertAuthService
{
    /**
     * Attempt to authenticate against the OceanExpert API.
     *
     * This class is a thin HTTP wrapper: it does not log. Failures are reported
     * through typed exceptions carrying whatever diagnostic context the caller
     * needs to log (HTTP status, truncated body, ...).
     *
     * @return array{token: string, user: array{email: string, password: string}}
     *
     * @throws RuntimeException when the auth URL is not configured
     * @throws OceanExpertAuthenticationException on any auth or transport failure
     */
    public function authenticate(string $email, string $password): array
    {
        $url = Config::get('services.oceanexpert.auth_url');
        if (! $url) {
            throw new RuntimeException('OceanExpert auth URL not configured.');
        }

        $response = Http::asJson()->post($url, [
            'username' => $email,
            'password' => $password,
        ]);

        if ($response->serverError()) {
            throw OceanExpertAuthenticationException::serviceUnavailable($response->status(), $response->body());
        }

        $data = $response->json();

        if ($response->failed() || (is_array($data) && isset($data['error']))) {
            throw OceanExpertAuthenticationException::invalidCredentials();
        }

        $token = Arr::get($data, 'token');
        if (! is_string($token) || $token === '') {
            throw OceanExpertAuthenticationException::invalidResponse('token missing from response');
        }

        return [
            'token' => $token,
            'user' => [
                'email' => $email,
                'password' => $password,
            ],
        ];
    }
}
