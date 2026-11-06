<?php

declare(strict_types=1);

namespace App\Shared\Support;

use App\Domains\User\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Support\Facades\Log;

/**
 * Thin application-side wrapper around the SampleDataSeeder so the admin UI
 * can preview and run it. All environment guards live in the seeder.
 *
 * Lives in Shared because the seeder spans every domain and has no natural
 * owner; User is the tolerated Domains import.
 */
class SampleDataService
{
    public function isAvailable(): bool
    {
        return SampleDataSeeder::isAllowedEnvironment();
    }

    /**
     * Keep/delete counts without touching the database.
     *
     * @return array{keep: array<string, int>, delete: array<string, int>, protected_emails: array<string>}
     */
    public function preview(): array
    {
        $plan = $this->seeder()->plan();

        return [
            'keep' => $plan['keep'],
            'delete' => $plan['delete'],
            'protected_emails' => $plan['protected_emails'],
        ];
    }

    /**
     * Purge and regenerate. Caller is responsible for confirmation.
     *
     * @return array<string, mixed> summary (see SampleDataSeeder::execute)
     */
    public function run(User $actor): array
    {
        Log::info('Sample data generation started', ['user_id' => $actor->id, 'environment' => app()->environment()]);

        $summary = $this->seeder()->execute();

        Log::info('Sample data generation finished', [
            'user_id' => $actor->id,
            'duration_seconds' => $summary['duration_seconds'],
            'created' => $summary['created'],
            'deleted' => $summary['deleted'],
        ]);

        return $summary;
    }

    private function seeder(): SampleDataSeeder
    {
        return app(SampleDataSeeder::class);
    }
}
