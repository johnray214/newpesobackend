<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jobseeker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminJobseekerController extends Controller
{
    public function index(Request $request)
    {
        $query = Jobseeker::query();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobseekers = $query->withCount('applications')->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $jobseekers,
        ]);
    }

    public function show($id)
    {
        $jobseeker = Jobseeker::with(['skills', 'applications.jobListing'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $jobseeker,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $jobseeker = Jobseeker::findOrFail($id);
        $jobseeker->update($validated);

        return response()->json([
            'success' => true,
            'data' => $jobseeker,
            'message' => 'Jobseeker status updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $jobseeker = Jobseeker::findOrFail($id);
        $jobseeker->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jobseeker deleted successfully',
        ]);
    }
}
