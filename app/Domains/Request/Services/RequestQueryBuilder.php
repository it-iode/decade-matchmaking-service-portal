<?php

namespace App\Domains\Request\Services;

use App\Domains\Request\Enums\PublicRequestStatus;
use App\Domains\Request\Models\Request as OCDRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\AbstractPaginator;

class RequestQueryBuilder
{
    /**
     * Apply search filters to query
     */
    public function applySearchFilters(Builder $query, array $searchFilters): Builder
    {
        if (! empty($searchFilters['user'])) {
            $query->whereHas('user', function ($q) use ($searchFilters) {
                $q->where('name', 'like', '%'.$searchFilters['user'].'%');
            });
        }

        if (! empty($searchFilters['title'])) {
            $query->whereHas('detail', function ($q) use ($searchFilters) {
                $q->where('capacity_development_title', 'like', '%'.$searchFilters['title'].'%');
            });
        }

        if (! empty($searchFilters['search'])) {
            $this->applyGeneralSearch($query, $searchFilters['search']);
        }

        if (! empty($searchFilters['status'])) {
            $query->whereHas('status', function (Builder $q) use ($searchFilters) {
                $q->whereIn('status_code', $searchFilters['status']);
            });
        }
        if (! empty($searchFilters['public_status'])) {
            $query->whereHas('status', function (Builder $q) use ($searchFilters) {
                $q->whereIn('status_code', PublicRequestStatus::getTechnicalStatusCodes($searchFilters['public_status']));
            });
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    public function applySorting(Builder $query, array $sortFilters): Builder
    {
        if (! empty($sortFilters['field']) && ! empty($sortFilters['order'])) {
            if ($sortFilters['field'] === 'user') {
                $query->join('users', 'requests.user_id', '=', 'users.id')
                    ->orderBy('users.name', $sortFilters['order'])
                    ->select('requests.*');
            } else {
                $query->orderBy($sortFilters['field'], $sortFilters['order']);
            }

            // Add secondary sort for consistency
            if ($sortFilters['field'] !== 'created_at') {
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
    public function applyPagination(Builder $query, array $sortFilters): AbstractPaginator
    {
        $perPage = $sortFilters['per_page'] ?? 10;

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Apply general search across multiple fields
     */
    private function applyGeneralSearch(Builder $query, string $searchTerm): void
    {
        $query->orWhereHas('detail', function (Builder $q) use ($searchTerm) {
            $q->whereRaw(
                'MATCH(capacity_development_title, gap_description, expected_outcomes) AGAINST(? IN BOOLEAN MODE)',
                [$searchTerm]
            );
        });
    }

    /**
     * Build query for public requests
     */
    public function buildPublicRequestsQuery(): Builder
    {
        $publicStatuses = ['validated', 'offer_made', 'match_made', 'closed', 'in_implementation'];

        return OCDRequest::with(['status', 'detail'])
            ->whereHas('status', function (Builder $query) use ($publicStatuses) {
                $query->whereIn('status_code', $publicStatuses);
            });
    }

    /**
     * Build query for user's requests
     */
    public function buildUserRequestsQuery(int $userId): Builder
    {
        return OCDRequest::with(['status', 'detail'])
            ->where('user_id', $userId);
    }

    /**
     * Build query for matched requests
     */
    public function buildMatchedRequestsQuery(int $userId): Builder
    {
        return OCDRequest::with(['status', 'detail'])
            ->whereHas('offers', function (Builder $query) use ($userId) {
                $query->where('matched_partner_id', $userId);
            });
    }

    public function buildSubscribedRequestsQuery(int $userId): Builder
    {
        return OCDRequest::with(['status', 'detail'])
            ->whereHas('subscriptions', function (Builder $query) use ($userId) {
                $query->where('user_id', $userId);
            });
    }

    /**
     * Build query with standard relationships
     */
    public function buildBaseQuery(): Builder
    {
        return OCDRequest::with(['status', 'detail', 'user', 'offers']);
    }
}
