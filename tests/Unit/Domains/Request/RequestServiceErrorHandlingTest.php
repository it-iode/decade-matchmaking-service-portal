<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Request;

use App\Domains\Request\Exceptions\RequestAuthorizationException;
use App\Domains\Request\Exceptions\RequestNotFoundException;
use App\Domains\Request\Exceptions\RequestStorageException;
use App\Domains\Request\Exceptions\RequestValidationException;
use App\Domains\Request\Models\Request;
use App\Domains\Request\Services\RequestLogger;
use App\Domains\Request\Services\RequestRepository;
use App\Domains\Request\Services\RequestService;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

class RequestServiceErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private RequestService $service;

    private RequestRepository $repository;

    private RequestLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(RequestRepository::class);
        $this->logger = Mockery::mock(RequestLogger::class);
        $this->service = new RequestService($this->repository, $this->logger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function store_request_throws_validation_exception_for_invalid_mode(): void
    {
        $user = User::factory()->create();

        $this->expectException(RequestValidationException::class);
        $this->expectExceptionMessage('Invalid mode parameter: invalid_mode');

        $this->service->storeRequest($user, [], null, 'invalid_mode');
    }

    #[Test]
    public function store_request_throws_not_found_exception_when_updating_non_existent_request(): void
    {
        $user = User::factory()->create();
        $request = new Request(['id' => 999]);

        $this->repository->shouldReceive('findById')
            ->with(999)
            ->andReturn(null);

        $this->expectException(RequestNotFoundException::class);
        $this->expectExceptionMessage('Cannot update request with ID 999');

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->service->storeRequest($user, [], $request, 'submit');
    }

    #[Test]
    public function store_request_throws_storage_exception_when_create_fails(): void
    {
        $user = User::factory()->create();

        $this->repository->shouldReceive('getStatusByCode')
            ->with('under_review')
            ->andReturn((object) ['id' => 1]);

        $this->repository->shouldReceive('create')
            ->andReturn(null);

        $this->logger->shouldReceive('logError')
            ->once();

        $this->expectException(RequestStorageException::class);

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->service->storeRequest($user, [], null, 'submit');
    }

    #[Test]
    public function store_request_logs_created_request(): void
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        $this->repository->shouldReceive('getStatusByCode')
            ->with('under_review')
            ->andReturn((object) ['id' => 1]);

        $this->repository->shouldReceive('create')
            ->andReturn($request);

        $this->repository->shouldReceive('createOrUpdateDetail')
            ->once();

        $this->logger->shouldReceive('logCreated')
            ->once()
            ->with($request, $user);

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $result = $this->service->storeRequest($user, ['title' => 'Test'], null, 'submit');

        $this->assertInstanceOf(Request::class, $result);
    }

    #[Test]
    public function store_request_logs_updated_request(): void
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->repository->shouldReceive('getStatusByCode')
            ->with('under_review')
            ->andReturn((object) ['id' => 1]);

        $this->repository->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $this->repository->shouldReceive('createOrUpdateDetail')
            ->once();

        $this->logger->shouldReceive('logUpdated')
            ->once()
            ->with($request, $user, Mockery::type('array'));

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $result = $this->service->storeRequest($user, ['title' => 'Updated'], $request, 'submit');

        $this->assertInstanceOf(Request::class, $result);
    }

    #[Test]
    public function store_request_rolls_back_transaction_on_error(): void
    {
        $user = User::factory()->create();

        $this->repository->shouldReceive('getStatusByCode')
            ->andThrow(new \Exception('Database error'));

        $this->logger->shouldReceive('logError')
            ->once()
            ->with('storeRequest', Mockery::type(Throwable::class), Mockery::type('array'));

        $this->expectException(RequestStorageException::class);
        $this->expectExceptionMessage('Database transaction failed');

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->service->storeRequest($user, [], null, 'submit');
    }

    #[Test]
    public function update_request_status_throws_not_found_exception(): void
    {
        $user = User::factory()->create();

        $this->repository->shouldReceive('findById')
            ->with(999)
            ->andReturn(null);

        $this->expectException(RequestNotFoundException::class);
        $this->expectExceptionMessage('Cannot change status for request with ID 999');

        $this->service->updateRequestStatus(999, 'approved', $user);
    }

    #[Test]
    public function update_request_status_throws_authorization_exception_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $owner->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->logger->shouldReceive('logAuthorizationFailure')
            ->once()
            ->with('updateRequestStatus', $request->id, $otherUser);

        $this->expectException(RequestAuthorizationException::class);
        $this->expectExceptionMessage('not authorized to change status');

        $this->service->updateRequestStatus($request->id, 'approved', $otherUser);
    }

    #[Test]
    public function update_request_status_allows_administrator_to_update(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->shouldReceive('hasRole')->with('administrator')->andReturn(true);

        $request = Request::factory()->create(['user_id' => $owner->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->repository->shouldReceive('getStatusByCode')
            ->with('approved')
            ->andReturn((object) ['id' => 2]);

        $this->repository->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $this->logger->shouldReceive('logStatusChanged')
            ->once();

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $result = $this->service->updateRequestStatus($request->id, 'approved', $admin);

        $this->assertInstanceOf(Request::class, $result);
    }

    #[Test]
    public function update_request_status_throws_validation_exception_for_invalid_status(): void
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->repository->shouldReceive('getStatusByCode')
            ->with('invalid_status')
            ->andReturn(null);

        $this->expectException(RequestValidationException::class);
        $this->expectExceptionMessage('Validation failed for field \'status_code\'');

        $this->service->updateRequestStatus($request->id, 'invalid_status', $user);
    }

    #[Test]
    public function update_request_status_logs_status_change(): void
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id, 'status_id' => 1]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->repository->shouldReceive('getStatusByCode')
            ->with('approved')
            ->andReturn((object) ['id' => 2]);

        $this->repository->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $this->logger->shouldReceive('logStatusChanged')
            ->once()
            ->with($request, 1, 2, $user);

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->service->updateRequestStatus($request->id, 'approved', $user);
    }

    #[Test]
    public function update_request_status_logs_authorization_failure(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $owner->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->logger->shouldReceive('logAuthorizationFailure')
            ->once()
            ->with('updateRequestStatus', $request->id, $otherUser);

        try {
            $this->service->updateRequestStatus($request->id, 'approved', $otherUser);
        } catch (RequestAuthorizationException $e) {
            // Expected
            $this->assertInstanceOf(RequestAuthorizationException::class, $e);
        }
    }

    #[Test]
    public function update_request_status_throws_storage_exception_when_update_fails(): void
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->repository->shouldReceive('getStatusByCode')
            ->with('approved')
            ->andReturn((object) ['id' => 2]);

        $this->repository->shouldReceive('update')
            ->once()
            ->andReturn(false);

        $this->expectException(RequestStorageException::class);
        $this->expectExceptionMessage('Failed to update status');

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->service->updateRequestStatus($request->id, 'approved', $user);
    }

    #[Test]
    public function delete_request_throws_not_found_exception(): void
    {
        $user = User::factory()->create();

        $this->repository->shouldReceive('findById')
            ->with(999)
            ->andReturn(null);

        $this->expectException(RequestNotFoundException::class);
        $this->expectExceptionMessage('Cannot delete request with ID 999');

        $this->service->deleteRequest(999, $user);
    }

    #[Test]
    public function delete_request_throws_authorization_exception_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $owner->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->logger->shouldReceive('logAuthorizationFailure')
            ->once()
            ->with('deleteRequest', $request->id, $otherUser);

        $this->expectException(RequestAuthorizationException::class);
        $this->expectExceptionMessage('not authorized to delete');

        $this->service->deleteRequest($request->id, $otherUser);
    }

    #[Test]
    public function delete_request_logs_deletion(): void
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->repository->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $this->logger->shouldReceive('logDeleted')
            ->once()
            ->with($request->id, $user);

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $result = $this->service->deleteRequest($request->id, $user);

        $this->assertTrue($result);
    }

    #[Test]
    public function delete_request_throws_storage_exception_when_deletion_fails(): void
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->repository->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        $this->logger->shouldReceive('logError')
            ->once();

        $this->expectException(RequestStorageException::class);
        $this->expectExceptionMessage('Failed to delete');

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->service->deleteRequest($request->id, $user);
    }

    #[Test]
    public function delete_request_rolls_back_transaction_on_error(): void
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        $this->repository->shouldReceive('findById')
            ->with($request->id)
            ->andReturn($request);

        $this->repository->shouldReceive('delete')
            ->andThrow(new \Exception('Database error'));

        $this->logger->shouldReceive('logError')
            ->once()
            ->with('deleteRequest', Mockery::type(Throwable::class), Mockery::type('array'));

        $this->expectException(RequestStorageException::class);
        $this->expectExceptionMessage('Database transaction failed');

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->service->deleteRequest($request->id, $user);
    }
}
