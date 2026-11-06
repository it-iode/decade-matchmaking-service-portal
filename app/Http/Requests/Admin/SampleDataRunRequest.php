<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SampleDataRunRequest extends FormRequest
{
    public const CONFIRMATION_PHRASE = 'RESET';

    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('administrator');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'string', 'in:'.self::CONFIRMATION_PHRASE],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.required' => 'Type '.self::CONFIRMATION_PHRASE.' to confirm.',
            'confirmation.in' => 'Type '.self::CONFIRMATION_PHRASE.' exactly (uppercase) to confirm.',
        ];
    }
}
