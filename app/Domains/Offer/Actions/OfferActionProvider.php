<?php

declare(strict_types=1);

namespace App\Domains\Offer\Actions;

use App\Domains\Offer\Enums\RequestOfferStatus;
use App\Domains\Offer\Models\Offer;
use App\Domains\User\Models\User;
use App\Shared\Contracts\ActionProviderInterface;

/**
 * Provides available actions for request offers.
 */
class OfferActionProvider implements ActionProviderInterface
{
    /**
     * Get available actions for an offer.
     *
     * @param  mixed  $entity  The offer entity
     * @param  User|null  $user  The current user
     * @param  string|null  $context  The UI context (admin, user, etc.)
     * @return array<int, array<string, mixed>>
     */
    public function getActions(mixed $entity, ?User $user = null, ?string $context = null): array
    {
        if (! $entity instanceof Offer) {
            return [];
        }
        $actions = [];
        $request = $entity->request;
        // Accept Offer - for request owner
        if ($user && $request && $user->can('accept', $entity)) {
            $actions[] = [
                'key' => 'accept_offer',
                'label' => 'Accept Offer',
                'route' => route('offer.accept', ['id' => $entity->id]),
                'method' => 'POST',
                'enabled' => true,
                'style' => [
                    'color' => 'green',
                    'icon' => 'check',
                    'variant' => 'solid',
                ],
                'confirm' => 'Are you sure you want to accept this offer?',
            ];
        }

        if ($user && $user->can('requestClarifications', $entity)) {
            $actions[] = [
                'key' => 'request_clarifications',
                'label' => 'Request Clarifications',
                'route' => route('offer.clarification-request', ['id' => $entity->id]),
                'method' => 'POST',
                'enabled' => true,
                'style' => [
                    'color' => 'blue',
                    'icon' => 'question-mark-circle',
                    'variant' => 'outline',
                ],
            ];
        }

        if ($user && $user->can('uploadFinancialBreakDown', $entity)) {
            $actions[] = [
                'key' => 'upload_financial_breakdown',
                'label' => 'Upload Financial Breakdown report',
                'route' => route('offer.documents.upload', ['id' => $entity->id, 'type' => 'financial_breakdown']),
                'method' => 'POST',
                'enabled' => true,
                'style' => [
                    'color' => 'blue',
                    'icon' => 'document-arrow-up',
                    'variant' => 'outline',
                ],
                'metadata' => [
                    'handler' => 'dialog',
                    'dialog_component' => 'FileUploadDialogProps',
                    'dialog_props' => [
                        'accept' => '.pdf,.xlsx,.xls,.csv',
                        'maxSize' => 10,  // MB
                        'multiple' => false,
                        'endpoint' => route('offer.documents.upload', ['id' => $entity->id, 'type' => 'financial_breakdown']),
                        'documentType' => 'financial_breakdown',
                        'title' => 'Upload Financial Breakdown',
                        'description' => 'Please upload a detailed financial breakdown document (PDF, Excel, or CSV format)',
                        'validationRules' => [
                            'required' => true,
                            'mimeTypes' => ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'],
                            'maxSizeBytes' => 10485760,  // 10MB
                        ],
                    ],
                ],
            ];
        }
        if ($user && $user->can('uploadLessonLearned', $entity)) {
            $actions[] = [
                'key' => 'upload_lesson_learned',
                'label' => 'Upload Lesson Learned Report',
                'route' => route('offer.documents.upload', ['id' => $entity->id, 'type' => 'lesson_learned']),
                'method' => 'POST',
                'enabled' => true,
                'style' => [],
                'metadata' => [
                    'handler' => 'dialog',
                    'dialog_component' => 'FileUploadDialogProps',
                    'dialog_props' => [
                        'accept' => '.pdf,.xlsx,.xls,.csv',
                        'maxSize' => 10,  // MB
                        'multiple' => false,
                        'endpoint' => route('offer.documents.upload', ['id' => $entity->id, 'type' => 'lesson_learned']),
                        'documentType' => 'lesson_learned',
                        'title' => 'Upload Lesson Learned report',
                        'description' => 'Please Upload Lesson Learned report document (PDF, Excel, or CSV format)',
                        'validationRules' => [
                            'required' => true,
                            'mimeTypes' => ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'],
                            'maxSizeBytes' => 10485760,  // 10MB
                        ],
                    ],
                ],
            ];
        }

        // Admin actions
        if ($context === 'admin' && $user) {
            // Edit Offer
            if ($user->can('view', $entity)) {
                $actions[] = [
                    'key' => 'view',
                    'label' => 'View details',
                    'route' => route('admin.offer.show', ['id' => $entity->id]),
                    'method' => 'GET',
                    'enabled' => true,
                    'style' => [],
                ];
            }
            // Edit Offer
            if ($user->can('update', $entity)) {
                $actions[] = [
                    'key' => 'edit',
                    'label' => 'Edit Offer',
                    'route' => route('admin.offer.edit', ['id' => $entity->id]),
                    'method' => 'GET',
                    'enabled' => true,
                    'style' => [],
                ];
            }

            // Enable/Disable Offer
            if ($user->can('canEnableOrDisable', $entity)) {
                $isActive = $entity->status === RequestOfferStatus::ACTIVE;
                $targetStatus = $isActive ? RequestOfferStatus::INACTIVE : RequestOfferStatus::ACTIVE;

                $actions[] = [
                    'key' => $isActive ? 'disable' : 'enable',
                    'label' => $isActive ? 'Disable Offer' : 'Enable Offer',
                    'route' => route('admin.offer.update-status', ['id' => $entity->id]),
                    'method' => 'POST',
                    'data' => ['status' => $targetStatus->value],
                    'enabled' => true,
                    'style' => [],
                ];
            }

            // Delete Offer
            if ($user->can('delete', $entity)) {
                $actions[] = [
                    'key' => 'delete',
                    'label' => 'Delete Offer',
                    'route' => route('admin.offer.destroy', ['id' => $entity->id]),
                    'method' => 'DELETE',
                    'enabled' => true,
                    'style' => [],
                    'confirm' => 'This action cannot be undone. Are you sure you want to delete this offer?',
                ];
            }
            if ($user->can('view', $entity->request)) {
                $actions[] = [
                    'key' => 'view_request',
                    'label' => 'View Offer Request',
                    'route' => route('admin.request.show', ['id' => $entity->request->id]),
                    'method' => 'GET',
                    'enabled' => true,
                    'style' => [],
                ];
            }

        }

        return array_values($actions);
    }
}
