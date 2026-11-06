<?php

namespace App\Domains\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('administrator');
    }

    public function rules(): array
    {
        $availableRoles = Role::pluck('name')->toArray();

        return [
            'roles' => ['array'],
            'roles.*' => ['required', 'string', 'in:'.implode(',', $availableRoles)],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'At least one role must be assigned.',
            'roles.*.in' => 'Invalid role selected.',
        ];
    }
}
