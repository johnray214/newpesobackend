<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\NotificationRead;
use Illuminate\Http\Request;

class EmployerNotificationController extends Controller
{
    /**
     * Infer a notification type from its subject line.
     */
    private function inferType(string $subject): string
    {
        $s = strtolower($subject);
        if (str_contains($s, 'match') || str_contains($s, 'potential')) return 'match';
        if (str_contains($s, 'applicant') || str_contains($s, 'application') || str_contains($s, 'applied') || str_contains($s, 'withdrew')) return 'applicant';
        if (str_contains($s, 'job') || str_contains($s, 'listing') || str_contains($s, 'expir')) return 'job';
        return 'system';
    }

    /**
     * Format a NotificationRead record for API responses.
     */
    private function formatNotification(NotificationRead $nr): array
    {
        $n = $nr->notification;
        return [
            'id'         => $nr->id,
            'type'       => $this->inferType($n?->subject ?? ''),
            'title'      => $n?->subject ?? 'Notification',
            'message'    => $n?->message ?? '',
            'read'       => $nr->read_at !== null,
            'read_at'    => $nr->read_at,
            'created_at' => $nr->created_at,
        ];
    }

    public function index(Request $request)
    {
        $employer = $request->user();

        $query = NotificationRead::where('recipient_type', 'employer')
            ->where('recipient_id', $employer->id)
            ->with('notification');

        if ($request->has('is_read')) {
            if ($request->is_read) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        $paginated = $query->orderByDesc('created_at')->paginate(50);

        $items = $paginated->getCollection()->map(fn ($nr) => $this->formatNotification($nr));

        return response()->json([
            'success' => true,
            'data'    => [
                'data'         => $items,
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $employer = $request->user();

        $nr = NotificationRead::where('recipient_type', 'employer')
            ->where('recipient_id', $employer->id)
            ->with('notification')
            ->findOrFail($id);

        $nr->markAsRead();

        return response()->json([
            'success' => true,
            'data'    => $this->formatNotification($nr),
        ]);
    }

    /**
     * Mark a single notification as read (POST route to avoid {id} conflict).
     */
    public function markRead(Request $request, $id)
    {
        $employer = $request->user();

        $nr = NotificationRead::where('recipient_type', 'employer')
            ->where('recipient_id', $employer->id)
            ->with('notification')
            ->findOrFail($id);

        $nr->markAsRead();

        return response()->json([
            'success' => true,
            'data'    => $this->formatNotification($nr),
        ]);
    }

    public function unreadCount(Request $request)
    {
        $employer = $request->user();

        $count = NotificationRead::where('recipient_type', 'employer')
            ->where('recipient_id', $employer->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data'    => ['unread_count' => $count],
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $employer = $request->user();

        NotificationRead::where('recipient_type', 'employer')
            ->where('recipient_id', $employer->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }
}
