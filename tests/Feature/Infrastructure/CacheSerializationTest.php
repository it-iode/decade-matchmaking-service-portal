<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Domains\Notification\Services\NewsletterService;
use App\Infrastructure\Email\Services\TemplateResolver;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the "no PHP objects in cache" invariant.
 *
 * The cache 'serializable_classes' option is disabled (Laravel 13 default),
 * so every value we cache must survive a real serialize/unserialize round
 * trip through a persistent store. The default unit-test store is 'array',
 * which never serializes — these tests force the database store to exercise
 * the real path.
 */
class CacheSerializationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'database']);
    }

    #[Test]
    public function email_template_resolution_round_trips_through_a_serializing_cache_store(): void
    {
        $resolver = app(TemplateResolver::class);

        $eventName = array_key_first(config('mail-templates.templates'));
        $this->assertNotNull($eventName, 'No mail templates configured');

        $first = $resolver->resolve($eventName);
        // Second call is served from the cached config array.
        $second = $resolver->resolve($eventName);

        $this->assertEquals($first, $second);

        // The cached value itself must not be an object.
        $cached = Cache::get('email_template:'.$eventName);
        $this->assertIsArray($cached);
    }

    #[Test]
    public function newsletter_last_send_date_round_trips_through_a_serializing_cache_store(): void
    {
        Cache::put('newsletter:last_send_date', now()->toIso8601String(), 60);

        $this->assertIsString(Cache::get('newsletter:last_send_date'));

        $parsed = NewsletterService::parseLastSendDate(Cache::get('newsletter:last_send_date'));

        $this->assertInstanceOf(CarbonInterface::class, $parsed);
        $this->assertTrue($parsed->isSameDay(now()));
    }

    #[Test]
    public function newsletter_last_send_date_tolerates_a_legacy_cached_carbon_object(): void
    {
        // Entries written before the ISO-string change may still hold a
        // Carbon object until their 14-day TTL expires.
        $legacy = now()->subDays(3);

        $parsed = NewsletterService::parseLastSendDate($legacy);

        $this->assertSame($legacy, $parsed);
        $this->assertNull(NewsletterService::parseLastSendDate(null));
        $this->assertNull(NewsletterService::parseLastSendDate('not-a-date'));
    }
}
