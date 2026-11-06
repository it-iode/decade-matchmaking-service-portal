<?php

declare(strict_types=1);

namespace App\Domains\Request\Services;

use App\Domains\Request\Exceptions\RequestAuthorizationException;
use App\Domains\Request\Exceptions\RequestNotFoundException;
use App\Domains\Request\Exceptions\RequestStorageException;
use App\Domains\Request\Exceptions\RequestValidationException;
use App\Domains\Request\Models\Request;
use App\Domains\Request\Models\Status;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class RequestService
{
    public function __construct(
        private RequestRepository $repository,
        private RequestLogger $logger
    ) {}

    /**
     * Store a new request or update existing one
     *
     * @param  User  $user  The user creating/updating the request
     * @param  array<string, mixed>  $data  The request data
     * @param  Request|null  $request  Existing request for updates
     * @param  string  $mode  Operation mode: 'draft' or 'submit'
     * @return Request The created or updated request
     *
     * @throws RequestValidationException If mode is invalid
     * @throws RequestNotFoundException If request not found during update
     * @throws RequestStorageException If database operation fails
     */
    public function storeRequest(User $user, array $data, ?Request $request = null, string $mode = 'submit'): Request
    {
        // Validate mode parameter
        if (! in_array($mode, ['draft', 'submit'], true)) {
            throw RequestValidationException::invalidMode($mode);
        }

        try {
            return DB::transaction(function () use ($user, $data, $request, $mode) {
                $isUpdate = $request !== null;

                // Validate request exists for update mode
                if ($isUpdate && ! $this->repository->findById($request->id)) {
                    throw RequestNotFoundException::forUpdate($request->id);
                }

                $statusId = match ($mode) {
                    'draft' => $this->getStatusId('draft'),
                    default => $this->getStatusId('under_review'),
                };

                if ($isUpdate) {
                    // Never reassign ownership on update; only the status may change here.
                    $this->repository->update($request, ['status_id' => $statusId]);
                } else {
                    $request = $this->repository->create([
                        'user_id' => $user->id,
                        'status_id' => $statusId,
                    ]);
                    if (! $request) {
                        throw RequestStorageException::failedToCreate();
                    }
                }

                $this->repository->createOrUpdateDetail($request, $data);

                // Log the operation
                if ($isUpdate) {
                    $this->logger->logUpdated($request, $user, $data);
                } else {
                    $this->logger->logCreated($request, $user);
                }

                return $request->load(['status', 'detail']);
            });
        } catch (RequestValidationException|RequestNotFoundException|RequestStorageException $e) {
            // Re-throw our custom exceptions
            throw $e;
        } catch (Throwable $e) {
            // Log and wrap any other exceptions
            $this->logger->logError('storeRequest', $e, [
                'user_id' => $user->id,
                'request_id' => $request?->id,
                'mode' => $mode,
            ]);
            throw RequestStorageException::transactionFailed('storeRequest', $e);
        }
    }

    public function getAllRequests(): Collection
    {
        return $this->repository->getAll();
    }

    /**
     * Find request by ID with authorization
     */
    public function findRequest(int $id): ?Request
    {
        return $this->repository->findById($id);
    }

    /**
     * Update request status
     *
     * @param  string  $statusCode  The new status code
     * @return Request The updated request
     *
     * @throws RequestStorageException If database update fails
     * @throws RequestValidationException If status code is invalid
     */
    public function updateRequestStatus(Request $request, string $statusCode): Request
    {
        $statusId = $this->getStatusId($statusCode);
        if (! $statusId) {
            throw RequestValidationException::invalidField('status_code', "Status code '{$statusCode}' does not exist");
        }

        try {
            return DB::transaction(function () use ($request, $statusId) {
                $oldStatusId = $request->status_id;

                $updated = $this->repository->update($request, ['status_id' => $statusId]);

                if (! $updated) {
                    throw RequestStorageException::failedToUpdateStatus($request->id, $statusId);
                }

                return $request->fresh();
            });
        } catch (RequestStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logger->logError('updateRequestStatus', $e, [
                'request_id' => $request->id,
                'status_code' => $statusCode,
            ]);
            throw RequestStorageException::transactionFailed('updateRequestStatus', $e);
        }
    }

    /**
     * Delete request
     *
     * @param  int  $requestId  The ID of the request to delete
     * @param  User  $user  The user performing the deletion
     * @return bool True if deletion was successful
     *
     * @throws RequestNotFoundException If request not found
     * @throws RequestAuthorizationException If user not authorized
     * @throws RequestStorageException If database deletion fails
     */
    public function deleteRequest(int $requestId, User $user): bool
    {
        $request = $this->repository->findById($requestId);

        if (! $request) {
            throw RequestNotFoundException::forDeletion($requestId);
        }

        // Check authorization
        if ($request->user_id !== $user->id && ! $user->hasRole('administrator')) {
            $this->logger->logAuthorizationFailure('deleteRequest', $requestId, $user);
            throw RequestAuthorizationException::forDeletion($requestId, $user->id);
        }

        try {
            DB::transaction(function () use ($request) {
                // Delete normalized data if exists
                if ($request->detail_id) {
                    $request->detail()->delete();
                }

                // Delete the request
                $deleted = $this->repository->delete($request);

                if (! $deleted) {
                    throw RequestStorageException::failedToDelete($request->id);
                }
            });

            // Log the deletion
            $this->logger->logDeleted($requestId, $user);

            return true;
        } catch (RequestStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logger->logError('deleteRequest', $e, [
                'request_id' => $requestId,
                'user_id' => $user->id,
            ]);
            throw RequestStorageException::transactionFailed('deleteRequest', $e);
        }
    }

    /**
     * Get paginated requests with search and sorting
     */
    public function getPaginatedRequests(array $searchFilters = [], array $sortFilters = []): AbstractPaginator
    {
        return $this->repository->getPaginated($searchFilters, $sortFilters)->withQueryString();
    }

    /**
     * Get public requests (for partners)
     */
    public function getPublicRequests(
        array $searchFilters = [],
        array $sortFilters = []
    ): AbstractPaginator {
        return $this->repository->getPublicRequests($searchFilters, $sortFilters)->withQueryString();
    }

    /**
     * Get matched requests for user
     */
    public function getMatchedRequests(
        User $user,
        array $searchFilters = [],
        array $sortFilters = []
    ): AbstractPaginator {
        return $this->repository->getMatchedRequests($user, $searchFilters, $sortFilters)->withQueryString();
    }

    public function getSubscribedRequests(
        User $user,
        array $searchFilters = [],
        array $sortFilters = []
    ): AbstractPaginator {
        return $this->repository->getSubscribedRequests($user, $searchFilters, $sortFilters)->withQueryString();
    }

    /**
     * Get user's requests
     */
    public function getUserRequests(
        User $user,
        array $searchFilters = [],
        array $sortFilters = []
    ): AbstractPaginator {
        return $this->repository->getUserRequests($user, $searchFilters, $sortFilters)->withQueryString();
    }

    /**
     * Get request by ID (for admin/system use)
     *
     * @throws RequestNotFoundException
     */
    public function getRequestById(int $id, ?User $user = null): ?Request
    {
        $request = $this->repository->findById($id);
        if (! $request) {
            throw RequestNotFoundException::forId($id);
        }

        return $request;
    }

    /**
     * Get status ID by code
     */
    private function getStatusId(string $statusCode): ?int
    {
        $status = $this->repository->getStatusByCode($statusCode);

        return $status?->getAttribute('id');
    }

    /**
     * Get available statuses for filtering
     */
    public static function getAvailableStatuses(): Collection
    {
        return Status::select('id', 'status_code', 'status_label')
            ->orderBy('status_label')
            ->get();
    }
}
