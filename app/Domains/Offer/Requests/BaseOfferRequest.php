<?php

namespace App\Domains\Offer\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base form request for offer-related operations
 *
 * Provides common functionality for offer form requests:
 * - Authorization checks
 * - Common validation messages and attributes
 * - Consistent error response formatting
 * - Context-aware response handling (JSON vs redirect)
 */
abstract class BaseOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get common validation messages for offer fields
     */
    protected function getCommonMessages(): array
    {
        return [
            'description.required' => 'The offer description is required.',
            'description.min' => 'The offer description must be at least :min characters.',
            'description.max' => 'The offer description may not be greater than :max characters.',
            'partner_id.required' => 'The partner ID is required.',
            'partner_id.exists' => 'The selected partner does not exist.',
            'request_id.required' => 'The request is required.',
            'request_id.exists' => 'The selected request does not exist.',
            'document.required' => 'A document file is required.',
            'document.file' => 'The document must be a valid file.',
            'document.mimes' => 'The document must be a PDF file.',
            'document.max' => 'The document may not be greater than :max kilobytes.',
        ];
    }

    /**
     * Get common attributes for validator errors
     */
    protected function getCommonAttributes(): array
    {
        return [
            'description' => 'offer description',
            'partner_id' => 'partner',
            'request_id' => 'request',
            'document' => 'document file',
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return array_merge($this->getCommonMessages(), $this->getCustomMessages());
    }

    /**
     * Get custom attributes for validator errors
     */
    public function attributes(): array
    {
        return array_merge($this->getCommonAttributes(), $this->getCustomAttributes());
    }

    /**
     * Override this method to provide additional custom messages
     */
    protected function getCustomMessages(): array
    {
        return [];
    }

    /**
     * Override this method to provide additional custom attributes
     */
    protected function getCustomAttributes(): array
    {
        return [];
    }

    /**
     * Handle a failed validation attempt with context-aware responses
     */
    protected function failedValidation(Validator $validator)
    {
        // Check if this is an admin route or API request
        if ($this->isAdminRoute()) {
            // For admin routes, use standard Laravel behavior (redirect with errors)
            parent::failedValidation($validator);
        }

        // For API routes, return JSON response
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt with context-aware responses
     */
    protected function failedAuthorization()
    {
        if ($this->isAdminRoute()) {
            // For admin routes, use standard Laravel behavior (403 error)
            parent::failedAuthorization();
        }

        // For API routes, return JSON response
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'error' => 'You are not authorized to perform this action.',
            ], 403)
        );
    }

    /**
     * Check if the current route is an admin route
     */
    protected function isAdminRoute(): bool
    {
        return $this->route() && str_starts_with($this->route()->getName() ?? '', 'admin.');
    }
}
