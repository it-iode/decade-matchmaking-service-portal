<?php

declare(strict_types=1);

namespace App\Domains\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtpVerifyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $codeLength = (int) config('one-time-passwords.password_length', 6);

        return [
            'code' => [
                'required',
                'string',
                "size:{$codeLength}",
                "regex:/^\d{{$codeLength}}$/",
            ],
            'email' => ['sometimes', 'string', 'email'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $codeLength = (int) config('one-time-passwords.password_length', 6);

        return [
            'code.required' => 'Please enter the OTP code.',
            'code.size' => "The OTP code must be exactly {$codeLength} digits.",
            'code.regex' => 'The OTP code must contain only numbers.',
        ];
    }
}
