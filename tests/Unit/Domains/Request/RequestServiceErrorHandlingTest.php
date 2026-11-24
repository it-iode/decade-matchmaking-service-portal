<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Request;

use App\Domains\Request\Exceptions\RequestNotFoundException;
use App\Domains\Request\Exceptions\RequestStorageException;
use App\Domains\Request\Exceptions\RequestValidationException;
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
}
