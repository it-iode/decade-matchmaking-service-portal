<?php

namespace App\Domains\Request\Controllers;

use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\Request\Enums\DeliveryFormat;
use App\Domains\Request\Enums\ProjectStage;
use App\Domains\Request\Enums\RelatedActivity;
use App\Domains\Request\Enums\SupportType;
use App\Domains\Request\Events\RequestSubmitted;
use App\Domains\Request\Requests\StoreRequest;
use App\Domains\Request\Resources\RequestResource;
use App\Domains\Request\Services\RequestContextService;
use App\Domains\Request\Services\RequestService;
use App\Shared\Enums\Country;
use App\Shared\Enums\Language;
use App\Domains\Request\Enums\TargetAudience;
use App\Shared\Enums\YesNo;
use App\Shared\Http\HasPageActions;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RequestFormController extends BaseRequestController
{
    use HasPageActions;

    public function __construct(
        private readonly RequestService $service,
        RequestContextService $contextService
    ) {
        parent::__construct($contextService);
    }

    /**
     * Show the form for creating a new resource or editing an existing one.
     */
    public function form(?int $id = null): Response|RedirectResponse
    {
        $actions = $this->buildActions([
            $this->createPrimaryAction('List of my requests', route('request.create'), 'arrowLongLeft'),
        ]);

        // Check if this is edit mode (ID provided) or create mode (no ID)
        $isEditMode = ! is_null($id);
        if ($isEditMode) {
            $request = $this->service->findRequest($id);
            // Edit mode - fetch the existing request
            if (! $request) {
                return to_route('request.me.list')->with('error', 'Request not found.');
            }
            // Only the owner may open the edit form, and only while editable.
            Gate::authorize('update', $request);
        }
        $data = [
            'formOptions' => [
                'decade_challenges' => DecadeChallenge::getOptions(),
                'support_types' => SupportType::getOptions(),
                'related_activity' => RelatedActivity::getOptions(),
                'delivery_format' => DeliveryFormat::getOptions(),
                'target_audience' => TargetAudience::getOptions(),
                'target_languages' => Language::getOptions(),
                'delivery_countries' => Country::getOptions(),
                'project_stage' => ProjectStage::getOptions(),
                'yes_no' => YesNo::getOptions(),
            ],
        ];
        if ($isEditMode) {
            $requestTitle = $request->detail?->capacity_development_title ?? 'Untitled';
            $data = array_merge($data, [
                'title' => 'Request : '.$requestTitle,
                'banner' => $this->buildBanner(
                    'Request : '.$requestTitle,
                    'Edit my request details here.'
                ),
                'request' => new RequestResource($request, RequestContextService::CONTEXT_USER_OWN),
            ]);
        } else {
            // Create mode - new request
            $data = array_merge($data, [
                'title' => 'Create a new request',
                'banner' => [
                    'title' => 'Create a new request',
                    'description' => 'Create a new request to get started.',
                    'image' => '/assets/img/sidebar.png',
                ],
                'actions' => $actions,
            ]);
        }

        return Inertia::render('request/Create', $data);
    }

    /**
     * Handle all form submissions (store, submit, draft)
     */
    public function submit(StoreRequest $request, ?int $id = null): RedirectResponse
    {
        // Resolve and authorize the target BEFORE the try, so an authorization
        // failure surfaces as a 403 rather than being swallowed into an error flash.
        $existing = null;
        if ($id !== null) {
            $existing = $this->service->findRequest($id);
            if (! $existing) {
                abort(404);
            }
            Gate::authorize('update', $existing);
        }

        $mode = $request->input('mode', 'submit');
        try {
            $request = $this->service->storeRequest(
                $request->user(),
                $request->validated(),
                $existing,
                $mode
            );
            if ($mode == 'submit') {
                RequestSubmitted::dispatch($request);

                return to_route('request.me.list')->with('success', 'Request submitted successfully.');
            }

            return to_route('request.edit', ['id' => $request->id])->with(
                'success',
                'Request draft saved successfully.'
            );
        } catch (Exception $e) {
            if ($id) {
                return to_route('request.edit', ['id' => $id])->with('error', $e->getMessage());
            }

            return to_route('request.create')->with('error', $e->getMessage());
        }
    }
}
