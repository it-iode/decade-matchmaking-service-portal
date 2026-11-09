<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Request;

use App\Domains\Request\Models\Request;
use App\Domains\Request\Services\RequestLogger;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class RequestLoggerTest extends TestCase
{
    private RequestLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new RequestLogger;
    }

    #[Test]
    public function log_created_logs_to_requests_channel(): void
    {
        Log::shouldReceive('channel')->with('requests')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Request created', \Mockery::on(function ($context) {
            return $context['request_id'] === 1 && $context['user_id'] === 2;
        }));

        $user = (new User(['email' => 'a@example.com']))->forceFill(['id' => 2]);
        $request = (new Request(['status_id' => 3]))->forceFill(['id' => 1]);

        $this->logger->logCreated($request, $user);
    }

    #[Test]
    public function log_updated_logs_changed_field_keys(): void
    {
        Log::shouldReceive('channel')->with('requests')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Request updated', \Mockery::on(function ($context) {
            return $context['changed_fields'] === ['title'];
        }));

        $user = new User(['id' => 2, 'email' => 'a@example.com']);
        $request = new Request(['id' => 1, 'status_id' => 3]);

        $this->logger->logUpdated($request, $user, ['title' => 'New title']);
    }

    #[Test]
    public function log_status_changed_logs_old_and_new_status(): void
    {
        Log::shouldReceive('channel')->with('requests')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Request status changed', \Mockery::on(function ($context) {
            return $context['old_status'] === 1 && $context['new_status'] === 2;
        }));

        $user = new User(['id' => 2, 'email' => 'a@example.com']);
        $request = new Request(['id' => 1]);

        $this->logger->logStatusChanged($request, 1, 2, $user);
    }

    #[Test]
    public function log_deleted_logs_request_id(): void
    {
        Log::shouldReceive('channel')->with('requests')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Request deleted', \Mockery::on(function ($context) {
            return $context['request_id'] === 42;
        }));

        $user = new User(['id' => 2, 'email' => 'a@example.com']);

        $this->logger->logDeleted(42, $user);
    }

    #[Test]
    public function log_authorization_failure_logs_a_warning(): void
    {
        Log::shouldReceive('channel')->with('requests')->andReturnSelf();
        Log::shouldReceive('warning')->once()->with('Authorization failed', \Mockery::on(function ($context) {
            return $context['action'] === 'deleteRequest' && $context['request_id'] === 5;
        }));

        $user = new User(['id' => 2, 'email' => 'a@example.com']);

        $this->logger->logAuthorizationFailure('deleteRequest', 5, $user);
    }

    #[Test]
    public function log_error_logs_exception_details(): void
    {
        Log::shouldReceive('channel')->with('requests')->andReturnSelf();
        Log::shouldReceive('error')->once()->with('Request operation failed', \Mockery::on(function ($context) {
            return $context['operation'] === 'storeRequest' && $context['error_message'] === 'boom';
        }));

        $this->logger->logError('storeRequest', new RuntimeException('boom'), ['user_id' => 1]);
    }
}
