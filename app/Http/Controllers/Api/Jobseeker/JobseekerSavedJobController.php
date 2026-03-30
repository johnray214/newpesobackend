<?php

namespace App\Http\Controllers\Api\Jobseeker;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\JobseekerSavedJob;
use Illuminate\Http\Request;

class JobseekerSavedJobController extends Controller
{
    public function index(Request $request)
    {
        $jobseeker = $request->user();

        $saved = JobseekerSavedJob::where('jobseeker_id', $jobseeker->id)
            ->with([
                'jobListing:id,title,type,location,salary_range,description,slots,deadline,posted_date,created_at,employer_id',
                'jobListing.employer:id,company_name',
                'jobListing.skills:id,job_listing_id,skill',
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $saved,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_listing_id' => 'required|exists:job_listings,id',
        ]);

        $jobseeker = $request->user();
        $jobListing = JobListing::findOrFail($validated['job_listing_id']);

        $existing = JobseekerSavedJob::where('jobseeker_id', $jobseeker->id)
            ->where('job_listing_id', $jobListing->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Job already saved',
            ]);
        }

        $saved = JobseekerSavedJob::create([
            'jobseeker_id' => $jobseeker->id,
            'job_listing_id' => $jobListing->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $saved,
            'message' => 'Job saved successfully',
        ], 201);
    }

    public function destroyByJobListing(Request $request, $jobListingId)
    {
        $jobseeker = $request->user();

        $saved = JobseekerSavedJob::where('jobseeker_id', $jobseeker->id)
            ->where('job_listing_id', $jobListingId)
            ->first();

        if (!$saved) {
            return response()->json([
                'success' => false,
                'message' => 'Saved job not found',
            ], 404);
        }

        $saved->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job removed from saved',
        ]);
    }
}

