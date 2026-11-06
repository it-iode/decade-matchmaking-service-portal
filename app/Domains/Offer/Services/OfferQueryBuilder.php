<?php

namespace App\Domains\Offer\Services;

use App\Domains\Offer\Models\Offer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OfferQueryBuilder
{
    /**
     * Apply search filters to query
     */
    public function applySearchFilters(Builder $query, array $searchFilters): Builder
    {
        if (! empty($searchFilters['description'])) {
            $query->where('description', 'like', '%'.$searchFilters['description'].'%');
        }

        if (! empty($searchFilters['partner'])) {
            $query->whereHas('matchedPartner', function ($q) use ($searchFilters) {
                $q->where('name', 'like', '%'.$searchFilters['partner'].'%');
            });
        }

        if (! empty($searchFilters['request'])) {
            $query->whereHas('request', function ($q) use ($searchFilters) {
                $q->where('id', '=', $searchFilters['request']);
            });
        }

        if (! empty($searchFilters['status'])) {
            $query->whereIn('status', $searchFilters['status']);
        }

        if (! empty($searchFilters['search'])) {
            $this->applyGeneralSearch($query, $searchFilters['search']);
        }

        if (! empty($searchFilters['date_from'])) {
            $query->whereDate('created_at', '>=', $searchFilters['date_from']);
        }

        if (! empty($searchFilters['date_to'])) {
            $query->whereDate('created_at', '<=', $searchFilters['date_to']);
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    public function applySorting(Builder $query, array $sortFilters): Builder
    {
        if (! empty($sortFilters['field']) && ! empty($sortFilters['order'])) {
            if ($sortFilters['field'] === 'partner_name') {
                $query->join('users as partners', 'request_offers.matched_partner_id', '=', 'partners.id')
                    ->orderBy('partners.name', $sortFilters['order'])
                    ->select('request_offers.*');
            } elseif ($sortFilters['field'] === 'request_title') {
                $query->join('request_details', 'request_offers.request_id', '=', 'request_details.request_id')
                    ->orderBy('request_details.capacity_development_title', $sortFilters['order'])
                    ->select('request_offers.*');
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
    public function applyPagination(Builder $query, array $sortFilters): LengthAwarePaginator
    {
        $perPage = $sortFilters['per_page'] ?? 10;

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Apply general search across multiple fields
     */
    private function applyGeneralSearch(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('description', 'like', '%'.$searchTerm.'%')
                ->orWhereHas('matchedPartner', function ($subQ) use ($searchTerm) {
                    $subQ->where('name', 'like', '%'.$searchTerm.'%');
                })
                ->orWhereHas('request.detail', function ($subQ) use ($searchTerm) {
                    $subQ->where('capacity_development_title', 'like', '%'.$searchTerm.'%');
                });
        });
    }

    /**
     * Build query for user's offers (as partner)
     */
    public function buildUserOffersQuery(int $userId): Builder
    {
        return Offer::with(['request', 'request.status', 'request.user', 'matchedPartner', 'documents'])
            ->where('matched_partner_id', $userId);
    }

    /**
     * Build query for offers on user's requests
     */
    public function buildRequestOffersQuery(int $userId): Builder
    {
        return Offer::with(['request', 'request.status', 'request.user', 'matchedPartner', 'documents'])
            ->whereHas('request', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }

    /**
     * Build query with standard relationships
     */
    public function buildBaseQuery(): Builder
    {
        return Offer::with(['request', 'request.detail', 'request.status', 'request.user', 'matchedPartner', 'documents']);
    }
}
