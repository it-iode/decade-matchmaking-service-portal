<?php

declare(strict_types=1);

namespace App\Domains\User\Services\Invitation;

use App\Domains\User\Models\UserInvitation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class InvitationQueryBuilder
{
    /**
     * Build base query with standard relationships
     */
    public function buildBaseQuery(): Builder
    {
        return UserInvitation::with(['inviter']);
    }

    /**
     * Apply search filters to query
     */
    public function applySearchFilters(Builder $query, array $searchFilters): Builder
    {
        if (! empty($searchFilters['name'])) {
            $query->where('name', 'like', '%'.$searchFilters['name'].'%');
        }

        if (! empty($searchFilters['email'])) {
            $query->where('email', 'like', '%'.$searchFilters['email'].'%');
        }

        if (! empty($searchFilters['status'])) {
            $this->applyStatusFilter($query, $searchFilters['status']);
        }

        if (! empty($searchFilters['inviter'])) {
            $query->whereHas('inviter', function ($q) use ($searchFilters) {
                $q->where('name', 'like', '%'.$searchFilters['inviter'].'%')
                    ->orWhere('email', 'like', '%'.$searchFilters['inviter'].'%');
            });
        }

        if (! empty($searchFilters['search'])) {
            $this->applyGeneralSearch($query, $searchFilters['search']);
        }

        return $query;
    }

    /**
     * Apply status filter (computed field based on accepted_at and expires_at)
     */
    private function applyStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'pending' => $query->whereNull('accepted_at')
                ->where('expires_at', '>', now()),
            'accepted' => $query->whereNotNull('accepted_at'),
            'expired' => $query->whereNull('accepted_at')
                ->where('expires_at', '<=', now()),
            default => null,
        };
    }

    /**
     * Apply general search across multiple fields
     */
    private function applyGeneralSearch(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', '%'.$searchTerm.'%')
                ->orWhere('email', 'like', '%'.$searchTerm.'%');
        });
    }

    /**
     * Apply sorting to query
     */
    public function applySorting(Builder $query, array $sortFilters): Builder
    {
        $field = $sortFilters['field'] ?? 'created_at';
        $order = $sortFilters['order'] ?? 'desc';

        $allowedFields = ['id', 'name', 'email', 'expires_at', 'accepted_at', 'created_at'];

        if (in_array($field, $allowedFields, true)) {
            $query->orderBy($field, $order);

            // Add secondary sort for consistency
            if ($field !== 'created_at') {
                $query->orderBy('created_at', 'desc');
            }
        } else {
            // Default sorting
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Apply pagination to query
     */
    public function applyPagination(Builder $query, array $sortFilters): LengthAwarePaginator
    {
        $perPage = $sortFilters['per_page'] ?? 15;

        return $query->paginate($perPage)->withQueryString();
    }
}
