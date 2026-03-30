<?php

namespace App\Http\Controllers\Api\Jobseeker;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobseekerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $jobseeker = $request->user();
        
        $query = Application::where('jobseeker_id', $jobseeker->id);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->with([
                'jobListing:id,title,type,location,salary_range,description,slots,deadline,posted_date,created_at,employer_id',
                'jobListing.employer:id,company_name',
                'jobListing.skills:id,job_listing_id,skill',
            ])
            ->orderByDesc('applied_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }

    public function show(Request $request, $id)
    {
        $jobseeker = $request->user();
        
        $application = Application::where('jobseeker_id', $jobseeker->id)
            ->with(['jobListing.employer'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_listing_id' => 'required|exists:job_listings,id',
        ]);

        $jobseeker = $request->user();
        $jobListing = JobListing::findOrFail($validated['job_listing_id']);

        // Check if already applied
        $existing = Application::where('job_listing_id', $jobListing->id)
            ->where('jobseeker_id', $jobseeker->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied for this job',
            ], 422);
        }

        // Check if job is open
        if (!$jobListing->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'This job is no longer open for applications',
            ], 422);
        }

        // Calculate match score
        $matchScore = Application::calculateMatchScore($jobseeker, $jobListing);

        $application = Application::create([
            'job_listing_id' => $jobListing->id,
            'jobseeker_id' => $jobseeker->id,
            'status' => 'reviewing',
            'match_score' => $matchScore,
            'applied_at' => now(),
        ]);

        // Create notification for jobseekers: Registration (first time applying)
        $notification = Notification::create([
            'subject'    => 'Application submitted',
            'message'    => "Your application for {$jobListing->title} at {$jobListing->employer->company_name} has been received and is under review.",
            'recipients' => 'jobseekers',
            'scheduled_at' => null,
            'sent_at'    => now(),
            'status'     => 'sent',
            'created_by' => null,
        ]);

        NotificationRead::create([
            'notification_id' => $notification->id,
            'recipient_type'  => 'jobseeker',
            'recipient_id'    => $jobseeker->id,
            'read_at'         => null,
        ]);

        // Create notification for Employer: New Applicant
        $employerNotification = Notification::create([
            'subject'        => 'New Job Applicant',
            'message'        => "{$jobseeker->fullName()} has submitted an application for the {$jobListing->title} position.",
            'type'           => 'applicant',
            'job_listing_id' => $jobListing->id,
            'recipients'     => 'specific',
            'scheduled_at'   => null,
            'sent_at'        => now(),
            'status'         => 'sent',
            'created_by'     => null,
        ]);

        NotificationRead::create([
            'notification_id' => $employerNotification->id,
            'recipient_type'  => 'employer',
            'recipient_id'    => $jobListing->employer_id,
            'read_at'         => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $application,
            'message' => 'Application submitted successfully',
        ], 201);
    }

    public function withdraw(Request $request, $id)
    {
        $jobseeker = $request->user();
        
        $application = Application::where('jobseeker_id', $jobseeker->id)
            ->whereIn('status', ['reviewing', 'shortlisted'])
            ->findOrFail($id);
        
        $application->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application withdrawn successfully',
        ]);
    }
}
