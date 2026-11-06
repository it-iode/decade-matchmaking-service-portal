<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Settings\Services\SettingsService;
use App\Infrastructure\Email\Services\EmailLogger;
use App\Infrastructure\Email\Services\EmailTemplateService;
use App\Infrastructure\Email\Services\HealthCheckService;
use App\Infrastructure\Email\Services\MandrillClient;
use App\Infrastructure\Email\Services\RateLimiter;
use App\Infrastructure\Email\Services\TemplateResolver;
use App\Infrastructure\Email\Services\VariableValidator;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Log;
use MailchimpTransactional\ApiClient;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Mandrill API client as singleton
        $this->app->singleton(ApiClient::class, function (Application $app) {
            $apiKey = config('mail-templates.mandrill.api_key');
            $client = new ApiClient;
            $client->setApiKey($apiKey);

            return $client;
        });

        // Register email services as singletons for performance
        $this->app->singleton(TemplateResolver::class);
        $this->app->singleton(VariableValidator::class);
        $this->app->singleton(EmailLogger::class);
        $this->app->singleton(MandrillClient::class);
        $this->app->singleton(EmailTemplateService::class);
        $this->app->singleton(RateLimiter::class);
        $this->app->singleton(HealthCheckService::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure Mandrill API key from database
        $this->configureMandrillApiKey();

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/mail-templates.php' => config_path('mail-templates.php'),
            ], 'mail-templates');

            // Load migrations
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }

        // Email event listeners are now auto-discovered by Laravel 12
    }

    /**
     * Configure Mandrill API key from database (database-only, no ENV fallback).
     *
     * Exception handling is deferred to MandrillClient::createClient() when API client is actually used.
     */
    private function configureMandrillApiKey(): void
    {
        // Skip if running migrations to prevent errors when settings table doesn't exist yet
        if ($this->app->runningInConsole() && ! $this->app->environment('testing')) {
            $command = $_SERVER['argv'][1] ?? '';
            if (str_contains($command, 'migrate')) {
                return;
            }
        }

        try {
            /** @var SettingsService $settingsService */
            $settingsService = $this->app->make(SettingsService::class);

            // Get API key from database only (no ENV fallback)
            $apiKey = $settingsService->getSetting('mandrill_api_key');

            // Set the API key in config (even if null - exception will be thrown when MandrillClient is used)
            config(['mail-templates.mandrill.api_key' => $apiKey]);

            // Log the configuration source for debugging
            if (config('app.debug')) {
                if (empty($apiKey)) {
                    Log::warning('Mandrill API key not configured in database - emails will fail until configured');
                }
            }
        } catch (Exception $e) {
            // During fresh installs or when database is not available, silently skip
            // The MandrillClient will throw its own exception when actually needed
            if (config('app.debug')) {
                Log::warning('Could not load Mandrill API key from database: '.$e->getMessage());
            }
        }
    }
}
