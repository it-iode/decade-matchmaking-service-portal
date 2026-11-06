<?php

declare(strict_types=1);

namespace App\Domains\Offer\Requests;

use Illuminate\Validation\Rules\File;

class UploadDocumentRequest extends BaseOfferRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $type = $this->route('type');

        return [
            'document' => [
                'required',
                'file',
                File::types($this->getAllowedMimeTypes($type))
                    ->max($this->getMaxFileSizeKB($type)),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    protected function getCustomMessages(): array
    {
        return [
            'document.required' => 'Please select a document to upload.',
            'document.file' => 'The uploaded file is invalid.',
        ];
    }

    /**
     * Get allowed MIME types based on document type.
     */
    private function getAllowedMimeTypes(string $type): array
    {
        return match ($type) {
            'financial_breakdown' => ['pdf', 'xlsx', 'xls', 'csv'],
            'offer_document' => ['pdf', 'docx', 'doc', 'xlsx', 'xls'],
            default => ['pdf']
        };
    }

    /**
     * Get max file size in KB based on document type.
     */
    private function getMaxFileSizeKB(string $type): int
    {
        return match ($type) {
            'financial_breakdown' => 10 * 1024,  // 10MB
            'offer_document' => 10 * 1024,       // 10MB
            default => 5 * 1024                   // 5MB
        };
    }
}
