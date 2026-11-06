<?php

declare(strict_types=1);

namespace App\Domains\User\Services\Invitation;

use App\Domains\User\Models\UserInvitation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvitationRepository
{
    public function __construct(
        private readonly InvitationQueryBuilder $queryBuilder
    ) {}

    /**
     * Get paginated invitations with search and sorting
     */
    public function getPaginated(array $searchFilters = [], array $sortFilters = []): LengthAwarePaginator
    {
        $query = $this->queryBuilder->buildBaseQuery();
        $query = $this->queryBuilder->applySearchFilters($query, $searchFilters);
        $query = $this->queryBuilder->applySorting($query, $sortFilters);

        return $this->queryBuilder->applyPagination($query, $sortFilters);
    }

    /**
     * Get invitation statistics
     *
     * @return array{total: int, pending: int, accepted: int, expired: int}
     */
    public function getStatistics(): array
    {
        $total = UserInvitation::count();
        $accepted = UserInvitation::whereNotNull('accepted_at')->count();
        $pending = UserInvitation::whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->count();
        $expired = UserInvitation::whereNull('accepted_at')
            ->where('expires_at', '<=', now())
            ->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'accepted' => $accepted,
            'expired' => $expired,
        ];
    }

    /**
     * Find invitation by ID
     */
    public function findById(int $id): ?UserInvitation
    {
        return UserInvitation::with(['inviter'])->find($id);
    }
}
