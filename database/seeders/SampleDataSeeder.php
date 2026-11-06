<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Notification\Models\SystemNotification;
use App\Domains\Notification\Models\UserNotificationSetting;
use App\Domains\Offer\Enums\RequestOfferStatus;
use App\Domains\Offer\Models\Offer;
use App\Domains\Opportunity\Enums\CoverageActivity;
use App\Domains\Opportunity\Enums\Status as OpportunityStatus;
use App\Domains\Opportunity\Enums\TargetAudience as OpportunityTargetAudience;
use App\Domains\Opportunity\Enums\ThematicAreas;
use App\Domains\Opportunity\Enums\Type as OpportunityType;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\Request\Enums\DeliveryFormat;
use App\Domains\Request\Enums\ProjectStage;
use App\Domains\Request\Enums\RelatedActivity;
use App\Domains\Request\Enums\SupportType;
use App\Domains\Request\Enums\TargetAudience as RequestTargetAudience;
use App\Domains\Request\Models\Detail;
use App\Domains\Request\Models\Request as OCDRequest;
use App\Domains\Request\Models\Status as RequestStatus;
use App\Domains\User\Models\User;
use App\Shared\Enums\Country;
use App\Shared\Enums\Language;
use App\Shared\Enums\Ocean;
use App\Shared\Enums\Region;
use App\Shared\Enums\YesNo;
use Carbon\CarbonImmutable;
use Faker\Generator as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Sample dataset for local and staging environments.
 *
 * Usage:
 *   php artisan db:seed --class=SampleDataSeeder      (console, asks for confirmation)
 *   /admin/sample-data                                (admin UI, local/staging only)
 *
 * What it does, in order:
 *   1. Refuses to run outside the allowed environments.
 *   2. Resolves the protected accounts (see PROTECTED_EMAILS) and every entity
 *      related to them (requests, offers, opportunities, subscriptions, ...).
 *   3. Deletes everything else.
 *   4. Generates fake users, opportunities (every type and status) and
 *      requests (every status, varied activities/formats/support types), plus
 *      offers, subscriptions, notification settings and notifications.
 *
 * Model events are disabled for the whole run so no observer fires and no
 * email / queue job is dispatched while seeding. Polymorphic type columns are
 * written through getMorphClass() so they match the enforced morph map.
 */
