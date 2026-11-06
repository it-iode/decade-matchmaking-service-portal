<?php

namespace App\Domains\Offer\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreRequestOffer extends BaseOfferRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'description' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'partner_id' => [
                'required',
            ],
            'document' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240', // 10MB max
            ],
        ];
    }

    /**
     * Get additional custom messages specific to store operations
     */
    protected function getCustomMessages(): array
    {
        return [
            'partner_id.min' => 'The partner ID must be at least :min characters.',
            'partner_id.max' => 'The partner ID may not be greater than :max characters.',
            'partner_id.regex' => 'The partner ID may only contain letters, numbers, hyphens, underscores, and dots.',
        ];
    }

    /**
     * Get additional custom attributes specific to store operations
     */
    protected function getCustomAttributes(): array
    {
        return [
            'partner_id' => 'partner ID',
        ];
    }
}
