<?php

namespace App\Domains\Opportunity\Services;

use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Enums\ThematicAreas;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OpportunityQueryBuilder
{
    /**
     * Apply search filters to query
     */
    public function applySearchFilters(Builder $query, array $searchFilters): Builder
    {
        if (! empty($searchFilters['title'])) {
            $term = $searchFilters['title'];
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%'.$term.'%');
                $this->orWhereKeyWordsLike($q, $term);
            });
        }

        if (! empty($searchFilters['type'])) {
            $query->where('type', Type::tryFrom($searchFilters['type'])->value);
        }

        if (! empty($searchFilters['thematic_areas'])) {
            $query->whereJsonContains('thematic_areas', ThematicAreas::tryFrom($searchFilters['thematic_areas'])->value);
        }

        if (! empty($searchFilters['location'])) {
            $query->where('implementation_location', 'like', '%'.$searchFilters['location'].'%');
        }

        if (! empty($searchFilters['closing_date'])) {
            $query->whereDate('closing_date', '>=', $searchFilters['closing_date']);
        }

        if (! empty($searchFilters['user'])) {
            $query->whereHas('user', function ($q) use ($searchFilters) {
                $q->where('name', 'like', '%'.$searchFilters['user'].'%')
                    ->orWhere('email', 'like', '%'.$searchFilters['user'].'%');
            });
        }

        if (! empty($searchFilters['search'])) {
            $this->applyGeneralSearch($query, $searchFilters['search']);
        }

        if (! empty($searchFilters['status'])) {
            $query->where('status', Status::tryFrom($searchFilters['status'])->value);
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
            if ($sortFilters['field'] === 'user_id') {
                $query->join('users', 'opportunities.user_id', '=', 'users.id')
                    ->orderBy('users.name', $sortFilters['order'])
                    ->select('opportunities.*');
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
            $q->where('title', 'like', '%'.$searchTerm.'%')
                ->orWhere('summary', 'like', '%'.$searchTerm.'%');
            $this->orWhereKeyWordsLike($q, $searchTerm);
        });
    }

    /**
     * `key_words` is a JSON column; MySQL compares JSON values as binary, so a
     * plain LIKE is case-sensitive. Lower-case both sides to make it
     * case-insensitive.
     */
    private function orWhereKeyWordsLike(Builder $query, string $term): Builder
    {
        return $query->orWhereRaw('LOWER(key_words) LIKE ?', ['%'.mb_strtolower($term).'%']);
    }

    /**
     * Build query for user's opportunities
     */
    public function buildUserOpportunitiesQuery(int $userId): Builder
    {
        return Opportunity::with(['user'])
            ->where('user_id', $userId);
    }

    /**
     * Build query for active opportunities
     */
    public function buildActiveOpportunitiesQuery(): Builder
    {
        return Opportunity::with(['user'])
            ->where('status', Status::ACTIVE);
    }

    /**
     * Build query for active opportunities that are still open (closing date in
     * the future), ordered by soonest closing date first. Used by the weekly
     * newsletter to surface the most urgent opportunities.
     */
    public function buildActiveOpenByClosingDateQuery(): Builder
    {
        return Opportunity::query()
            ->where('status', Status::ACTIVE)
            ->whereNotNull('closing_date')
            ->whereDate('closing_date', '>=', now()->toDateString())
            ->orderBy('closing_date', 'asc');
    }

    /**
     * Build query for public opportunities (excluding user's own)
     */
    public function buildPublicOpportunitiesQuery(int $excludeUserId): Builder
    {
        return Opportunity::with(['user'])
            ->where('user_id', '!=', $excludeUserId)
            ->where('status', Status::ACTIVE);
    }

    /**
     * Build query with standard relationships
     */
    public function buildBaseQuery(): Builder
    {
        return Opportunity::with(['user']);
    }
}
