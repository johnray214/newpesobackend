<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $notifications = $query->with('creator:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function show($id)
    {
        $notification = Notification::with('creator')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $notification,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'recipients' => ['required', Rule::in(['jobseekers', 'employers', 'specific'])],
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = $request->has('scheduled_at') ? 'scheduled' : 'draft';
        
        $notification = Notification::create($validated);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification created successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        
        if ($notification->status === 'sent') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update sent notification',
            ], 400);
        }
        
        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
            'recipients' => ['sometimes', Rule::in(['jobseekers', 'employers', 'specific'])],
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['status'] = $request->has('scheduled_at') ? 'scheduled' : 'draft';
        
        $notification->update($validated);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification updated successfully',
        ]);
    }

    public function send($id)
    {
        $notification = Notification::findOrFail($id);
        
        if ($notification->status === 'sent') {
            return response()->json([
                'success' => false,
                'message' => 'Notification already sent',
            ], 400);
        }
        
        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Create notification reads for recipients
        if ($notification->recipients === 'jobseekers') {
            $jobseekers = \App\Models\Jobseeker::where('status', 'active')->get();
            foreach ($jobseekers as $jobseeker) {
                NotificationRead::create([
                    'notification_id' => $notification->id,
                    'recipient_type' => 'jobseeker',
                    'recipient_id' => $jobseeker->id,
                ]);
            }
        } elseif ($notification->recipients === 'employers') {
            $employers = \App\Models\Employer::where('status', 'approved')->get();
            foreach ($employers as $employer) {
                NotificationRead::create([
                    'notification_id' => $notification->id,
                    'recipient_type' => 'employer',
                    'recipient_id' => $employer->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully',
        ]);
    }

    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        
        if ($notification->status === 'sent') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete sent notification',
            ], 400);
        }
        
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ]);
    }
}
