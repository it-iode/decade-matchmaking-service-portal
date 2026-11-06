<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Settings\Models\Setting;
use App\Domains\Settings\Services\SettingsService;

/**
 * Loads OAuth client credentials stored in the settings table (editable from
 * the admin dashboard) into the runtime `services.*` config read by Socialite.
 * Env-based config values remain as a fallback when no setting is stored.
 */
class OAuthConfigService
{
    private const PROVIDER_SETTINGS = [
        'linkedin-openid' => [
            'client_id' => Setting::LINKEDIN_CLIENT_ID,
            'client_secret' => Setting::LINKEDIN_CLIENT_SECRET,
        ],
        'google' => [
            'client_id' => Setting::GOOGLE_CLIENT_ID,
            'client_secret' => Setting::GOOGLE_CLIENT_SECRET,
        ],
    ];

    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    /**
     * Apply the stored credentials for the given Socialite driver.
     */
    public function applyCredentials(string $driver): void
    {
        $settingPaths = self::PROVIDER_SETTINGS[$driver] ?? null;

        if ($settingPaths === null) {
            return;
        }

        foreach ($settingPaths as $configKey => $settingPath) {
            $value = $this->settingsService->getSetting($settingPath);

            if (! empty($value)) {
                config(["services.{$driver}.{$configKey}" => $value]);
            }
        }
    }
}
