<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Email;

use App\Domains\User\Models\User;
use App\Infrastructure\Email\Exceptions\MissingVariableException;
use App\Infrastructure\Email\Exceptions\TemplateNotFoundException;
use App\Infrastructure\Email\Exceptions\ValidationException;
use App\Infrastructure\Email\Services\EmailLogger;
use App\Infrastructure\Email\Services\EmailTemplate;
use App\Infrastructure\Email\Services\EmailTemplateService;
use App\Infrastructure\Email\Services\MandrillClient;
use App\Infrastructure\Email\Services\TemplateResolver;
use App\Infrastructure\Email\Services\VariableValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmailTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailTemplateService $service;

    private $mockTemplateResolver;

    private $mockVariableValidator;

    private $mockMandrillClient;

    private $mockEmailLogger;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockTemplateResolver = Mockery::mock(TemplateResolver::class);
        $this->mockVariableValidator = Mockery::mock(VariableValidator::class);
        $this->mockMandrillClient = Mockery::mock(MandrillClient::class);
        $this->mockEmailLogger = Mockery::mock(EmailLogger::class);

        // Create service with mocks
        $this->service = new EmailTemplateService(
            $this->mockTemplateResolver,
            $this->mockVariableValidator,
            $this->mockMandrillClient,
            $this->mockEmailLogger
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test successful email send
     */
    public function test_sends_email_successfully(): void
    {
        // Arrange
        $eventName = 'user.registered';
        $user = $this->createTestUser();
        $variables = [
            'verification_url' => 'https://example.com/verify',
        ];

        $template = $this->createMockTemplate($eventName);

        $this->mockTemplateResolver
            ->shouldReceive('resolve')
            ->with($eventName)
            ->once()
            ->andReturn($template);

        $this->mockVariableValidator
            ->shouldReceive('validate')
            ->once()
            ->andReturn(array_merge($this->getDefaultVariables($user), $variables));

        $this->mockEmailLogger
            ->shouldReceive('logQueued')
            ->once()
            ->andReturn(123);

        $this->mockMandrillClient
            ->shouldReceive('sendTemplate')
            ->once()
            ->andReturn([
                'id' => 'mandrill-123',
                'status' => 'sent',
            ]);

        $this->mockEmailLogger
            ->shouldReceive('logSent')
            ->once();

        // Act
        $result = $this->service->send($eventName, $user, $variables);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('mandrill-123', $result->mandrillId);
        $this->assertEquals('sent', $result->status);
        $this->assertEquals(123, $result->logId);
    }

    /**
     * Test template not found exception
     */
    public function test_throws_exception_when_template_not_found(): void
    {
        // Arrange
        $eventName = 'non.existent';
        $user = $this->createTestUser();

        $this->mockTemplateResolver
            ->shouldReceive('resolve')
            ->with($eventName)
            ->once()
            ->andThrow(TemplateNotFoundException::forEvent($eventName));

        $this->mockEmailLogger
            ->shouldReceive('logFailed')
            ->once();

        // Assert
        $this->expectException(TemplateNotFoundException::class);

        // Act
        $this->service->send($eventName, $user, []);
    }

    /**
     * Test missing required variables
     */
    public function test_throws_exception_for_missing_variables(): void
    {
        // Arrange
        $eventName = 'user.registered';
        $user = $this->createTestUser();
        $template = $this->createMockTemplate($eventName);

        $this->mockTemplateResolver
            ->shouldReceive('resolve')
            ->with($eventName)
            ->once()
            ->andReturn($template);

        $this->mockVariableValidator
            ->shouldReceive('validate')
            ->once()
            ->andThrow(MissingVariableException::forVariables($eventName, ['verification_url']));

        $this->mockEmailLogger
            ->shouldReceive('logFailed')
            ->once();

        // Assert
        $this->expectException(MissingVariableException::class);

        // Act
        $this->service->send($eventName, $user, []);
    }

    /**
     * Test variable validation failure
     */
    public function test_throws_exception_for_invalid_variables(): void
    {
        // Arrange
        $eventName = 'user.registered';
        $user = $this->createTestUser();
        $template = $this->createMockTemplate($eventName);

        $this->mockTemplateResolver
            ->shouldReceive('resolve')
            ->with($eventName)
            ->once()
            ->andReturn($template);

        $this->mockVariableValidator
            ->shouldReceive('validate')
            ->once()
            ->andThrow(ValidationException::withErrors($eventName, [
                'verification_url' => ['The verification_url must be a valid URL.'],
            ]));

        $this->mockEmailLogger
            ->shouldReceive('logFailed')
            ->once();

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->service->send($eventName, $user, ['verification_url' => 'not-a-url']);
    }

    /**
     * Test template exists check
     */
    public function test_checks_if_template_exists(): void
    {
        // Arrange
        $this->mockTemplateResolver
            ->shouldReceive('exists')
            ->with('user.registered')
            ->once()
            ->andReturn(true);

        $this->mockTemplateResolver
            ->shouldReceive('exists')
            ->with('non.existent')
            ->once()
            ->andReturn(false);

        // Act & Assert
        $this->assertTrue($this->service->templateExists('user.registered'));
        $this->assertFalse($this->service->templateExists('non.existent'));
    }

    /**
     * Test preview functionality
     */
    public function test_previews_email_template(): void
    {
        // Arrange
        $eventName = 'user.registered';
        $variables = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'verification_url' => 'https://example.com/verify',
        ];

        $template = $this->createMockTemplate($eventName);

        $this->mockTemplateResolver
            ->shouldReceive('resolve')
            ->with($eventName)
            ->once()
            ->andReturn($template);

        $this->mockVariableValidator
            ->shouldReceive('validate')
            ->once()
            ->andReturn($variables);

        // Act
        $preview = $this->service->preview($eventName, $variables);

        // Assert
        $this->assertEquals($eventName, $preview['event_name']);
        $this->assertEquals('ocd-welcome', $preview['template_name']);
        $this->assertEquals('Welcome to Ocean Decade Portal', $preview['subject']);
        $this->assertArrayHasKey('variables', $preview);
        $this->assertArrayHasKey('tags', $preview);
        $this->assertArrayHasKey('required_variables', $preview);
        $this->assertArrayHasKey('optional_variables', $preview);
    }

    /**
     * Test sending to multiple recipients
     */
    public function test_sends_to_multiple_recipients(): void
    {
        // Arrange
        $eventName = 'user.registered';
        $users = [
            $this->createTestUser('user1@example.com'),
            $this->createTestUser('user2@example.com'),
        ];
        $variables = ['verification_url' => 'https://example.com/verify'];

        $template = $this->createMockTemplate($eventName);

        $this->mockTemplateResolver
            ->shouldReceive('resolve')
            ->times(2)
            ->andReturn($template);

        $this->mockVariableValidator
            ->shouldReceive('validate')
            ->times(2)
            ->andReturn($variables);

        $this->mockEmailLogger
            ->shouldReceive('logQueued')
            ->times(2)
            ->andReturn(1, 2);

        $this->mockMandrillClient
            ->shouldReceive('sendTemplate')
            ->times(2)
            ->andReturn(
                ['id' => 'mandrill-1', 'status' => 'sent'],
                ['id' => 'mandrill-2', 'status' => 'sent']
            );

        $this->mockEmailLogger
            ->shouldReceive('logSent')
            ->times(2);

        // Act
        $results = $this->service->sendToMultiple($eventName, $users, $variables);

        // Assert
        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->success);
        $this->assertTrue($results[1]->success);
        $this->assertEquals('mandrill-1', $results[0]->mandrillId);
        $this->assertEquals('mandrill-2', $results[1]->mandrillId);
    }

    /**
     * Test getting available templates
     */
    public function test_gets_available_templates(): void
    {
        // Arrange
        $templates = [
            'user.registered' => $this->createMockTemplate('user.registered'),
            'request.submitted' => $this->createMockTemplate('request.submitted'),
        ];

        $this->mockTemplateResolver
            ->shouldReceive('getAllTemplates')
            ->once()
            ->andReturn($templates);

        // Act
        $available = $this->service->getAvailableTemplates();

        // Assert
        $this->assertCount(2, $available);
        $this->assertArrayHasKey('user.registered', $available);
        $this->assertArrayHasKey('request.submitted', $available);
    }

    /**
     * Create a test user
     */
    private function createTestUser(string $email = 'test@example.com'): User
    {
        return User::factory()->create([
            'email' => $email,
            'name' => 'Test User',
        ]);
    }

    /**
     * Create a mock email template
     */
    private function createMockTemplate(string $eventName): EmailTemplate
    {
        return new EmailTemplate(
            eventName: $eventName,
            mandrillTemplateName: 'ocd-welcome',
            subject: 'Welcome to Ocean Decade Portal',
            variables: [
                'user_name' => 'required|string',
                'verification_url' => 'required|url',
                'portal_url' => 'required|url',
            ],
            tags: ['user', 'registration'],
            metadata: []
        );
    }

    /**
     * Get default variables that would be added by the service
     */
    private function getDefaultVariables(User $user): array
    {
        return [
            'user_name' => $user->name,
            'user_email' => $user->email,
            'portal_url' => config('app.url'),
            'current_year' => date('Y'),
            'support_email' => config('mail-templates.mandrill.reply_to', 'support@oceandecade.org'),
        ];
    }
}
