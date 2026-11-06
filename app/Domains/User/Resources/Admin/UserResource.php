<?php

namespace App\Domains\User\Resources\Admin;

use App\Domains\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'country' => $user->country,
            'city' => $user->city,
            'roles' => $user->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ]),
            'permissions' => $user->permissions->pluck('name'),
            'is_blocked' => $user->is_blocked ?? false,
            'email_verified' => ! is_null($user->email_verified_at),
            'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
            'created_at' => $user->created_at->toDateTimeString(),
            'updated_at' => $user->updated_at->toDateTimeString(),
            'requests_count' => $this->whenCounted('requests'),
            'notifications_count' => $this->whenCounted('notifications'),
            'avatar_url' => $user->getAvatarUrl(),
            'is_social_user' => $user->isSocialUser(),
            'provider' => $user->provider,
            'status' => $this->getStatusAttribute(),
        ];
    }

    private function getStatusAttribute(): array
    {
        if ($this->is_blocked) {
            return [
                'value' => 'blocked',
                'label' => 'Blocked',
                'color' => 'red',
            ];
        }

        if (is_null($this->email_verified_at)) {
            return [
                'value' => 'unverified',
                'label' => 'Unverified',
                'color' => 'yellow',
            ];
        }

        return [
            'value' => 'active',
            'label' => 'Active',
            'color' => 'green',
        ];
    }
}
