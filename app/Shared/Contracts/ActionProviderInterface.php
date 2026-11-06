<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Domains\User\Models\User;

/**
 * Interface for action providers that determine available actions for entities.
 */
interface ActionProviderInterface
{
    /**
     * Get available actions for the entity.
     *
     * @param  mixed  $entity  The entity to get actions for
     * @param  User|null  $user  The user to check permissions for
     * @param  string|null  $context  The context (admin, user, list, detail, etc.)
     * @return array<int, array<string, mixed>> Array of action arrays
     */
    public function getActions(mixed $entity, ?User $user = null, ?string $context = null): array;
}
