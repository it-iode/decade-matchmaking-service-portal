<?php

declare(strict_types=1);

namespace App\Domains\Document\Resources;

use App\Domains\Document\Actions\DocumentActionProvider;
use App\Domains\Document\Models\Document;
use App\Domains\User\Resources\UserResource;
use App\Shared\Http\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Document
 */
class DocumentResource extends BaseResource
{
    protected function actionProvider(): ?string
    {
        return DocumentActionProvider::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function fields(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'file_type' => $this->file_type,
            'document_type' => [
                'value' => $this->document_type?->value,
                'label' => $this->document_type?->label(),
            ],
            'parent_id' => $this->parent_id,
            'parent_type' => $this->parent_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Computed properties
            'file_size' => $this->getFileSize(),
            'file_size_human' => $this->getFileSizeHuman(),
            'download_url' => $this->getDownloadUrl(),
            'file_extension' => $this->getFileExtension(),

            // Relationships
            'uploader' => $this->whenLoaded('uploader', function () {
                return new UserResource($this->uploader);
            }),
        ];
    }

    /**
     * Get the file size in bytes.
     */
    private function getFileSize(): int
    {
        try {
            if (Storage::disk('public')->exists($this->path)) {
                return Storage::disk('public')->size($this->path);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the response
            \Log::warning('Failed to get file size for document', [
                'document_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

        return 0;
    }

    /**
     * Get human-readable file size.
     */
    private function getFileSizeHuman(): string
    {
        $bytes = $this->getFileSize();

        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));

        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Get the download URL for the document.
     */
    private function getDownloadUrl(): string
    {
        return route('offer.documents.download', [
            'id' => $this->parent_id,
            'document' => $this->id,
        ]);
    }

    /**
     * Get the file extension.
     */
    private function getFileExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }
}
