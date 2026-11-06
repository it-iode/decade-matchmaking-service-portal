<?php

namespace App\Domains\Notification\Controllers\Admin;

use App\Domains\Notification\Models\SystemNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SystemNotificationsController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        // Validate sort order
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';

        $notifications = Auth::user()->notifications()
            ->orderBy($sortField, $sortOrder)->paginate(10)->appends($request->only(['sort', 'order']));

        return Inertia::render('admin/SystemNotification/List', [
            'title' => 'Notifications',
            'notifications' => $notifications,
            'currentSort' => [
                'field' => $sortField,
                'order' => $sortOrder,
            ],
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('user.home')],
                ['name' => 'Notifications'],
            ],
        ]);
    }

    public function show(SystemNotification $notification): Response
    {
        $this->authorizeNotification($notification);

        return Inertia::render('admin/SystemNotification/Show', [
            'title' => $notification->title,
            'notification' => $notification,
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('user.home')],
                ['name' => 'Notifications', 'url' => route('admin.notifications.index')],
                ['name' => $notification->title],
            ],
        ]);
    }

    public function markAsRead(SystemNotification $notification)
    {
        $this->authorizeNotification($notification);
        $notification->update(['is_read' => true]);

        return to_route('admin.notifications.index')->with('success', 'SystemNotification marked as read.');
    }

    private function authorizeNotification(SystemNotification $notification): void
    {
        abort_unless($notification->user_id === Auth::id(), 403);
    }
}
