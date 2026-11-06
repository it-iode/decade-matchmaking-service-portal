<?php

declare(strict_types=1);

namespace App\Domains\Document\Actions;

use App\Domains\Document\Models\Document;
use App\Domains\Offer\Models\Offer;
use App\Domains\Request\Models\Request;
use App\Domains\User\Models\User;
use App\Shared\Contracts\ActionProviderInterface;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Provides available actions for documents.
 */
class DocumentActionProvider implements ActionProviderInterface
{
    /**
     * Get available actions for a document.
     *
     * @param  mixed  $entity  The document entity
     * @param  User|null  $user  The current user
     * @param  string|null  $context  The UI context (admin, user, etc.)
     * @return array<int, array<string, mixed>>
     */
    public function getActions(mixed $entity, ?User $user = null, ?string $context = null): array
    {
        if (! $entity instanceof Document) {
            return [];
        }

        $actions = [];

        // Download action - for users who can view the document
        if ($user && $this->canDownload($entity, $user)) {
            $actions[] = [
                'key' => 'download',
                'label' => 'Download',
                'route' => route('offer.documents.download', [
                    'id' => $entity->parent_id,
                    'document' => $entity->id,
                ]),
                'method' => 'GET',
                'enabled' => true,
                'style' => [
                    'color' => 'blue',
                    'icon' => 'arrow-down-tray',
                    'variant' => 'outline',
                ],
                'metadata' => [
                    'open_in_new_tab' => true,
                ],
            ];
        }

        // Delete action - for users who can delete the document
        if ($user && $this->canDelete($entity, $user)) {
            $actions[] = [
                'key' => 'delete',
                'label' => 'Delete',
                'route' => route('offer.documents.destroy', [
                    'id' => $entity->parent_id,
                    'document' => $entity->id,
                ]),
                'method' => 'DELETE',
                'enabled' => true,
                'style' => [
                    'color' => 'red',
                    'icon' => 'trash',
                    'variant' => 'outline',
                ],
                'confirm' => 'Are you sure you want to delete this document? This action cannot be undone.',
            ];
        }

        return array_values($actions);
    }

    /**
     * Check if the user can download the document.
     */
    private function canDownload(Document $document, User $user): bool
    {
        // Document uploader can always download
        if ($document->uploader_id === $user->id) {
            return true;
        }

        // Request owner can download
        if ($document->parent_type === Relation::getMorphAlias(Request::class)) {
            $parent = Request::find($document->parent_id);
            if ($parent && $parent->user_id === $user->id) {
                return true;
            }
        }

        // Offer partner can download
        if ($document->parent_type === Relation::getMorphAlias(Offer::class)) {
            $parent = Offer::find($document->parent_id);
            if ($parent && $parent->matched_partner_id === $user->id) {
                return true;
            }
        }

        // Admin can always download
        if ($user->hasRole('administrator')) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user can delete the document.
     */
    private function canDelete(Document $document, User $user): bool
    {
        // Document uploader can delete their own documents
        if ($document->uploader_id === $user->id) {
            return true;
        }

        // Admin can delete any document
        if ($user->hasRole('administrator')) {
            return true;
        }

        // Request owner can delete documents attached to their request
        if ($document->parent_type === Relation::getMorphAlias(Request::class)) {
            $parent = Request::find($document->parent_id);
            if ($parent && $parent->user_id === $user->id) {
                return true;
            }
        }

        return false;
    }
}
