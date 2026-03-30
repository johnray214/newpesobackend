<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::query();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('jobseeker', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('job_listing_id')) {
            $query->where('job_listing_id', $request->job_listing_id);
        }
        
        if ($request->has('jobseeker_id')) {
            $query->where('jobseeker_id', $request->jobseeker_id);
        }

        $applications = $query->with(['jobseeker.skills', 'jobListing.employer'])
            ->orderByDesc('applied_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }

    public function show($id)
    {
        $application = Application::with(['jobseeker.skills', 'jobListing'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['reviewing', 'shortlisted', 'interview', 'hired', 'rejected'])],
        ]);

        $application = Application::with(['jobseeker', 'jobListing.employer'])->findOrFail($id);

        $oldStatus = $application->status;
        $application->update($validated);

        $newStatus = $application->status;

        // Create jobseeker notification when status actually changes
        if ($newStatus !== $oldStatus) {
            $jobseeker = $application->jobseeker;
            $job       = $application->jobListing;
            $company   = $job->employer->company_name ?? 'Employer';

            switch ($newStatus) {
                case 'reviewing':
                    $subject = 'Application received';
                    $message = "Your application for {$job->title} at {$company} has been received and is under review.";
                    break;
                case 'shortlisted':
                case 'interview':
                    $subject = 'Application in process';
                    $message = "Your application for {$job->title} at {$company} is now being processed.";
                    break;
                case 'hired':
                    $subject = 'Application successful';
                    $message = "Congratulations! You have been hired for {$job->title} at {$company}.";
                    break;
                case 'rejected':
                    $subject = 'Application update';
                    $message = "Your application for {$job->title} at {$company} was not selected. Please consider applying to other opportunities.";
                    break;
                default:
                    $subject = 'Application update';
                    $message = "There is an update to your application for {$job->title} at {$company}.";
            }

            $notification = Notification::create([
                'subject'      => $subject,
                'message'      => $message,
                'recipients'   => 'jobseekers',
                'scheduled_at' => null,
                'sent_at'      => now(),
                'status'       => 'sent',
                'created_by'   => $request->user()->id ?? null,
            ]);

            NotificationRead::create([
                'notification_id' => $notification->id,
                'recipient_type'  => 'jobseeker',
                'recipient_id'    => $jobseeker->id,
                'read_at'         => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $application,
            'message' => 'Application status updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $application = Application::findOrFail($id);
        $application->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application deleted successfully',
        ]);
    }
       public function reviewingCount()
    {
        $count = \App\Models\Application::where('status', 'reviewing')->count();
        return response()->json(['count' => $count]);
    }
}
