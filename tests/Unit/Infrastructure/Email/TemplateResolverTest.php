<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Email;

use App\Infrastructure\Email\Exceptions\TemplateNotFoundException;
use App\Infrastructure\Email\Services\EmailTemplate;
use App\Infrastructure\Email\Services\TemplateResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TemplateResolverTest extends TestCase
{
    private TemplateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new TemplateResolver;

        // Set up test configuration
        Config::set('mail-templates.templates', [
            'user.registered' => [
                'template_name' => 'ocd-welcome',
                'subject' => 'Welcome',
                'variables' => [
                    'user_name' => 'required|string',
                ],
                'tags' => ['user', 'registration'],
            ],
            'request.submitted' => [
                'template_name' => 'ocd-request-submitted',
                'subject' => 'Request Submitted',
                'variables' => [
                    'request_title' => 'required|string',
                ],
                'tags' => ['request', 'submission'],
            ],
            'offer.received' => [
                'template_name' => 'ocd-offer-received',
                'subject' => 'Offer Received',
                'variables' => [
                    'offer_summary' => 'required|string',
                ],
                'tags' => ['offer', 'notification'],
            ],
        ]);
    }

    /**
     * Test resolving existing template
     */
    public function test_resolves_existing_template(): void
    {
        // Act
        $template = $this->resolver->resolve('user.registered');

        // Assert
        $this->assertInstanceOf(EmailTemplate::class, $template);
        $this->assertEquals('user.registered', $template->getEventName());
        $this->assertEquals('ocd-welcome', $template->getRawTemplateName());
        $this->assertEquals('Welcome', $template->getSubject());
        $this->assertArrayHasKey('user_name', $template->getVariables());
    }

    /**
     * Test throwing exception for non-existent template
     */
    public function test_throws_exception_for_non_existent_template(): void
    {
        // Assert
        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Email template configuration not found for event: non.existent');

        // Act
        $this->resolver->resolve('non.existent');
    }

    /**
     * Test checking if template exists
     */
    public function test_checks_if_template_exists(): void
    {
        // Act & Assert
        $this->assertTrue($this->resolver->exists('user.registered'));
        $this->assertTrue($this->resolver->exists('request.submitted'));
        $this->assertFalse($this->resolver->exists('non.existent'));
    }

    /**
     * Test getting all templates
     */
    public function test_gets_all_templates(): void
    {
        // Act
        $templates = $this->resolver->getAllTemplates();

        // Assert
        $this->assertCount(3, $templates);
        $this->assertArrayHasKey('user.registered', $templates);
        $this->assertArrayHasKey('request.submitted', $templates);
        $this->assertArrayHasKey('offer.received', $templates);
        $this->assertInstanceOf(EmailTemplate::class, $templates['user.registered']);
    }

    /**
     * Test getting templates by tag
     */
    public function test_gets_templates_by_tag(): void
    {
        // Act
        $userTemplates = $this->resolver->getTemplatesByTag('user');
        $requestTemplates = $this->resolver->getTemplatesByTag('request');
        $notificationTemplates = $this->resolver->getTemplatesByTag('notification');

        // Assert
        $this->assertCount(1, $userTemplates);
        $this->assertArrayHasKey('user.registered', $userTemplates);

        $this->assertCount(1, $requestTemplates);
        $this->assertArrayHasKey('request.submitted', $requestTemplates);

        $this->assertCount(1, $notificationTemplates);
        $this->assertArrayHasKey('offer.received', $notificationTemplates);
    }

    /**
     * Test template caching
     */
    public function test_caches_resolved_templates(): void
    {
        // Clear cache first
        Cache::flush();

        // Act - First call should cache
        $template1 = $this->resolver->resolve('user.registered');

        // Modify config (this shouldn't affect cached result)
        Config::set('mail-templates.templates.user.registered.subject', 'Modified Subject');

        // Second call should use cache
        $template2 = $this->resolver->resolve('user.registered');

        // Assert - Both should be the same (from cache)
        $this->assertEquals($template1->getSubject(), $template2->getSubject());
        $this->assertEquals('Welcome', $template2->getSubject()); // Original value, not modified
    }

    /**
     * Test clearing cache
     */
    public function test_clears_cache(): void
    {
        // Arrange - Resolve and cache a template
        $template1 = $this->resolver->resolve('user.registered');

        // Modify config
        Config::set('mail-templates.templates.user.registered.subject', 'Modified Subject');

        // Act - Clear cache
        $this->resolver->clearCache('user.registered');

        // Resolve again (should not use cache)
        $template2 = $this->resolver->resolve('user.registered');

        // Assert - Should have new value
        $this->assertEquals('Modified Subject', $template2->getSubject());
    }

    /**
     * Test clearing all cache
     */
    public function test_clears_all_cache(): void
    {
        // Arrange - Resolve multiple templates to cache them
        $this->resolver->resolve('user.registered');
        $this->resolver->resolve('request.submitted');

        // Act
        $this->resolver->clearAllCache();

        // Modify config
        Config::set('mail-templates.templates.user.registered.subject', 'New Welcome');
        Config::set('mail-templates.templates.request.submitted.subject', 'New Request');

        // Resolve again
        $userTemplate = $this->resolver->resolve('user.registered');
        $requestTemplate = $this->resolver->resolve('request.submitted');

        // Assert - Should have new values
        $this->assertEquals('New Welcome', $userTemplate->getSubject());
        $this->assertEquals('New Request', $requestTemplate->getSubject());
    }

    /**
     * Test getting templates grouped by category
     */
    public function test_gets_templates_grouped_by_category(): void
    {
        // Act
        $grouped = $this->resolver->getTemplatesGroupedByCategory();

        // Assert
        $this->assertArrayHasKey('user', $grouped);
        $this->assertArrayHasKey('request', $grouped);
        $this->assertArrayHasKey('offer', $grouped);

        $this->assertCount(1, $grouped['user']);
        $this->assertCount(1, $grouped['request']);
        $this->assertCount(1, $grouped['offer']);

        $this->assertArrayHasKey('user.registered', $grouped['user']);
        $this->assertArrayHasKey('request.submitted', $grouped['request']);
        $this->assertArrayHasKey('offer.received', $grouped['offer']);
    }

    /**
     * Test validating template configuration
     */
    public function test_validates_template_configuration(): void
    {
        // Arrange
        $validConfig = [
            'template_name' => 'test-template',
            'variables' => ['test' => 'required'],
        ];

        $invalidConfig1 = [
            'variables' => ['test' => 'required'],
            // Missing template_name
        ];

        $invalidConfig2 = [
            'template_name' => 'test-template',
            // Missing variables
        ];

        // Act & Assert
        $this->assertTrue($this->resolver->validateTemplateConfig($validConfig));
        $this->assertFalse($this->resolver->validateTemplateConfig($invalidConfig1));
        $this->assertFalse($this->resolver->validateTemplateConfig($invalidConfig2));
    }
}
