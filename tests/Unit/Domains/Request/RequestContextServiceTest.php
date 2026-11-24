<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Request;

use App\Domains\Request\Services\RequestContextService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequestContextServiceTest extends TestCase
{
    #[Test]
    public function get_valid_contexts_returns_all_known_contexts(): void
    {
        $this->assertEqualsCanonicalizing(
            ['admin', 'user_own', 'public', 'matched', 'subscribed'],
            RequestContextService::getValidContexts()
        );
    }

    #[Test]
    public function validate_context_accepts_known_context(): void
    {
        RequestContextService::validateContext(RequestContextService::CONTEXT_ADMIN);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validate_context_throws_for_unknown_context(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RequestContextService::validateContext('unknown');
    }

    #[Test]
    public function get_detail_banner_config_returns_context_specific_description(): void
    {
        $service = new RequestContextService;

        $config = $service->getDetailBannerConfig(RequestContextService::CONTEXT_USER_OWN, 'My Title');

        $this->assertSame('My Title', $config['title']);
        $this->assertStringContainsString('your submitted request', $config['description']);
    }

    #[Test]
    public function get_detail_banner_config_falls_back_to_default_description(): void
    {
        $service = new RequestContextService;

        $config = $service->getDetailBannerConfig('unknown_context', 'My Title');

        $this->assertSame('View request details here.', $config['description']);
    }
}
