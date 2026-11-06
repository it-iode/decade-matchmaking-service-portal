<?php

namespace App\Domains\User\Services;

use App\Domains\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

class UserRepository
{
    public function __construct(
        private readonly UserQueryBuilder $queryBuilder
    ) {}

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function getPaginated(
        array $searchFilters,
        array $sortFilters
    ): LengthAwarePaginator {
        $query = $this->queryBuilder->buildBaseQuery();
        $query = $this->queryBuilder->applySearchFilters($query, $searchFilters);
        $query = $this->queryBuilder->applyStatusFilters($query, $searchFilters);
        $query = $this->queryBuilder->applyRoleFilters($query, $searchFilters);
        $query = $this->queryBuilder->applySorting($query, $sortFilters);

        return $this->queryBuilder->applyPagination($query, $sortFilters);
    }

    /**
     * Fetch users holding any of the given roles, projected for selection lists.
     *
     * @param  array<int, string>  $roleNames
     * @return Collection<int, User>
     */
    public function getSelectionCandidatesByRoles(array $roleNames): Collection
    {
        return User::role($roleNames)
            ->select('id', 'name', 'email', 'first_name', 'last_name')
            ->orderBy('name')
            ->get();
    }

    public function searchByQuery(string $query, int $limit = 20): Collection
    {
        $builder = $this->queryBuilder->buildBaseQuery();
        $builder = $this->queryBuilder->applySearchFilters($builder, ['search' => $query]);

        return $builder->limit($limit)->get();
    }

    /**
     * Get all users for export with relationships
     * Uses cursor for memory-efficient streaming
     *
     * @return LazyCollection<int, User>
     */
    public function getUsersForExport(): LazyCollection
    {
        return User::query()
            ->with('roles')
            ->cursor();
    }
}
