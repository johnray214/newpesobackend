<?php

namespace App\Http\Controllers\Api\Jobseeker;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;

class JobseekerEventRegistrationController extends Controller
{
    /**
     * IDs of events the current jobseeker is registered for (for client UI).
     */
    public function registeredEventIds(Request $request)
    {
        $ids = EventRegistration::query()
            ->where('jobseeker_id', $request->user()->id)
            ->pluck('event_id');

        return response()->json([
            'success' => true,
            'data' => $ids->values()->all(),
        ]);
    }

    public function register(Request $request, int $id)
    {
        $jobseeker = $request->user();
        $event = Event::query()->findOrFail($id);
        $raw = $event->getRawOriginal('status');

        if ($raw === 'cancelled' || $raw === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'This event is not open for registration.',
            ], 422);
        }

        if (EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('jobseeker_id', $jobseeker->id)
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are already registered for this event.',
            ], 422);
        }

        $count = $event->registrations()->count();
        if ($event->max_participants !== null && $count >= $event->max_participants) {
            return response()->json([
                'success' => false,
                'message' => 'This event is full.',
            ], 422);
        }

        EventRegistration::create([
            'event_id' => $event->id,
            'jobseeker_id' => $jobseeker->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registered successfully.',
            'data' => [
                'participants_count' => $count + 1,
            ],
        ]);
    }

    public function unregister(Request $request, int $id)
    {
        $deleted = EventRegistration::query()
            ->where('event_id', $id)
            ->where('jobseeker_id', $request->user()->id)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'You are not registered for this event.',
            ], 422);
        }

        $event = Event::query()->find($id);
        $remaining = $event ? $event->registrations()->count() : 0;

        return response()->json([
            'success' => true,
            'message' => 'Registration cancelled.',
            'data' => [
                'participants_count' => $remaining,
            ],
        ]);
    }
}
