<?php

declare(strict_types=1);

namespace App\Domains\Offer\Services;

use App\Domains\Document\Enums\DocumentType;
use App\Domains\Document\Models\Document;
use App\Domains\Document\Services\DocumentService;
use App\Domains\Offer\Enums\RequestOfferStatus;
use App\Domains\Offer\Events\OfferAccepted;
use App\Domains\Offer\Models\Offer;
use App\Domains\Request\Models\Request;
use App\Domains\User\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OfferService
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly OfferRepository $repository
    ) {}

    /**
     * Get paginated offers with search and sorting.
     */
    public function getPaginatedOffers(array $searchFilters = [], array $sortFilters = []): AbstractPaginator
    {
        return $this->repository->getPaginated($searchFilters, $sortFilters)->withQueryString();
    }

    /**
     * Get the active offer for a request.
     */
    public function getActiveOfferForRequest(Request $request): ?Offer
    {
        return $this->repository->findActiveOfferForRequest($request->id);
    }

    /**
     * Create a new offer
     *
     * @throws Throwable
     */
    public function createOffer(array $data, User $user): Offer
    {
        DB::beginTransaction();

        try {
            // Validate that user can create offers for this request
            $request = Request::findOrFail($data['request_id']);

            if (! $user->hasRole('administrator') && ! $user->hasRole('partner')) {
                throw new Exception('Only administrators and partners can create offers');
            }

            if ($user->id === $request->user_id && ! $user->hasRole('administrator')) {
                throw new Exception('Cannot create offer for your own request');
            }

            // Create the offer
            $offer = $this->repository->create([
                'request_id' => $data['request_id'],
                'matched_partner_id' => $data['partner_id'] ?? $user->id,
                'description' => $data['description'],
                'status' => RequestOfferStatus::INACTIVE,
            ]);

            // Handle document upload if provided
            if (isset($data['document']) && $data['document']) {
                $this->documentService->storeDocumentForOffer(
                    $data['document'],
                    DocumentType::OFFER_DOCUMENT->value,
                    $offer,
                    $user
                );
            }

            DB::commit();

            return $offer->load(['request', 'matchedPartner', 'documents']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create offer: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing offer
     *
     * @throws Throwable
     */
    public function updateOffer(int $offerId, array $data, User $user): Offer
    {
        DB::beginTransaction();
        try {
            $offer = $this->repository->findById($offerId);
            if (! $offer) {
                throw new Exception('Offer not found');
            }
            // Update offer data
            $updateData = [];
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }
            $this->repository->update($offer, $updateData);
            // Handle document upload if provided
            if (isset($data['document']) && $data['document']) {
                $this->documentService->storeDocumentForOffer(
                    $data['document'],
                    DocumentType::OFFER_DOCUMENT->value,
                    $offer,
                    $user,
                );
            }
            DB::commit();

            return $offer->load(['request', 'matchedPartner', 'documents']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update offer: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete an offer
     *
     * @throws Throwable
     */
    public function deleteOffer(int $offerId): bool
    {
        DB::beginTransaction();

        try {
            $offer = $this->repository->findById($offerId);
            if (! $offer) {
                throw new Exception('Offer not found');
            }

            // Delete associated documents
            foreach ($offer->documents as $document) {
                $this->documentService->deleteDocument($document);
            }

            // Delete the offer
            $this->repository->delete($offer);
            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete offer: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get offer by ID with authorization check
     */
    public function getOfferById(int $offerId): Offer
    {
        return $this->repository->findByIdWithRelations($offerId);
    }

    /**
     * Change offer status
     *
     * @throws Exception
     */
    public function changeOfferStatus(Offer $offer, RequestOfferStatus $status): Offer
    {
        if ($status == RequestOfferStatus::ACTIVE) {
            $activeOffer = $offer->request->activeOffer;
            if ($activeOffer) {
                throw new Exception('Another active offer already exists for this request');
            }
        }
        $this->repository->update($offer, ['status' => $status]);

        return $offer;
    }

    /**
     * Accept an offer
     *
     * @throws Throwable
     */
    public function acceptOffer(Offer $offer, User $acceptedBy): Offer
    {
        DB::beginTransaction();

        try {
            // Validate that the user can accept this offer
            if ($offer->request->user_id !== $acceptedBy->id) {
                throw new Exception('Only the request owner can accept offers for their request');
            }

            // Validate that the offer is in a state that can be accepted
            if ($offer->status !== RequestOfferStatus::ACTIVE) {
                throw new Exception('Only active offers can be accepted');
            }

            if ($offer->is_accepted) {
                throw new Exception('This offer has already been accepted');
            }

            // Update the offer to accepted status
            $this->repository->update($offer, [
                'is_accepted' => true,
            ]);
            DB::commit();
            OfferAccepted::dispatch($offer, $acceptedBy);

            return $offer->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to accept offer: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload a document for an offer
     *
     * @throws Throwable
     */
    public function uploadDocument(UploadedFile $file, Offer $offer, string $documentType, User $uploader): Document
    {
        DB::beginTransaction();

        try {
            // Store the new document
            $document = $this->documentService->storeDocumentForOffer(
                $file,
                $documentType,
                $offer,
                $uploader
            );

            DB::commit();

            Log::info('Document uploaded for offer', [
                'offer_id' => $offer->id,
                'document_id' => $document->id,
                'document_type' => $documentType,
                'uploader_id' => $uploader->id,
            ]);

            return $document;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to upload document for offer: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a document from an offer
     *
     * @throws Throwable
     */
    public function deleteDocument(Document $document): bool
    {
        DB::beginTransaction();

        try {
            $deleted = $this->documentService->deleteDocument($document);

            DB::commit();

            Log::info('Document deleted from offer', [
                'document_id' => $document->id,
                'offer_id' => $document->parent_id,
            ]);

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete document from offer: '.$e->getMessage());
            throw $e;
        }
    }
}
