<?php

namespace App\Providers;

use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Services\AuthenticationService;
use App\Domains\Auth\Services\Strategies\OAuthAuthStrategy;
use App\Domains\Auth\Services\Strategies\OceanExpertAuthStrategy;
use App\Domains\Document\Actions\DocumentActionProvider;
use App\Domains\Document\Models\Document;
use App\Domains\Notification\Channels\SystemNotificationChannel;
use App\Domains\Notification\Models\RequestSubscription;
use App\Domains\Notification\Models\SystemNotification;
use App\Domains\Notification\Models\UserNotificationSetting;
use App\Domains\Offer\Actions\OfferActionProvider;
use App\Domains\Offer\Events\OfferAccepted;
use App\Domains\Offer\Events\OfferCreated;
use App\Domains\Offer\Events\OfferRejected;
use App\Domains\Offer\Listeners\SendOfferAcceptedNotifications;
use App\Domains\Offer\Listeners\SendOfferCreatedNotifications;
use App\Domains\Offer\Listeners\SendOfferRejectedNotifications;
use App\Domains\Offer\Models\Offer;
use App\Domains\Offer\Observers\OfferObserver;
use App\Domains\Offer\Policies\OfferPolicy;
use App\Domains\Opportunity\Events\OpportunityClicked;
use App\Domains\Opportunity\Listeners\RecordOpportunityClick;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Observers\OpportunityObserver;
use App\Domains\Opportunity\Policies\OpportunityPolicy;
use App\Domains\ReferenceData\Models\IOCPlatform;
use App\Domains\ReferenceData\Models\Organization;
use App\Domains\Request\Actions\RequestActionProvider;
use App\Domains\Request\Events\RequestExpressInterest;
use App\Domains\Request\Events\RequestPartnerMatched;
use App\Domains\Request\Events\RequestStatusChanged;
use App\Domains\Request\Events\RequestSubmitted;
use App\Domains\Request\Events\RequestValidated;
use App\Domains\Request\Listeners\ExpressInterestListener;
use App\Domains\Request\Listeners\SendRequestCreatedNotifications;
use App\Domains\Request\Listeners\SendRequestPartnerMatchedNotifications;
use App\Domains\Request\Listeners\SendRequestStatusChangedNotifications;
use App\Domains\Request\Listeners\SendRequestValidatedNotifications;
use App\Domains\Request\Models\Detail as RequestDetail;
use App\Domains\Request\Models\Request as OCDRequest;
use App\Domains\Request\Models\Status as RequestStatus;
use App\Domains\Request\Observers\RequestObserver;
use App\Domains\Request\Policies\RequestPolicy;
use App\Domains\Settings\Models\Setting;
use App\Domains\User\Events\UserRegistered;
use App\Domains\User\Events\UserRoleChanged;
use App\Domains\User\Listeners\NotifyAdminsWhenNewUserRegistred;
use App\Domains\User\Listeners\SendMailUserRolesUpdates;
use App\Domains\User\Models\User;
use App\Domains\User\Models\UserInvitation;
use App\Domains\User\Observers\UserObserver;
use App\Domains\User\Policies\UserPolicy;
use App\Infrastructure\Email\Channels\MandrillChannel;
use App\Infrastructure\Email\Jobs\SendTransactionalEmail;
use App\Infrastructure\Email\Models\EmailLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerLegacyClassAliases();

        // Register simplified Action Provider Pattern services
        $this->app->singleton(RequestActionProvider::class);
        $this->app->singleton(OfferActionProvider::class);
        $this->app->singleton(DocumentActionProvider::class);

        // Register authentication services
        $this->app->singleton(
            AuthenticationServiceInterface::class,
            AuthenticationService::class
        );

        // Register authentication strategies as singletons
        $this->app->singleton(OceanExpertAuthStrategy::class);
        $this->app->singleton(OAuthAuthStrategy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        JsonResource::withoutWrapping();

        $this->configureModelConventions();

        // Register custom notification channels
        Notification::extend('mandrill', function ($app) {
            return new MandrillChannel;
        });

        Notification::extend('system', function ($app) {
            return new SystemNotificationChannel;
        });

        // Configure rate limiters for authentication
        $this->configureRateLimiting();

        // Register the observers
        OCDRequest::observe(RequestObserver::class);
        Offer::observe(OfferObserver::class);
        Opportunity::observe(OpportunityObserver::class);
        User::observe(UserObserver::class);

        // Register policies
        Gate::policy(OCDRequest::class, RequestPolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(Offer::class, OfferPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        // Event listeners are discovered from app/Domains/*/Listeners and app/Infrastructure/*/Listeners
        // (see bootstrap/app.php); commands from app/Domains/*/Console and app/Infrastructure/*/Console.

        if (app()->environment('production') && config('services.opportunity_click.ip_pepper') === '') {
            \Log::warning('OPPORTUNITY_CLICK_IP_PEPPER is empty in production; opportunity click IP hashes are not peppered.');
        }
    }

    /**
     * Transitional aliases for classes moved during the 2026-09 restructuring.
     *
     * Queue payloads (jobs.payload, failed_jobs) and SerializesModels store the FQCN,
     * so anything queued before a deploy still references the old name. Keep each
     * alias for one release after the move, then delete it.
     *
     * @return array<string, class-string> old FQCN => current class
     */
    private static function legacyClassAliases(): array
    {
        return [
            // step 1 (2026-09-18)
            'App\\Jobs\\Email\\SendTransactionalEmail' => SendTransactionalEmail::class,
            // step 2 (2026-09-18) - belt and braces only: none of these models is carried in a queued payload
            'App\\Models\\Document' => Document::class,
            'App\\Models\\Organization' => Organization::class,
            'App\\Models\\IOCPlatform' => IOCPlatform::class,
            'App\\Models\\Setting' => Setting::class,
            // step 3 (2026-09-18) - load-bearing: User is carried by queued notifications/listeners
            'App\\Models\\User' => User::class,
            'App\\Models\\UserInvitation' => UserInvitation::class,
            'App\\Listeners\\User\\SendMailUserRolesUpdates' => SendMailUserRolesUpdates::class,
            'App\\Listeners\\User\\NotifyAdminsWhenNewUserRegistred' => NotifyAdminsWhenNewUserRegistred::class,
            'App\\Events\\User\\UserRoleChanged' => UserRoleChanged::class,
            'App\\Events\\User\\UserRegistered' => UserRegistered::class,
            // step 5 (2026-09-18) - load-bearing: RecordOpportunityClick is queued and OpportunityClicked carries an Opportunity
            'App\\Models\\Opportunity' => Opportunity::class,
            'App\\Listeners\\Opportunity\\RecordOpportunityClick' => RecordOpportunityClick::class,
            'App\\Events\\Opportunity\\OpportunityClicked' => OpportunityClicked::class,
            // step 6 (2026-09-18) - load-bearing: all three offer listeners are queued and their events carry an Offer
            'App\\Models\\Request\\Offer' => Offer::class,
            'App\\Listeners\\RequestOffer\\SendOfferCreatedNotifications' => SendOfferCreatedNotifications::class,
            'App\\Listeners\\RequestOffer\\SendOfferRejectedNotifications' => SendOfferRejectedNotifications::class,
            'App\\Listeners\\RequestOffer\\SendOfferAcceptedNotifications' => SendOfferAcceptedNotifications::class,
            'App\\Events\\RequestOffer\\OfferCreated' => OfferCreated::class,
            'App\\Events\\RequestOffer\\OfferRejected' => OfferRejected::class,
            'App\\Events\\OfferAccepted' => OfferAccepted::class,
            // step 7 (2026-09-19) - load-bearing: all five request listeners are queued and their events carry a Request
            'App\\Models\\Request' => OCDRequest::class,
            'App\\Listeners\\Request\\ExpressInterestListener' => ExpressInterestListener::class,
            'App\\Listeners\\Request\\SendRequestCreatedNotifications' => SendRequestCreatedNotifications::class,
            'App\\Listeners\\Request\\SendRequestPartnerMatchedNotifications' => SendRequestPartnerMatchedNotifications::class,
            'App\\Listeners\\Request\\SendRequestStatusChangedNotifications' => SendRequestStatusChangedNotifications::class,
            'App\\Listeners\\Request\\SendRequestValidatedNotifications' => SendRequestValidatedNotifications::class,
            'App\\Events\\Request\\RequestSubmitted' => RequestSubmitted::class,
            'App\\Events\\Request\\RequestValidated' => RequestValidated::class,
            'App\\Events\\Request\\RequestPartnerMatched' => RequestPartnerMatched::class,
            'App\\Events\\Request\\RequestStatusChanged' => RequestStatusChanged::class,
            'App\\Events\\Request\\RequestExpressInterest' => RequestExpressInterest::class,
        ];
    }

    protected function registerLegacyClassAliases(): void
    {
        foreach (self::legacyClassAliases() as $old => $current) {
            if (! class_exists($old, false)) {
                class_alias($current, $old);
            }
        }
    }

    /**
     * Decouple persisted class names from the PHP namespace layout.
     *
     * - The morph map stores short aliases in polymorphic *_type columns
     *   (documents.parent_type, spatie model_has_roles.model_type, one_time_passwords.authenticatable_type)
     *   so models can be moved between namespaces without touching data.
     *   Every new model MUST be added here.
     * - The factory resolver maps any model, whatever its namespace, to Database\Factories\{Model}Factory.
     */
    protected function configureModelConventions(): void
    {
        Relation::enforceMorphMap(self::morphMap());

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    /**
     * @return array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public static function morphMap(): array
    {
        return [
            'user' => User::class,
            'user_invitation' => UserInvitation::class,
            'user_notification_setting' => UserNotificationSetting::class,
            'request' => OCDRequest::class,
            'request_detail' => RequestDetail::class,
            'request_status' => RequestStatus::class,
            'request_subscription' => RequestSubscription::class,
            'offer' => Offer::class,
            'opportunity' => Opportunity::class,
            'document' => Document::class,
            'organization' => Organization::class,
            'ioc_platform' => IOCPlatform::class,
            'setting' => Setting::class,
            'system_notification' => SystemNotification::class,
            'email_log' => EmailLog::class,
        ];
    }

    /**
     * Configure rate limiting for authentication endpoints
     */
    protected function configureRateLimiting(): void
    {
        // Authentication rate limiting: 5 attempts per minute per email
        RateLimiter::for('authentication', function (HttpRequest $request) {
            return Limit::perMinute(5)->by($request->input('email') ?? $request->ip());
        });

        // OAuth callback rate limiting: 10 attempts per minute per IP
        RateLimiter::for('oauth-callback', function (HttpRequest $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
