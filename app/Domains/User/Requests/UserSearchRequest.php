<?php

namespace App\Domains\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('administrator');
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }
}