final class SampleDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /** `testing` is included so the seeder can be covered by PHPUnit. */
    private const ALLOWED_ENVIRONMENTS = ['local', 'staging', 'testing'];

    /** Accounts that are never deleted, nor any entity related to them. */
    private const PROTECTED_EMAILS = [
        'n.kim1@unesco.org',
        'labidi.saddem@gmail.com',
    ];

    private const FAKE_EMAIL_DOMAIN = 'sample.oceandecade.test';

    private const FAKE_PASSWORD = 'password';

    private const ADMIN_COUNT = 2;

    private const PARTNER_COUNT = 8;

    private const USER_COUNT = 30;

    private const OPPORTUNITIES_PER_TYPE = 4;

    private const REQUESTS_PER_STATUS = 4;

    private const REQUEST_STATUSES = [
        'draft' => 'Draft',
        'under_review' => 'Under Review',
        'validated' => 'Validated',
        'offer_made' => 'Offer made',
        'in_implementation' => 'In Implementation',
        'rejected' => 'Rejected',
        'unmatched' => 'Unmatched',
        'closed' => 'Closed',
    ];

    private Faker $faker;

    /** @var Collection<int, User> */
    private Collection $admins;

    /** @var Collection<int, User> */
    private Collection $partners;

    /** @var Collection<int, User> */
    private Collection $users;

    /** @var array<string, int> status_code => id */
    private array $statusIds = [];

    /** @var array<string, int> counters filled by the create* methods */
    private array $created = [];

    /**
     * Console entry point. Asks for confirmation, then delegates to execute().
     */
    public function run(): void
    {
        self::assertEnvironmentAllowed();

        $this->reportPlan($this->plan());

        if ($this->command !== null && ! $this->command->confirm('Delete all non-protected data and generate the sample dataset?', false)) {
            $this->command->warn('Aborted. Nothing was changed.');

            return;
        }

        $summary = $this->execute();
        $this->reportSummary($summary);
    }

    /**
     * Programmatic entry point (admin UI, tests). No confirmation: the caller
     * is responsible for it. Returns a structured summary of what happened.
     *
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        self::assertEnvironmentAllowed();
        $this->assertFakerAvailable();

        $this->faker = \Faker\Factory::create();
        $this->faker->seed(20261005);
        $this->created = [];

        $startedAt = microtime(true);

        // WithoutModelEvents only wraps __invoke(); when called directly we
        // disable model events ourselves so observers never fire.
        $summary = Model::withoutEvents(function (): array {
            $plan = $this->plan();
            $protected = $plan['protected_ids'];

            DB::transaction(fn () => $this->purge($protected));
            $this->info('Purge complete.');

            DB::transaction(function (): void {
                $this->ensureReferenceData();
                $this->createUsers();
                $this->createOpportunities();
                $this->createRequests();
                $this->createNotificationSettings();
                $this->createSystemNotifications();
            });

            return [
                'environment' => app()->environment(),
                'protected_emails' => self::PROTECTED_EMAILS,
                'protected' => $plan['keep'],
                'deleted' => $plan['delete'],
                'created' => $this->created,
            ];
        });

        $summary['credentials'] = self::credentials();
        $summary['duration_seconds'] = round(microtime(true) - $startedAt, 2);

        return $summary;
    }

    /**
     * Dry-run view: what would be kept and what would be deleted.
     *
     * @return array{keep: array<string, int>, delete: array<string, int>, protected_emails: array<string>, protected_ids: array<string, array<int|string>>}
     */
    public function plan(): array
    {
        $protected = $this->resolveProtectedEntities();

        return [
            'keep' => [
                'users' => count($protected['users']),
                'requests' => count($protected['requests']),
                'offers' => count($protected['offers']),
                'opportunities' => count($protected['opportunities']),
                'documents' => count($protected['documents']),
            ],
            'delete' => [
                'users' => DB::table('users')->whereNotIn('id', $protected['users'])->count(),
                'requests' => DB::table('requests')->whereNotIn('id', $protected['requests'])->count(),
                'offers' => DB::table('request_offers')->whereNotIn('id', $protected['offers'])->count(),
                'opportunities' => DB::table('opportunities')->whereNotIn('id', $protected['opportunities'])->count(),
                'documents' => DB::table('documents')->whereNotIn('id', $protected['documents'])->count(),
            ],
            'protected_emails' => self::PROTECTED_EMAILS,
            'protected_ids' => $protected,
        ];
    }

    public static function isAllowedEnvironment(): bool
    {
        return in_array(app()->environment(), self::ALLOWED_ENVIRONMENTS, true);
    }

    /**
     * @return array<string>
     */
    public static function protectedEmails(): array
    {
        return self::PROTECTED_EMAILS;
    }

    /**
     * @return array{domain: string, password: string, logins: array<string>}
     */
    public static function credentials(): array
    {
        return [
            'domain' => self::FAKE_EMAIL_DOMAIN,
            'password' => self::FAKE_PASSWORD,
            'logins' => [
                sprintf('admin1..admin%d', self::ADMIN_COUNT),
                sprintf('partner1..partner%d', self::PARTNER_COUNT),
                sprintf('user1..user%d', self::USER_COUNT),
            ],
        ];
    }

    // ---------------------------------------------------------------------
    // Guards
    // ---------------------------------------------------------------------

    private static function assertEnvironmentAllowed(): void
    {
        if (! self::isAllowedEnvironment()) {
            throw new RuntimeException(sprintf(
                'SampleDataSeeder can only run in [%s]; current environment is "%s".',
                implode(', ', self::ALLOWED_ENVIRONMENTS),
                app()->environment()
            ));
        }
    }

    private function assertFakerAvailable(): void
    {
        if (! class_exists(\Faker\Factory::class)) {
            throw new RuntimeException('fakerphp/faker is not installed (it is a dev dependency). Run "composer install" with dev dependencies first.');
        }
    }

    // ---------------------------------------------------------------------
    // Protected entity resolution
    // ---------------------------------------------------------------------

    /**
     * Everything reachable from the protected accounts is kept:
     *  - the accounts themselves
     *  - requests they created, are matched on, offered on, or subscribed to
     *  - offers on those requests, and offers they made
     *  - documents they uploaded or attached to protected offers/requests
     *  - opportunities they published (and clicks on them)
     *  - any other user referenced by one of the protected entities, so that
     *    foreign keys on protected rows are never broken.
     *
     * @return array{users: array<int>, emails: array<string>, requests: array<int>, offers: array<int>, documents: array<int>, opportunities: array<int>}
     */
    private function resolveProtectedEntities(): array
    {
        $userIds = DB::table('users')
            ->whereIn('email', self::PROTECTED_EMAILS)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // request_offers.matched_partner_id is a string column.
        $userIdsAsStrings = array_map('strval', $userIds);

        $requestIds = DB::table('requests')
            ->whereIn('user_id', $userIds)
            ->orWhereIn('matched_partner_id', $userIds)
            ->pluck('id')
            ->merge(DB::table('request_offers')->whereIn('matched_partner_id', $userIdsAsStrings)->pluck('request_id'))
            ->merge(DB::table('request_subscriptions')->whereIn('user_id', $userIds)->pluck('request_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $offerIds = DB::table('request_offers')
            ->whereIn('request_id', $requestIds)
            ->orWhereIn('matched_partner_id', $userIdsAsStrings)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $opportunityIds = DB::table('opportunities')
            ->whereIn('user_id', $userIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $offerMorph = (new Offer)->getMorphClass();
        $requestMorph = (new OCDRequest)->getMorphClass();

        $documentIds = DB::table('documents')
            ->whereIn('uploader_id', $userIds)
            ->orWhere(function ($query) use ($offerIds, $offerMorph): void {
                $query->whereIn('parent_type', [$offerMorph, Offer::class])->whereIn('parent_id', $offerIds);
            })
            ->orWhere(function ($query) use ($requestIds, $requestMorph): void {
                $query->whereIn('parent_type', [$requestMorph, OCDRequest::class])->whereIn('parent_id', $requestIds);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Users referenced by protected rows must survive too (FK integrity).
        $keepUserIds = collect($userIds)
            ->merge(DB::table('requests')->whereIn('id', $requestIds)->pluck('user_id'))
            ->merge(DB::table('requests')->whereIn('id', $requestIds)->whereNotNull('matched_partner_id')->pluck('matched_partner_id'))
            ->merge(DB::table('request_offers')->whereIn('id', $offerIds)->pluck('matched_partner_id'))
            ->merge(DB::table('documents')->whereIn('id', $documentIds)->pluck('uploader_id'))
            ->merge(DB::table('request_subscriptions')->whereIn('request_id', $requestIds)->pluck('user_id'))
            ->merge(DB::table('request_subscriptions')->whereIn('request_id', $requestIds)->whereNotNull('admin_user_id')->pluck('admin_user_id'))
            ->merge(DB::table('opportunity_clicks')->whereIn('opportunity_id', $opportunityIds)->whereNotNull('user_id')->pluck('user_id'))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $keepEmails = DB::table('users')->whereIn('id', $keepUserIds)->pluck('email')->all();

        return [
            'users' => $keepUserIds,
            'emails' => $keepEmails,
            'requests' => $requestIds,
            'offers' => $offerIds,
            'documents' => $documentIds,
            'opportunities' => $opportunityIds,
        ];
    }

    /**
     * @param  array{keep: array<string, int>, delete: array<string, int>}  $plan
     */
    private function reportPlan(array $plan): void
    {
        $found = DB::table('users')->whereIn('email', self::PROTECTED_EMAILS)->pluck('email')->all();
        $missing = array_diff(self::PROTECTED_EMAILS, $found);

        $this->info(sprintf('Environment: %s', app()->environment()));
        $this->info(sprintf('Protected accounts found: %s', $found === [] ? 'none' : implode(', ', $found)));
        if ($missing !== []) {
            $this->warn(sprintf('Protected accounts not present in this database: %s', implode(', ', $missing)));
        }

        $this->info(sprintf(
            'Keeping: %d users, %d requests, %d offers, %d documents, %d opportunities.',
            $plan['keep']['users'], $plan['keep']['requests'], $plan['keep']['offers'], $plan['keep']['documents'], $plan['keep']['opportunities']
        ));

        $this->warn(sprintf(
            'Deleting: %d users, %d requests, %d offers, %d documents, %d opportunities (plus their subscriptions, clicks, notifications, settings, invitations, email logs, OTPs, sessions).',
            $plan['delete']['users'], $plan['delete']['requests'], $plan['delete']['offers'], $plan['delete']['documents'], $plan['delete']['opportunities']
        ));
    }

    // ---------------------------------------------------------------------
    // Purge
    // ---------------------------------------------------------------------

    /**
     * Deletes in child-to-parent order so foreign keys are satisfied without
     * disabling constraint checks. Reference data (organizations,
     * ioc_platforms, settings, roles, request_statuses) is left untouched.
     *
     * @param  array{users: array<int>, emails: array<string>, requests: array<int>, offers: array<int>, documents: array<int>, opportunities: array<int>}  $protected
     */
    private function purge(array $protected): void
    {
        $users = $protected['users'];
        $userMorph = (new User)->getMorphClass();

        DB::table('opportunity_clicks')->whereNotIn('opportunity_id', $protected['opportunities'])->delete();
        DB::table('documents')->whereNotIn('id', $protected['documents'])->delete();
        DB::table('request_offers')->whereNotIn('id', $protected['offers'])->delete();
        DB::table('request_subscriptions')->whereNotIn('request_id', $protected['requests'])->delete();
        DB::table('request_details')->whereNotIn('request_id', $protected['requests'])->delete();
        DB::table('requests')->whereNotIn('id', $protected['requests'])->delete();
        DB::table('opportunities')->whereNotIn('id', $protected['opportunities'])->delete();

        DB::table('notifications')->whereNotIn('user_id', $users)->delete();
        DB::table('user_notification_settings')->whereNotIn('user_id', $users)->delete();
        DB::table('user_invitations')->whereNotIn('invited_by', $users)->delete();
        DB::table('email_logs')->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $users))->delete();

        DB::table('one_time_passwords')
            ->where('authenticatable_type', $userMorph)
            ->whereNotIn('authenticatable_id', $users)
            ->delete();

        DB::table('model_has_roles')->where('model_type', $userMorph)->whereNotIn('model_id', $users)->delete();
        DB::table('model_has_permissions')->where('model_type', $userMorph)->whereNotIn('model_id', $users)->delete();
        DB::table('sessions')->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $users))->delete();
        DB::table('password_reset_tokens')->whereNotIn('email', $protected['emails'])->delete();

        DB::table('users')->whereNotIn('id', $users)->delete();
    }

    // ---------------------------------------------------------------------
    // Reference data
    // ---------------------------------------------------------------------

    private function ensureReferenceData(): void
    {
        foreach (['administrator', 'partner', 'user'] as $role) {
            Role::findOrCreate($role);
        }

        foreach (self::REQUEST_STATUSES as $code => $label) {
            $status = RequestStatus::firstOrCreate(['status_code' => $code], ['status_label' => $label]);
            $this->statusIds[$code] = (int) $status->id;
        }
    }

    // ---------------------------------------------------------------------
    // Users
    // ---------------------------------------------------------------------

    private function createUsers(): void
    {
        $this->admins = $this->makeUsers('admin', self::ADMIN_COUNT, ['administrator', 'user']);
        $this->partners = $this->makeUsers('partner', self::PARTNER_COUNT, ['partner', 'user']);
        $this->users = $this->makeUsers('user', self::USER_COUNT, ['user']);

        $this->created['admins'] = $this->admins->count();
        $this->created['partners'] = $this->partners->count();
        $this->created['users'] = $this->users->count();

        $this->info(sprintf(
            'Created %d admins, %d partners, %d users (password: "%s").',
            $this->admins->count(),
            $this->partners->count(),
            $this->users->count(),
            self::FAKE_PASSWORD
        ));
    }

    /**
     * @param  array<string>  $roles
     * @return Collection<int, User>
     */
    private function makeUsers(string $prefix, int $count, array $roles): Collection
    {
        $password = Hash::make(self::FAKE_PASSWORD);
        $created = collect();

        for ($i = 1; $i <= $count; $i++) {
            $firstName = $this->faker->firstName();
            $lastName = $this->faker->lastName();
            $email = sprintf('%s%d@%s', $prefix, $i, self::FAKE_EMAIL_DOMAIN);

            if (in_array($email, self::PROTECTED_EMAILS, true)) {
                throw new RuntimeException("Refusing to generate protected email {$email}.");
            }

            $createdAt = $this->pastDate(180);

            $user = User::create([
                'name' => "{$firstName} {$lastName}",
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => $password,
                'country' => $this->faker->randomElement(Country::cases())->value,
                'city' => $this->faker->city(),
                'is_blocked' => $prefix === 'user' && $i % 10 === 0,
                'last_login_at' => $this->faker->boolean(80) ? $this->pastDate(30) : null,
            ]);
            $user->forceFill([
                'email_verified_at' => $createdAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();

            $user->syncRoles($roles);
            $created->push($user);
        }

        return $created;
    }

    // ---------------------------------------------------------------------
    // Opportunities
    // ---------------------------------------------------------------------

    private function createOpportunities(): void
    {
        $statusCycle = [
            OpportunityStatus::ACTIVE,
            OpportunityStatus::ACTIVE,
            OpportunityStatus::CLOSED,
            OpportunityStatus::PENDING_REVIEW,
        ];
        $coverageCycle = CoverageActivity::cases();
        $count = 0;

        foreach (OpportunityType::cases() as $typeIndex => $type) {
            for ($i = 0; $i < self::OPPORTUNITIES_PER_TYPE; $i++) {
                $status = $statusCycle[$i % count($statusCycle)];
                // Sprinkle a few rejected ones across types.
                if ($i === 3 && $typeIndex % 3 === 0) {
                    $status = OpportunityStatus::REJECTED;
                }

                $coverage = $coverageCycle[($typeIndex + $i) % count($coverageCycle)];
                $createdAt = $this->pastDate(120);
                $closingDate = $status === OpportunityStatus::CLOSED
                    ? $this->faker->dateTimeBetween('-60 days', '-1 day')
                    : $this->faker->dateTimeBetween('+1 week', '+6 months');

                $thematicAreas = $this->pickValues(ThematicAreas::cases(), 1, 3);
                $audiences = $this->pickValues(OpportunityTargetAudience::cases(), 1, 3);
                $languages = $this->pickValues(Language::cases(), 1, 2);

                $opportunity = new Opportunity([
                    // Set explicitly: the `creating` hook that normally fills it is disabled with model events.
                    'public_id' => (string) Str::ulid(),
                    'title' => $this->opportunityTitle($type),
                    'type' => $type,
                    'closing_date' => $closingDate,
                    'coverage_activity' => $coverage, // must precede implementation_location (cast depends on it)
                    'implementation_location' => $this->implementationLocation($coverage),
                    'thematic_areas' => $thematicAreas,
                    'thematic_areas_other' => in_array(ThematicAreas::OTHER->value, $thematicAreas, true) ? $this->faker->words(3, true) : null,
                    'target_audience' => $audiences,
                    'target_audience_other' => in_array(OpportunityTargetAudience::OTHER->value, $audiences, true) ? $this->faker->jobTitle() : null,
                    'target_languages' => $languages,
                    'target_languages_other' => in_array(Language::OTHER->value, $languages, true) ? $this->faker->word() : null,
                    'summary' => $this->faker->paragraphs(2, true),
                    'url' => $this->faker->url(),
                    'key_words' => $this->faker->words($this->faker->numberBetween(2, 5)),
                    'co_organizers' => $this->faker->randomElements([
                        'IOC-UNESCO', 'GOOS', 'POGO', 'Ocean Teacher Global Academy', 'IODE', 'Mercator Ocean', 'Ocean Networks Canada', 'CSIRO', 'NOAA', 'Ifremer',
                    ], $this->faker->numberBetween(1, 3)),
                    'user_id' => $this->partners->random()->id,
                    'status' => $status,
                ]);

                if ($status === OpportunityStatus::CLOSED) {
                    $opportunity->closed_at = CarbonImmutable::instance($closingDate)->addDay();
                    $opportunity->closed_reason = 'Closing date reached';
                    $opportunity->previous_status = (string) OpportunityStatus::ACTIVE->value;
                }

                $opportunity->created_at = $createdAt;
                $opportunity->updated_at = $createdAt;
                $opportunity->save();
                $count++;
            }
        }

        $this->created['opportunities'] = $count;
        $this->info(sprintf('Created %d opportunities across %d types.', $count, count(OpportunityType::cases())));
    }

    private function opportunityTitle(OpportunityType $type): string
    {
        $topic = $this->faker->randomElement([
            'Ocean Acidification Monitoring', 'Deep-Sea Biodiversity', 'Coastal Resilience', 'Marine Spatial Planning',
            'eDNA Sampling', 'Blue Carbon Ecosystems', 'Ocean Data Management', 'Tsunami Early Warning',
            'Coral Reef Restoration', 'Polar Oceanography', 'Marine Pollution Assessment', 'Ocean Literacy',
        ]);
        $region = $this->faker->randomElement(['Pacific', 'Atlantic', 'Indian Ocean', 'Mediterranean', 'Arctic', 'Caribbean', 'West Africa', 'Southeast Asia']);

        return match ($type) {
            OpportunityType::TRAINING => "{$topic} Training Workshop ({$region})",
            OpportunityType::ONBOARDING_EXPEDITIONS => "Research Cruise Berths: {$topic} Expedition",
            OpportunityType::FELLOWSHIPS => "{$region} Early-Career Fellowship in {$topic}",
            OpportunityType::INTERNSHIPS_JOBS_CONSULTANCIES => "Consultancy: {$topic} Specialist",
            OpportunityType::MENTORSHIPS => "Mentorship Programme on {$topic}",
            OpportunityType::VISITING_LECTURERS => "Visiting Lecturer Call: {$topic}",
            OpportunityType::TRAVEL_GRANTS => "Travel Grants for {$region} Scientists: {$topic}",
            OpportunityType::AWARDS => "{$topic} Excellence Award",
            OpportunityType::RESEARCH_FUNDING => "Research Funding Call: {$topic}",
            OpportunityType::ACCESS_INFRASTRUCTURE => "Access to Research Infrastructure for {$topic}",
            OpportunityType::OCEAN_DATA => "Open Ocean Data Release: {$topic}",
            OpportunityType::NETWORKS_COMMUNITY => "{$region} Community of Practice on {$topic}",
            OpportunityType::OCEAN_LITERACY => "{$topic} Ocean Literacy Campaign",
            OpportunityType::WEBINAR => "Webinar Series: {$topic}",
            OpportunityType::ACCESS_EQUIPMENT => "Equipment Loan Scheme: {$topic}",
            OpportunityType::CONFERENCE_FORUMS => "{$region} Forum on {$topic}",
            OpportunityType::ODC_TRAVEL_SUPPORT => "2027 ODC Travel Support: {$topic} ({$region})",
            OpportunityType::ONLINE_COURSES => "Online Course: {$topic}",
            OpportunityType::TECHNICAL_ASSISTANCE => "Technical Assistance on {$topic} ({$region})",
            default => sprintf('%s: %s (%s)', $type->label(), $topic, $region),
        };
    }

    /**
     * @return array<string>|string
     */
    private function implementationLocation(CoverageActivity $coverage): array|string
    {
        return match ($coverage) {
            CoverageActivity::GLOBAL => 'Global',
            CoverageActivity::REGIONS => $this->pickValues(Region::cases(), 1, 2),
            CoverageActivity::COUNTRY => $this->pickValues(Country::cases(), 1, 3),
            CoverageActivity::OCEANBASED => $this->pickValues(Ocean::cases(), 1, 2),
        };
    }

    // ---------------------------------------------------------------------
    // Requests
    // ---------------------------------------------------------------------

    private function createRequests(): void
    {
        $activities = RelatedActivity::cases();
        $formats = DeliveryFormat::cases();
        $requestCount = 0;
        $offerCount = 0;
        $subscriptionCount = 0;
        $counter = 0;

        foreach (array_keys(self::REQUEST_STATUSES) as $code) {
            for ($i = 0; $i < self::REQUESTS_PER_STATUS; $i++, $counter++) {
                $owner = $this->users->random();
                $createdAt = $this->pastDate(150);
                $isDraft = $code === 'draft';
                $hasPartner = in_array($code, ['offer_made', 'in_implementation', 'closed'], true);
                $partner = $hasPartner ? $this->partners->random() : null;

                $request = new OCDRequest([
                    'user_id' => $owner->id,
                    'status_id' => $this->statusIds[$code],
                    'matched_partner_id' => $partner?->id,
                ]);
                $request->created_at = $createdAt;
                $request->updated_at = $createdAt;
                $request->save();

                $detail = new Detail($isDraft
                    ? $this->draftDetail($owner)
                    : $this->fullDetail($owner, $activities[$counter % count($activities)], $formats[$counter % count($formats)]));
                $detail->request_id = $request->id;
                $detail->created_at = $createdAt;
                $detail->updated_at = $createdAt;
                $detail->save();
                $requestCount++;

                // Offers: pending (inactive) offers on validated requests, an active one once matched.
                if ($code === 'validated') {
                    $pending = $this->faker->numberBetween(0, 2);
                    foreach ($this->partners->random($pending) as $offeringPartner) {
                        $this->makeOffer($request, $offeringPartner, RequestOfferStatus::INACTIVE, false, $createdAt);
                        $offerCount++;
                    }
                }

                if ($partner !== null) {
                    $accepted = $code !== 'offer_made';
                    $this->makeOffer($request, $partner, RequestOfferStatus::ACTIVE, $accepted, $createdAt);
                    $offerCount++;
                }

                // Subscriptions on publicly visible requests.
                if (in_array($code, ['validated', 'offer_made', 'in_implementation'], true)) {
                    $subscribers = $this->users
                        ->where('id', '!=', $owner->id)
                        ->random($this->faker->numberBetween(0, 3));

                    foreach ($subscribers as $subscriber) {
                        $byAdmin = $this->faker->boolean(25);
                        $subscribedAt = $createdAt->addDays($this->faker->numberBetween(1, 10));
                        DB::table('request_subscriptions')->insert([
                            'user_id' => $subscriber->id,
                            'request_id' => $request->id,
                            'subscribed_by_admin' => $byAdmin,
                            'admin_user_id' => $byAdmin ? $this->admins->random()->id : null,
                            'created_at' => $subscribedAt,
                            'updated_at' => $subscribedAt,
                        ]);
                        $subscriptionCount++;
                    }
                }
            }
        }

        $this->created['requests'] = $requestCount;
        $this->created['offers'] = $offerCount;
        $this->created['subscriptions'] = $subscriptionCount;

        $this->info(sprintf(
            'Created %d requests across %d statuses, %d offers, %d subscriptions.',
            $requestCount,
            count(self::REQUEST_STATUSES),
            $offerCount,
            $subscriptionCount
        ));
    }

    private function makeOffer(OCDRequest $request, User $partner, RequestOfferStatus $status, bool $accepted, CarbonImmutable $requestCreatedAt): void
    {
        $offer = new Offer([
            'request_id' => $request->id,
            'matched_partner_id' => (string) $partner->id,
            'description' => $this->faker->paragraphs(2, true),
            'status' => $status,
            'is_accepted' => $accepted,
        ]);
        $offeredAt = $requestCreatedAt->addDays($this->faker->numberBetween(3, 30));
        $offer->created_at = $offeredAt;
        $offer->updated_at = $offeredAt;
        $offer->save();
    }

    /**
     * Partial payload, as the "save as draft" form allows.
     *
     * @return array<string, mixed>
     */
    private function draftDetail(User $owner): array
    {
        return [
            'capacity_development_title' => $this->requestTitle(),
            'first_name' => $owner->first_name,
            'last_name' => $owner->last_name,
            'email' => $owner->email,
            'is_related_decade_action' => YesNo::NO->value,
            'request_link_type' => YesNo::NO->value,
            'related_activity' => $this->faker->boolean() ? RelatedActivity::TRAINING->value : null,
            'decade_challenges' => $this->faker->boolean()
                ? ['primary' => $this->faker->randomElement(DecadeChallenge::cases())->value, 'secondary' => null, 'tertiary' => null]
                : null,
        ];
    }

    /**
     * Complete payload, mirroring the fields StoreRequest requires in submit mode.
     *
     * @return array<string, mixed>
     */
    private function fullDetail(User $owner, RelatedActivity $activity, DeliveryFormat $format): array
    {
        $relatedToDecadeAction = $this->faker->boolean(40);
        $hasLink = ! $relatedToDecadeAction && $this->faker->boolean(50);
        $hasPartner = $this->faker->boolean(40);
        $needsFunding = $this->faker->boolean(70);

        $supportTypes = $this->pickValues(SupportType::cases(), 1, 2);
        $audiences = $this->pickValues(RequestTargetAudience::cases(), 1, 3);
        $languages = $this->pickValues(Language::cases(), 1, 2);

        return [
            'capacity_development_title' => $this->requestTitle(),
            'is_related_decade_action' => YesNo::fromBool($relatedToDecadeAction)->value,
            'unique_related_decade_action_id' => $relatedToDecadeAction ? sprintf('%02d.%03d', $this->faker->numberBetween(1, 60), $this->faker->numberBetween(1, 999)) : null,
            'first_name' => $owner->first_name,
            'last_name' => $owner->last_name,
            'email' => $owner->email,
            'request_link_type' => $relatedToDecadeAction ? null : YesNo::fromBool($hasLink)->value,
            'project_stage' => $hasLink ? $this->faker->randomElement(ProjectStage::cases())->value : null,
            'project_url' => (! $relatedToDecadeAction && ! $hasLink) ? $this->faker->url() : null,
            'related_activity' => $activity->value,
            'delivery_format' => $format->value,
            'delivery_countries' => $format === DeliveryFormat::ONLINE ? null : $this->pickValues(Country::cases(), 1, 3),
            'decade_challenges' => $this->decadeChallenges(),
            'support_types' => $supportTypes,
            'support_types_other' => in_array(SupportType::OTHER->value, $supportTypes, true) ? $this->faker->sentence(4) : null,
            'target_audience' => $audiences,
            'target_audience_other' => in_array(RequestTargetAudience::OTHER->value, $audiences, true) ? $this->faker->jobTitle() : null,
            'target_languages' => $languages,
            'target_languages_other' => in_array(Language::OTHER->value, $languages, true) ? $this->faker->word() : null,
            'gap_description' => $this->faker->paragraph(4),
            'has_partner' => YesNo::fromBool($hasPartner)->value,
            'partner_name' => $hasPartner ? $this->faker->company() : null,
            'partner_confirmed' => $hasPartner ? $this->faker->randomElement(YesNo::cases())->value : null,
            'needs_financial_support' => YesNo::fromBool($needsFunding)->value,
            'budget_breakdown' => $needsFunding ? $this->budgetBreakdown() : null,
            'support_months' => $this->faker->numberBetween(3, 24),
            'completion_date' => $this->faker->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d'),
            'risks' => $this->faker->paragraph(2),
            'personnel_expertise' => $this->faker->paragraph(2),
            'direct_beneficiaries' => $this->faker->sentence(8),
            'direct_beneficiaries_number' => $this->faker->numberBetween(10, 500),
            'expected_outcomes' => $this->faker->paragraph(3),
            'success_metrics' => $this->faker->paragraph(2),
            'long_term_impact' => $this->faker->paragraph(3),
        ];
    }

    /**
     * Ranked Decade Challenges: primary always set, secondary/tertiary
     * optional and distinct (same shape StoreRequest validates).
     *
     * @return array{primary: string, secondary: string|null, tertiary: string|null}
     */
    private function decadeChallenges(): array
    {
        $ranked = $this->pickValues(DecadeChallenge::cases(), 1, 3);

        return [
            'primary' => $ranked[0],
            'secondary' => $ranked[1] ?? null,
            'tertiary' => $ranked[2] ?? null,
        ];
    }

    private function requestTitle(): string
    {
        $verb = $this->faker->randomElement(['Building', 'Strengthening', 'Developing', 'Scaling', 'Launching']);
        $topic = $this->faker->randomElement([
            'ocean acidification monitoring capacity', 'eDNA survey skills', 'marine spatial planning expertise',
            'coastal hazard early warning systems', 'FAIR ocean data stewardship', 'ecosystem-based fisheries management',
            'blue carbon assessment methods', 'science-policy communication', 'deep-sea observation techniques',
            'community-led coral restoration', 'ocean literacy curricula', 'BBNJ implementation know-how',
        ]);
        $where = $this->faker->randomElement(['in Small Island Developing States', 'in West Africa', 'across the Pacific', 'in the Caribbean', 'in Southeast Asia', 'in the Western Indian Ocean', 'in Latin America']);

        return ucfirst("{$verb} {$topic} {$where}");
    }

    private function budgetBreakdown(): string
    {
        return implode("\n", [
            'Trainer fees: USD '.number_format($this->faker->numberBetween(5, 30) * 1000),
            'Travel and accommodation: USD '.number_format($this->faker->numberBetween(5, 40) * 1000),
            'Venue and materials: USD '.number_format($this->faker->numberBetween(2, 15) * 1000),
            'Equipment: USD '.number_format($this->faker->numberBetween(0, 20) * 1000),
        ]);
    }

    // ---------------------------------------------------------------------
    // Notification settings & in-app notifications
    // ---------------------------------------------------------------------

    /**
     * One opt-out row per user: `opportunity` / `request` hold the types and
     * challenges the user does NOT want to hear about (see User::enabledOpportunityTypes()).
     */
    private function createNotificationSettings(): void
    {
        $count = 0;

        foreach ($this->users->merge($this->partners) as $user) {
            // Users without a row keep the defaults (everything enabled).
            if (! $this->faker->boolean(60)) {
                continue;
            }

            UserNotificationSetting::create([
                'user_id' => $user->id,
                'email_notifications_enabled' => $this->faker->boolean(75),
                'opportunity' => $this->faker->boolean(70) ? $this->pickValues(OpportunityType::cases(), 1, 5) : [],
                'request' => $this->faker->boolean(50) ? $this->pickValues(DecadeChallenge::cases(), 1, 4) : [],
            ]);
            $count++;
        }

        $this->created['notification_settings'] = $count;
        $this->info(sprintf('Created %d notification settings.', $count));
    }

    private function createSystemNotifications(): void
    {
        $templates = [
            ['Request validated', 'Your capacity development request has been validated and is now visible to partners.'],
            ['New offer received', 'A partner has made an offer on one of your requests.'],
            ['Opportunity published', 'Your opportunity is now live on the portal.'],
            ['Opportunity closing soon', 'One of the opportunities you follow closes in 7 days.'],
            ['Weekly digest', 'New opportunities matching your preferences were published this week.'],
        ];
        $count = 0;

        foreach ($this->users->merge($this->partners) as $user) {
            $n = $this->faker->numberBetween(0, 3);
            for ($i = 0; $i < $n; $i++) {
                [$title, $description] = $this->faker->randomElement($templates);
                $notification = new SystemNotification([
                    'user_id' => $user->id,
                    'title' => $title,
                    'description' => $description,
                    'is_read' => $this->faker->boolean(50),
                ]);
                $at = $this->pastDate(60);
                $notification->created_at = $at;
                $notification->updated_at = $at;
                $notification->save();
                $count++;
            }
        }

        $this->created['notifications'] = $count;
        $this->info(sprintf('Created %d in-app notifications.', $count));
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Pick between $min and $max distinct enum values from a list of cases.
     *
     * @param  array<\BackedEnum>  $cases
     * @return array<string>
     */
    private function pickValues(array $cases, int $min, int $max): array
    {
        $picked = $this->faker->randomElements($cases, $this->faker->numberBetween($min, min($max, count($cases))));

        return array_values(array_map(fn (\BackedEnum $case) => (string) $case->value, $picked));
    }

    private function pastDate(int $maxDaysAgo): CarbonImmutable
    {
        return CarbonImmutable::instance($this->faker->dateTimeBetween("-{$maxDaysAgo} days", 'now'));
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function reportSummary(array $summary): void
    {
        $this->info('');
        $this->info(sprintf('Sample dataset ready in %ss.', $summary['duration_seconds']));
        foreach ($summary['created'] as $entity => $count) {
            $this->info(sprintf('  created %-24s %d', $entity, $count));
        }
        $this->info(sprintf(
            '  fake logins: %s @%s / "%s"',
            implode(', ', $summary['credentials']['logins']),
            $summary['credentials']['domain'],
            $summary['credentials']['password']
        ));
    }

    private function info(string $message): void
    {
        $this->command?->info($message);
    }

    private function warn(string $message): void
    {
        $this->command?->warn($message);
    }
}
