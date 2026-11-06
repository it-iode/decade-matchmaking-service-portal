<?php

namespace App\Domains\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('administrator');
    }

    public function rules(): array
    {
        return [
            'blocked' => ['required', 'boolean'],
        ];
    }
}
