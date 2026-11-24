<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Seeders;

use App\Domains\Request\Models\Detail;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class SampleDataSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_allowed_in_local_staging_and_testing_only(): void
    {
        foreach (['local', 'staging', 'testing'] as $env) {
            app()->detectEnvironment(fn () => $env);
            $this->assertTrue(SampleDataSeeder::isAllowedEnvironment(), "{$env} should be allowed");
        }

        foreach (['production', 'prod', 'preprod'] as $env) {
            app()->detectEnvironment(fn () => $env);
            $this->assertFalse(SampleDataSeeder::isAllowedEnvironment(), "{$env} should be refused");
        }
    }

    #[Test]
    public function execute_refuses_to_run_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('production');

        app(SampleDataSeeder::class)->execute();
    }

    #[Test]
    public function plan_reports_keep_and_delete_counts_without_changing_anything(): void
    {
        $plan = app(SampleDataSeeder::class)->plan();

        $this->assertSame(['users', 'requests', 'offers', 'opportunities', 'documents'], array_keys($plan['keep']));
        $this->assertSame(['users', 'requests', 'offers', 'opportunities', 'documents'], array_keys($plan['delete']));
        $this->assertSame(['n.kim1@unesco.org', 'labidi.saddem@gmail.com'], $plan['protected_emails']);
        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function execute_returns_a_structured_summary(): void
    {
        $summary = app(SampleDataSeeder::class)->execute();

        $this->assertSame(
            ['environment', 'protected_emails', 'protected', 'deleted', 'created', 'credentials', 'duration_seconds'],
            array_keys($summary)
        );
        $this->assertSame(
            ['admins', 'partners', 'users', 'opportunities', 'requests', 'offers', 'subscriptions', 'notification_settings', 'notifications'],
            array_keys($summary['created'])
        );
        $this->assertSame('sample.oceandecade.test', $summary['credentials']['domain']);
        $this->assertDatabaseCount('request_statuses', 8);
        $this->assertDatabaseCount('roles', 3);

        // Submitted requests carry a ranked primary Decade Challenge; morph columns use the aliases.
        $submitted = Detail::whereNotNull('gap_description')->first();
        $this->assertNotNull($submitted);
        $this->assertStringStartsWith('challenge-', $submitted->decade_challenges['primary']);
        $this->assertDatabaseHas('model_has_roles', ['model_type' => 'user']);
    }
}
