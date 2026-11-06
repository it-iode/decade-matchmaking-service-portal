<?php

declare(strict_types=1);

namespace App\Domains\User\Controllers\Admin;

use App\Domains\User\Enums\Invitation\Status;
use App\Domains\User\Models\User;
use App\Domains\User\Models\UserInvitation;
use App\Domains\User\Requests\SendInvitationRequest;
use App\Domains\User\Resources\Admin\InvitationResource;
use App\Domains\User\Services\Invitation\InvitationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService
    ) {}

    /**
     * Display a listing of invitations
     */
    public function index(Request $request): Response
    {
        Gate::authorize('invite', User::class);

        $searchFilters = $request->only(['name', 'email', 'status', 'inviter', 'search']);
        $sortFilters = [
            'field' => $request->input('sort', 'created_at'),
            'order' => $request->input('order', 'desc'),
            'per_page' => $request->input('per_page', 15),
        ];

        $invitations = $this->invitationService->getInvitationsPaginated($searchFilters, $sortFilters);
        $invitations->toResourceCollection(InvitationResource::class);
        $statistics = $this->invitationService->getStatistics();

        return Inertia::render('admin/Invitation/List', [
            'invitations' => $invitations,
            'statistics' => $statistics,
            'searchFields' => $this->getSearchFields(),
            'currentSort' => [
                'field' => $sortFilters['field'],
                'order' => $sortFilters['order'],
            ],
            'currentSearch' => array_filter($searchFilters),
        ]);
    }

    /**
     * Get search field configurations
     */
    private function getSearchFields(): array
    {
        return [
            [
                'id' => 'name',
                'type' => 'text',
                'label' => 'Name',
                'placeholder' => 'Search by invitee name...',
            ],
            [
                'id' => 'email',
                'type' => 'text',
                'label' => 'Email',
                'placeholder' => 'Search by email...',
            ],
            [
                'id' => 'status',
                'type' => 'select',
                'label' => 'Status',
                'placeholder' => 'All statuses',
                'options' => Status::getOptions(),
            ],
            [
                'id' => 'inviter',
                'type' => 'text',
                'label' => 'Invited By',
                'placeholder' => 'Search by inviter...',
            ],
        ];
    }

    /**
     * Send invitation to a new user
     */
    public function store(SendInvitationRequest $request): RedirectResponse
    {
        Gate::authorize('invite', User::class);

        $validated = $request->validated();

        try {
            $this->invitationService->invite(
                $validated['email'],
                $validated['name'],
                $request->user()
            );

            return back()->with('success', "Invitation sent to {$validated['email']}");
        } catch (Throwable $e) {
            Log::error('Failed to send invitation', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to send invitation. Please try again.');
        }
    }

    /**
     * Resend an existing invitation
     */
    public function resend(UserInvitation $invitation): RedirectResponse
    {
        Gate::authorize('invite', User::class);

        if ($invitation->isAccepted()) {
            return back()->with('error', 'This invitation has already been accepted.');
        }

        try {
            $this->invitationService->resend($invitation);

            return back()->with('success', "Invitation resent to {$invitation->email}");
        } catch (Throwable $e) {
            Log::error('Failed to resend invitation', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to resend invitation. Please try again.');
        }
    }

    /**
     * Cancel/delete an invitation
     */
    public function destroy(UserInvitation $invitation): RedirectResponse
    {
        Gate::authorize('invite', User::class);

        if ($invitation->isAccepted()) {
            return back()->with('error', 'Cannot cancel an accepted invitation.');
        }

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled.');
    }
}
