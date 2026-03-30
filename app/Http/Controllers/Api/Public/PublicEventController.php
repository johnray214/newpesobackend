<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query()->withCount(['registrations as participants_count']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        // Only show non-cancelled events by default (uses DB column, not accessor)
        $status = $request->get('status');
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['upcoming', 'ongoing']);
        }

        $events = $query
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function show(Request $request, $id)
    {
        $event = Event::query()
            ->withCount(['registrations as participants_count'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }
}

