<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminJobListingController extends Controller
{
    public function index(Request $request)
    {
        $query = JobListing::query();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('employer_id')) {
            $query->where('employer_id', $request->employer_id);
        }

        $jobListings = $query->with('employer:id,company_name')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $jobListings,
        ]);
    }

    public function show($id)
    {
        $jobListing = JobListing::with(['employer', 'skills'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $jobListing,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'closed', 'draft'])],
        ]);

        $jobListing = JobListing::findOrFail($id);
        $jobListing->update($validated);

        return response()->json([
            'success' => true,
            'data' => $jobListing,
            'message' => 'Job listing status updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $jobListing = JobListing::findOrFail($id);
        $jobListing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job listing deleted successfully',
        ]);
    }
}
