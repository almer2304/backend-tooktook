<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\RentalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    protected RentalNotificationService $notificationService;

    public function __construct(RentalNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        // Filter by read status
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $notifications = $query->paginate(15);

        return NotificationResource::collection($notifications);
    }

    /**
     * Get a specific notification
     */
    public function show(Notification $notification): NotificationResource
    {
        $this->authorize('view', $notification);
        return new NotificationResource($notification);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $this->notificationService->markAsRead($notification->id);

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => new NotificationResource($notification->refresh())
        ]);
    }

    /**
     * Mark all notifications as read for authenticated user
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $count
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $this->notificationService->deleteNotification($notification->id);

        return response()->json([
            'message' => 'Notification deleted successfully'
        ], 200);
    }

    /**
     * Get notification statistics for authenticated user
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->notificationService->getNotificationStats($request->user()->id);

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get unread notifications count (quick check)
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Delete all notifications for authenticated user
     */
    public function deleteAll(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'All notifications deleted successfully',
            'count' => $count
        ]);
    }
}
