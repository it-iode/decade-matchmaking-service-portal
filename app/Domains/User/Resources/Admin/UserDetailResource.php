<?php

namespace App\Domains\User\Resources\Admin;

use Illuminate\Http\Request;

class UserDetailResource extends UserResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'statistics' => $this->when(isset($this->resource->statistics), $this->resource->statistics ?? []),
            'activity' => $this->when(isset($this->resource->activity), $this->resource->activity ?? []),
        ]);
    }
}
