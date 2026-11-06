<?php

namespace App\Domains\Request\Controllers;

use App\Domains\Request\Services\RequestContextService;
use App\Domains\Request\Services\RequestService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RequestManagementController extends BaseRequestController
{
    public function __construct(
        protected readonly RequestService $service,
        RequestContextService $contextService
    ) {
        parent::__construct($contextService);
    }

    /**
     * Update request status - unified method for both admin and user contexts
     */
    public function updateStatus(Request $httpRequest, ?int $id = null)
    {
        try {
            $validated = $httpRequest->validate([
                'status_code' => 'required|string|exists:request_statuses,status_code',
            ]);
            $request = $this->service->getRequestById($id);
            Gate::authorize('update-status', $request);
            $result = $this->service->updateRequestStatus(
                $request,
                $validated['status_code']
            );

            $message = 'Request status updated successfully';

            if ($this->isAdminRoute()) {
                return $this->getSuccessResponse($message, 'admin.request.list');
            }

            // For non-admin routes, return JSON with updated status
            return response()->json([
                'message' => $message,
                'status' => $result['request']->status,
            ]);
        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;

            if ($this->isAdminRoute()) {
                return $this->getErrorResponse($e->getMessage(), $statusCode, 'admin.request.list');
            }

            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    private function getSuccessResponse(string $message, ?string $redirectRoute = null)
    {
        if ($this->isAdminRoute() && $redirectRoute) {
            return to_route($redirectRoute)->with('success', $message);
        }

        // Default JSON response for non-admin routes
        return response()->json(['message' => $message]);
    }

    /**
     * Get error response based on context
     */
    private function getErrorResponse(string $message, int $statusCode = 400, ?string $redirectRoute = null)
    {
        if ($this->isAdminRoute() && $redirectRoute) {
            return to_route($redirectRoute)->with('error', $message);
        }

        // Default JSON response for non-admin routes
        return response()->json(['error' => $message], $statusCode);
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $requestId = (int) $request->route('id');
            $this->service->deleteRequest($requestId, $request->user());

            return back()->with('success', 'Request deleted successfully.');
        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;

            return back()->with('error', 'Request deletion failed: ');
        }
    }
}
