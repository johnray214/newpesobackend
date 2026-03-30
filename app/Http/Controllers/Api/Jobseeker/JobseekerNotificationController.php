<?php

namespace App\Http\Controllers\Api\Jobseeker;

use App\Http\Controllers\Controller;
use App\Models\NotificationRead;
use Illuminate\Http\Request;

class JobseekerNotificationController extends Controller
{
    public function index(Request $request)
    {
        $jobseeker = $request->user();
        
        $query = NotificationRead::where('recipient_type', 'jobseeker')
            ->where('recipient_id', $jobseeker->id)
            ->with([
                'notification',
                'notification.jobListing',
                'notification.jobListing.employer',
                'notification.jobListing.skills',
            ]);
        
        if ($request->has('is_read')) {
            if ($request->is_read) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        $notifications = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function show(Request $request, $id)
    {
        $jobseeker = $request->user();
        
        $notificationRead = NotificationRead::where('recipient_type', 'jobseeker')
            ->where('recipient_id', $jobseeker->id)
            ->with('notification')
            ->findOrFail($id);
        
        // Mark as read when viewed
        $notificationRead->markAsRead();
        
        return response()->json([
            'success' => true,
            'data' => $notificationRead,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $jobseeker = $request->user();
        
        $count = NotificationRead::where('recipient_type', 'jobseeker')
            ->where('recipient_id', $jobseeker->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count],
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $jobseeker = $request->user();
        
        NotificationRead::where('recipient_type', 'jobseeker')
            ->where('recipient_id', $jobseeker->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $jobseeker = $request->user();

        $notificationRead = NotificationRead::where('recipient_type', 'jobseeker')
            ->where('recipient_id', $jobseeker->id)
            ->findOrFail($id);

        $notificationId = $notificationRead->notification_id;
        $notificationRead->delete();

        // If no other reads reference this notification, hard delete the notification itself
        if (!NotificationRead::where('notification_id', $notificationId)->exists()) {
            \App\Models\Notification::where('id', $notificationId)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    public function destroyAllRead(Request $request)
    {
        $jobseeker = $request->user();

        // Collect all notification_ids for this jobseeker before deleting
        $notificationIds = NotificationRead::where('recipient_type', 'jobseeker')
            ->where('recipient_id', $jobseeker->id)
            ->pluck('notification_id')
            ->toArray();

        NotificationRead::where('recipient_type', 'jobseeker')
            ->where('recipient_id', $jobseeker->id)
            ->delete();

        // For each notification, if no reads remain anywhere, delete the notification row
        if (!empty($notificationIds)) {
            $uniqueIds = array_unique($notificationIds);
            foreach ($uniqueIds as $nid) {
                if (!NotificationRead::where('notification_id', $nid)->exists()) {
                    \App\Models\Notification::where('id', $nid)->delete();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'All notifications deleted',
        ]);
    }
}
