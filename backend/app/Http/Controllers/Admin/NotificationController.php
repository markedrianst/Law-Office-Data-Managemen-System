<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotificationController
 *
 * Exposes the Laravel database notifications for the authenticated user.
 * Powers the bell icon in the header and the approval alerts.
 *
 * Routes (registered in api.php under /notifications):
 *   GET  /notifications              → index()       paginated list
 *   GET  /notifications/unread-count → unreadCount() lightweight badge
 *   PATCH /notifications/{id}/read  → markRead()     mark one as read
 *   POST /notifications/read-all    → readAll()      mark all as read
 */
class NotificationController extends Controller
{
    /** GET /notifications — paginated notification list */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'total'        => $notifications->total(),
                'unread'       => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    /** GET /notifications/unread-count — lightweight badge endpoint */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** PATCH /notifications/{id}/read — mark a single notification read */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /** POST /notifications/read-all — mark every unread notification read */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
