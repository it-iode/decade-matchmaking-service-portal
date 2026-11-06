<?php

namespace App\Domains\User\Services;

use App\Domains\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserQueryBuilder
{
    public function buildBaseQuery(): Builder
    {
        return User::query()
            ->with(['roles', 'permissions'])
            ->withCount(['requests', 'notifications']);
    }

    public function applySearchFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function applyStatusFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $query->where('is_blocked', false)
                        ->whereNotNull('email_verified_at');
                    break;
                case 'blocked':
                    $query->where('is_blocked', true);
                    break;
                case 'unverified':
                    $query->whereNull('email_verified_at');
                    break;
            }
        }

        return $query;
    }

    public function applyRoleFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (! empty($filters['roles']) && is_array($filters['roles'])) {
            $query->role($filters['roles']);
        }

        return $query;
    }

    public function applySorting(Builder $query, array $sortFilters): Builder
    {
        $sortColumn = $sortFilters['sort'] ?? 'created_at';
        $sortDirection = $sortFilters['direction'] ?? 'desc';

        $allowedSortColumns = [
            'name',
            'email',
            'created_at',
            'updated_at',
            'requests_count',
            'country',
        ];

        if (in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        return $query;
    }

    public function applyPagination(Builder $query, array $sortFilters): LengthAwarePaginator
    {
        $perPage = $sortFilters['per_page'] ?? 15;
        $perPage = min(max($perPage, 10), 100); // Limit between 10 and 100

        return $query->paginate($perPage)->withQueryString();
    }
}
