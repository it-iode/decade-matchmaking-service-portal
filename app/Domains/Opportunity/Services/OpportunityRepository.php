<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Services;

use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

class OpportunityRepository
{
    public function __construct(
        private readonly OpportunityQueryBuilder $queryBuilder
    ) {}

    /**
     * Create a new opportunity
     */
    public function create(array $data): Opportunity
    {
        $opportunity = new Opportunity($data);
        $opportunity->save();

        return $opportunity;
    }

    /**
     * Find opportunity by ID
     */
    public function findById(int $id): ?Opportunity
    {
        return Opportunity::find($id);
    }

    /**
     * Get opportunities submitted by a specific user
     */
    public function getByUser(User $user): Collection
    {
        return Opportunity::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get public opportunities (active status only)
     */
    public function getPublicOpportunities(): Collection
    {
        return Opportunity::where('status', Status::ACTIVE)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update opportunity
     */
    public function update(Opportunity $opportunity, array $data): bool
    {
        return $opportunity->update($data);
    }

    /**
     * Delete opportunity
     */
    public function delete(Opportunity $opportunity): bool
    {
        return $opportunity->delete();
    }

    /**
     * Get opportunities count by status for a user
     */
    public function getCountByStatus(User $user): array
    {
        $opportunities = $this->getByUser($user);

        return [
            'total' => $opportunities->count(),
            'active' => $opportunities->where('status', Status::ACTIVE)->count(),
            'pending' => $opportunities->where('status', Status::PENDING_REVIEW)->count(),
            'closed' => $opportunities->where('status', Status::CLOSED)->count(),
        ];
    }

    /**
     * Get paginated opportunities with search and sorting
     */
    public function getPaginated(array $searchFilters = [], array $sortFilters = []): LengthAwarePaginator
    {
        $query = $this->queryBuilder->buildBaseQuery();
        $query = $this->queryBuilder->applySearchFilters($query, $searchFilters);
        $query = $this->queryBuilder->applySorting($query, $sortFilters);

        return $this->queryBuilder->applyPagination($query, $sortFilters);
    }

    /**
     * Get user's opportunities paginated
     */
    public function getUserOpportunitiesPaginated(
        User $user,
        array $searchFilters = [],
        array $sortFilters = []
    ): LengthAwarePaginator {
        $query = $this->queryBuilder->buildUserOpportunitiesQuery($user->id);
        $query = $this->queryBuilder->applySearchFilters($query, $searchFilters);
        $query = $this->queryBuilder->applySorting($query, $sortFilters);

        return $this->queryBuilder->applyPagination($query, $sortFilters);
    }

    /**
     * Get active opportunities paginated
     */
    public function getActiveOpportunitiesPaginated(
        array $searchFilters = [],
        array $sortFilters = []
    ): LengthAwarePaginator {
        $query = $this->queryBuilder->buildActiveOpportunitiesQuery();
        $query = $this->queryBuilder->applySearchFilters($query, $searchFilters);
        $query = $this->queryBuilder->applySorting($query, $sortFilters);

        return $this->queryBuilder->applyPagination($query, $sortFilters);
    }

    /**
     * Search opportunities with filters
     */
    public function search(array $filters): Collection
    {
        $query = $this->queryBuilder->buildBaseQuery();
        $query = $this->queryBuilder->applySearchFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get opportunities for CSV export with user relationship
     *
     * Uses cursor-based loading for memory efficiency when exporting large datasets.
     *
     * @return LazyCollection<int, Opportunity>
     */
    /**
     * Active, still-open opportunities of a given type, closing soonest first.
     *
     * @return Collection<int, Opportunity>
     */
    public function getActiveOpenByType(Type $type, int $limit = 50): Collection
    {
        return $this->queryBuilder->buildActiveOpenByClosingDateQuery()
            ->with('user')
            ->where('type', $type->value)
            ->limit($limit)
            ->get();
    }

    public function getOpportunitiesForExport(): LazyCollection
    {
        return Opportunity::query()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->cursor();
    }
}
