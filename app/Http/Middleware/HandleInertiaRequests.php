<?php

namespace App\Http\Middleware;

use App\Domains\User\Resources\UserResource;
use App\Shared\Support\NavigationService;
use Database\Seeders\SampleDataSeeder;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     *
     * @throws \Throwable
     */
    public function share(Request $request): array
    {
        $navigationService = app(NavigationService::class);

        $user = $request->user();
        if ($user && ! $user->hasAnyRole(['partner', 'user'])) {
            session()->flash('info', 'Contact cdf@unesco.org to request a User or Partner role and unlock additional functions.');
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->toResource(UserResource::class) ?? null,
            ],
            'navigation' => $request->user()
                ? $navigationService->getNavigationItems($request->user())
                : null,
            'unread_notifications' => $request->user()?->notifications()->where('is_read', false)->count() ?? 0,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
            'breadcrumbs' => Breadcrumbs::generate(Route::currentRouteName()),
            'devTools' => [
                'sampleData' => SampleDataSeeder::isAllowedEnvironment() && (bool) $user?->hasRole('administrator'),
            ],
        ];
    }
}
