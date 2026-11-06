<?php

namespace App\Domains\User\Controllers\Admin;

use App\Domains\User\Models\User;
use App\Domains\User\Requests\AssignRolesRequest;
use App\Domains\User\Requests\BlockUserRequest;
use App\Domains\User\Requests\UserSearchRequest;
use App\Domains\User\Resources\Admin\UserResource;
use App\Domains\User\Services\UserExportService;
use App\Domains\User\Services\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Display users grid
     *
     * @throws Throwable
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $searchFilters = $request->only(['search', 'status', 'role']);
        $sortFilters = $request->only(['sort', 'direction', 'per_page']);

        $users = $this->userService->getUsersPaginated($searchFilters, $sortFilters);
        $users->toResourceCollection(UserResource::class);

        return Inertia::render('admin/User/Index', [
            'title' => 'User Management',
            'users' => $users,
            'filters' => $searchFilters,
            'sort' => $sortFilters,
            'availableRoles' => Role::all()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => ucfirst($role->name),
            ]),
            'statusOptions' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'blocked', 'label' => 'Blocked'],
                ['value' => 'unverified', 'label' => 'Unverified'],
            ],
        ]);
    }

    /**
     * Assign roles to user
     */
    public function assignRoles(AssignRolesRequest $request, User $user)
    {

        try {
            Gate::authorize('assignRoles', $user);
            $user = $this->userService->assignRoles($user, $request->validated()['roles']);

            return to_route('admin.users.index')->with('success', 'Roles updated successfully');
        } catch (\Exception $exception) {
            return to_route('admin.users.index')->with('error', 'Failed to update user roles, '.$exception->getMessage());
        }
    }

    /**
     * Block/unblock user
     *
     * @throws Throwable
     */
    public function toggleBlock(BlockUserRequest $request, User $user): RedirectResponse
    {
        try {
            Gate::authorize('block', $user);

            $data = $request->validated();
            $user = $this->userService->toggleBlockStatus($user, $data['blocked']);
            $action = $data['blocked'] ? 'blocked' : 'unblocked';

            return to_route('admin.users.index')->with('success', "User $action successfully");
        } catch (\Exception $exception) {
            return to_route('admin.users.index')->with('error', 'Failed to update user block status, '.$exception->getMessage());
        }

    }

    /**
     * Search users by name or email for autocomplete
     */
    public function search(UserSearchRequest $request): JsonResponse
    {
        $query = $request->validated('query');
        $users = $this->userService->searchUsers($query, 20);

        return response()->json([
            'data' => UserResource::collection($users),
        ]);
    }

    public function exportCsv(UserExportService $exportService): StreamedResponse|RedirectResponse
    {
        try {
            Gate::authorize('exportUsers', User::class);

            return $exportService->exportUsersCsv();
        } catch (\Exception $exception) {
            return to_route('admin.users.index')->with('error', 'Failed to export users, '.$exception->getMessage());
        }
    }
}
