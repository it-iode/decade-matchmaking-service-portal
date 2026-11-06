<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Services;

use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Resources\OpportunityResource;
use App\Domains\User\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class OpportunityService
{
    public function __construct(
        private OpportunityRepository $repository,
        private OpportunityAnalyticsService $analytics
    ) {}

    /**
     * Create a new opportunity
     *
     * @throws Throwable
     */
    public function storeOpportunity(User $user, array $data, ?Opportunity $opportunity): Opportunity
    {
        return DB::transaction(function () use ($data, $user, $opportunity) {
            $data += [
                'status' => Status::PENDING_REVIEW,
                'user_id' => $user->id,
            ];

            $type = isset($data['type']) ? Type::tryFrom((string) $data['type']) : null;
            if ($type?->hasReducedForm()) {
                // Clear fields the reduced form does not collect, so an update switching
                // to a reduced type does not keep stale values.
                foreach (Type::REDUCED_FORM_FIELDS as $field) {
                    $data[$field] = null;
                }
            }
            if ($opportunity) {
                $this->repository->update($opportunity, $data);
                $opportunity = $opportunity->fresh();
            } else {
                $opportunity = $this->repository->create($data);
            }

            return $opportunity;
        });
    }

    /**
     * Get paginated opportunities submitted by a specific user
     *
     * @throws Throwable
     */
    public function getUserOpportunitiesPaginated(
        User $user,
        array $searchFilters = [],
        array $sortFilters = []
    ): LengthAwarePaginator {
        $opportunities = $this->repository->getUserOpportunitiesPaginated($user, $searchFilters, $sortFilters);
        $opportunities->toResourceCollection(OpportunityResource::class);

        return $opportunities;
    }

    /**
     * @throws Throwable
     */
    public function getAllOpportunitiesPaginated(
        array $searchFilters = [],
        array $sortFilters = []
    ): LengthAwarePaginator {
        $opportunities = $this->repository->getPaginated($searchFilters, $sortFilters);
        $opportunities->toResourceCollection(OpportunityResource::class);

        return $opportunities;
    }

    /**
     * Get paginated active opportunities (active status only)
     *
     * @throws Throwable
     */
    public function getActiveOpportunitiesPaginated(
        array $searchFilters = [],
        array $sortFilters = []
    ): LengthAwarePaginator {
        $opportunities = $this->repository->getActiveOpportunitiesPaginated($searchFilters, $sortFilters);
        $opportunities->toResourceCollection(OpportunityResource::class);

        return $opportunities;
    }

    /**
     * Find opportunity by ID with authorization check
     */
    public function findOpportunity(int $id): ?Opportunity
    {
        $opportunity = $this->repository->findById($id);
        if (! $opportunity) {
            return null;
        }

        return $opportunity;
    }

    /**
     * Update opportunity status
     *
     * @throws Exception
     */
    public function updateOpportunityStatus(Opportunity $opportunity, int $statusCode, User $user): array
    {
        // Validate status
        if (! in_array($statusCode, array_column(Status::cases(), 'value'))) {
            throw new Exception('Invalid status code', 422);
        }

        return DB::transaction(function () use ($opportunity, $statusCode) {
            $this->repository->update($opportunity, ['status' => $statusCode]);
            $opportunity = $opportunity->fresh();

            return [
                'opportunity' => $opportunity,
                'status' => [
                    'status_code' => (string) $statusCode,
                    'status_label' => Status::tryFrom($statusCode) ?? '',
                ],
            ];
        });
    }

    /**
     * Delete opportunity with validation
     */
    public function deleteOpportunity(Opportunity $opportunity, User $user): bool
    {
        // Check ownership
        if ($opportunity->user_id !== $user->id) {
            throw new Exception('Unauthorized to delete this opportunity', 403);
        }

        // Check if can be deleted (only pending review)
        if ($opportunity->status !== Status::PENDING_REVIEW) {
            throw new Exception('Only pending review opportunities can be deleted', 422);
        }

        $deleted = $this->repository->delete($opportunity);

        if ($deleted) {
            Log::info('Opportunity deleted', [
                'opportunity_id' => $opportunity->id,
                'user_id' => $user->id,
                'title' => $opportunity->title,
            ]);
        }

        return $deleted;
    }

    /**
     * Get opportunity statistics
     */
    public function getOpportunityStats(User $user): array
    {
        return $this->analytics->getUserOpportunityStats($user);
    }

    /**
     * Search opportunities with filters
     */
    public function searchOpportunities(array $filters, User $user): Collection
    {
        return $this->repository->search($filters);
    }

    public function extendOpportunityClosingDate(Opportunity $opportunity, ?\Carbon\Carbon $newClosingDate = null): Opportunity
    {
        $closingDate = $newClosingDate ?? $opportunity->closing_date->addWeeks(2);
        $this->repository->update($opportunity, ['closing_date' => $closingDate]);

        return $opportunity->fresh();
    }

    /**
     * Get all active public opportunities
     */
    public function getPublicOpportunities(): Collection
    {
        return $this->repository->getPublicOpportunities();
    }

    /**
     * Get active, still-open opportunities of a given type, closing soonest first.
     *
     * @return Collection<int, Opportunity>
     */
    public function getActiveOpportunitiesByType(Type $type, int $limit = 50): Collection
    {
        return $this->repository->getActiveOpenByType($type, $limit);
    }

    /**
     * Get the most recent active opportunities
     */
    public function getRecentActiveOpportunities(int $limit = 10): Collection
    {
        return Opportunity::where('status', Status::ACTIVE)
            ->where('closing_date', '>=', now())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all opportunities that have passed their closing date and are not already closed
     */
    public function getExpiredOpportunities(): Collection
    {
        return Opportunity::where('closing_date', '<', now())
            ->whereNotIn('status', [Status::CLOSED->value, Status::REJECTED->value])
            ->orderBy('closing_date', 'asc')
            ->get();
    }

    /**
     * Close all expired opportunities and return results summary
     *
     * @throws Throwable
     */
    public function closeExpiredOpportunities(): array
    {
        $results = [
            'total' => 0,
            'closed' => 0,
            'failed' => 0,
            'errors' => [],
            'closed_opportunities' => [],
        ];

        return DB::transaction(function () use (&$results) {
            $expiredOpportunities = $this->getExpiredOpportunities();
            $results['total'] = $expiredOpportunities->count();

            if ($expiredOpportunities->isEmpty()) {
                Log::info('[OpportunityService] No expired opportunities found');

                return $results;
            }

            Log::info("[OpportunityService] Processing {$results['total']} expired opportunities");

            foreach ($expiredOpportunities as $opportunity) {
                try {
                    $previousStatus = $opportunity->status;

                    $this->repository->update($opportunity, [
                        'status' => Status::CLOSED->value,
                        'closed_at' => now(),
                        'closed_reason' => 'Automatically closed - deadline passed',
                        'previous_status' => $previousStatus,
                    ]);

                    $results['closed']++;
                    $results['closed_opportunities'][] = [
                        'id' => $opportunity->id,
                        'title' => $opportunity->title,
                        'previous_status' => $previousStatus,
                        'closing_date' => $opportunity->closing_date,
                    ];

                    Log::info('[OpportunityService] Opportunity auto-closed', [
                        'opportunity_id' => $opportunity->id,
                        'title' => $opportunity->title,
                        'closing_date' => $opportunity->closing_date,
                        'previous_status' => $previousStatus,
                    ]);

                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'opportunity_id' => $opportunity->id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('[OpportunityService] Failed to close expired opportunity', [
                        'opportunity_id' => $opportunity->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('[OpportunityService] Opportunity closure process completed', [
                'total_processed' => $results['total'],
                'successfully_closed' => $results['closed'],
                'failed' => $results['failed'],
            ]);

            return $results;
        });
    }
}
