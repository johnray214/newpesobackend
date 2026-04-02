<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $employer = $request->user();
        
        $query = Application::whereHas('jobListing', function ($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        });
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('jobseeker', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('job_title')) {
            $query->whereHas('jobListing', function ($q) use ($request) {
                $q->where('title', $request->job_title);
            });
        }
        
        if ($request->has('job_listing_id')) {
            $query->where('job_listing_id', $request->job_listing_id);
        }

        $applications = $query->with(['jobseeker' => fn($q) => $q->withTrashed()->with('skills'), 'jobListing'])
            ->orderByDesc('applied_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => ApplicationResource::collection($applications->items()),
            'meta'    => [
                'current_page' => $applications->currentPage(),
                'last_page'    => $applications->lastPage(),
                'total'        => $applications->total(),
                'per_page'     => $applications->perPage(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $employer = $request->user();
        
        $application = Application::whereHas('jobListing', function ($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })->with(['jobseeker' => fn($q) => $q->withTrashed()->with('skills'), 'jobListing'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    /**
     * Stream applicant resume PDF (must be an application to this employer's listing).
     */
    public function downloadResume(Request $request, $id)
    {
        $employer = $request->user();

        $application = Application::whereHas('jobListing', function ($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })->with(['jobseeker' => fn($q) => $q->withTrashed()])->findOrFail($id);

        $path = $application->jobseeker->resume_path;

        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, basename($path), [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $employer = $request->user();
        \Illuminate\Support\Facades\Log::info("UpdateStatus Payload:", $request->all());
        
        $validated = $request->validate([
            'status'     => ['required', Rule::in(['reviewing', 'shortlisted', 'interview', 'hired', 'rejected'])],
            'start_date' => ['nullable', 'date'],
        ]);

        $application = Application::whereHas('jobListing', function ($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })->with(['jobseeker' => fn($q) => $q->withTrashed(), 'jobListing', 'jobListing.employer'])->findOrFail($id);

        $oldStatus = $application->status;
        $application->update($validated);

        $newStatus = $application->status;

        // Only notify when status actually changes
        if ($newStatus !== $oldStatus) {
            $jobseeker = $application->jobseeker;
            $job       = $application->jobListing;
            $company   = $job->employer->company_name ?? 'Employer';

            switch ($newStatus) {
                case 'reviewing':
                    $subject = 'Application received';
                    $message = "Your application for **{$job->title}** at **{$company}** has been received and is under review.";
                    $type = 'status_reviewing';
                    break;
                case 'shortlisted':
                    $subject = 'You have been Shortlisted!';
                    $message = "Great news! You have been shortlisted for the **{$job->title}** position at **{$company}**. The employer will contact you soon for the next steps.";
                    $type = 'status_shortlisted';
                    
                    try {
                        $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
                        $body = [
                            'Messages' => [
                                [
                                    'From' => [ 'Email' => env('MAILJET_FROM_EMAIL'), 'Name' => env('MAILJET_FROM_NAME', 'PESO') ],
                                    'To' => [ [ 'Email' => $jobseeker->email, 'Name' => trim($jobseeker->first_name . ' ' . $jobseeker->last_name) ] ],
                                    'TemplateID' => 7861619,
                                    'TemplateLanguage' => true,
                                    'Subject' => 'You have been Shortlisted',
                                    'Variables' => [
                                        'first_name' => $jobseeker->first_name,
                                        'job_title' => $job->title,
                                        'company_name' => $company,
                                        'job_location' => $job->location ?? 'Not specified'
                                    ]
                                ]
                            ]
                        ];
                        $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                        if (!$response->success()) {
                            \Illuminate\Support\Facades\Log::error('Mailjet API Error Shortlisted Email: ' . json_encode($response->getData()));
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Mailjet Exception Shortlisted Email: ' . $e->getMessage());
                    }
                    break;
                case 'interview':
                    $subject = 'Interview Scheduled';
                    $message = "You have been scheduled for an interview for the **{$job->title}** position at **{$company}**. Please check your email for the full details of the schedule.";
                    $type = 'status_interview';

                    try {
                        $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
                        $body = [
                            'Messages' => [
                                [
                                    'From' => [ 'Email' => env('MAILJET_FROM_EMAIL'), 'Name' => env('MAILJET_FROM_NAME', 'PESO') ],
                                    'To' => [ [ 'Email' => $jobseeker->email, 'Name' => trim($jobseeker->first_name . ' ' . $jobseeker->last_name) ] ],
                                    'TemplateID' => 7861384,
                                    'TemplateLanguage' => true,
                                    'Subject' => 'Interview Scheduled',
                                    'Variables' => [
                                        'first_name' => $jobseeker->first_name,
                                        'company_name' => $company,
                                        'job_title' => $job->title,
                                        'interview_date' => !empty($request->input('interview_date')) ? \Carbon\Carbon::parse($request->input('interview_date'))->format('F d, Y') : 'TBA',
                                        'interview_time' => !empty($request->input('interview_time')) ? \Carbon\Carbon::parse($request->input('interview_time'))->format('h:i A') : 'TBA',
                                        'interview_format' => $request->input('interview_format') ?? 'In-person',
                                        'interview_location' => $request->input('interview_location') ?? 'TBA',
                                        'interviewer_name' => $request->input('interviewer_name') ?? 'Hiring Manager'
                                    ]
                                ]
                            ]
                        ];
                        $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                        if (!$response->success()) {
                            \Illuminate\Support\Facades\Log::error('Mailjet API Error Interview Email: ' . json_encode($response->getData()));
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Mailjet Exception Interview Email: ' . $e->getMessage());
                    }
                    break;
                case 'hired':
                    $subject = 'Congratulations — You are Hired!';
                    $message = "Outstanding! You have been officially hired for **{$job->title}** at **{$company}**. Welcome to the team!";
                    $type = 'status_hired';

                    try {
                        $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
                        $body = [
                            'Messages' => [
                                [
                                    'From' => [
                                        'Email' => env('MAILJET_FROM_EMAIL'),
                                        'Name'  => env('MAILJET_FROM_NAME', 'PESO')
                                    ],
                                    'To' => [
                                        [
                                            'Email' => $jobseeker->email,
                                            'Name'  => trim($jobseeker->first_name . ' ' . $jobseeker->last_name)
                                        ]
                                    ],
                                    'TemplateID'       => 7861483,
                                    'TemplateLanguage' => true,
                                    'Subject'          => 'Congratulations — You have been hired!',
                                    'Variables'        => [
                                        'first_name'      => $jobseeker->first_name,
                                        'company_name'    => $company,
                                        'job_title'       => $job->title,
                                        'start_date'      => !empty($request->input('start_date')) ? \Carbon\Carbon::parse($request->input('start_date'))->format('F d, Y') : 'To be discussed',
                                        'salary'          => $job->salary_range ?? 'Negotiable',
                                        'employment_type' => $job->job_type ?? 'Full-time'
                                    ]
                                ]
                            ]
                        ];
                        $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                        if (!$response->success()) {
                            \Illuminate\Support\Facades\Log::error('Mailjet API Error Hired Email: ' . json_encode($response->getData()));
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Mailjet Exception Hired Email: ' . $e->getMessage());
                    }
                    break;
                case 'rejected':
                    $subject = 'Application Update';
                    $message = "Thank you for your interest in the **{$job->title}** position at **{$company}**. Unfortunately, the employer has decided not to proceed with your application at this time.";
                    $type = 'status_rejected';

                    try {
                        $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
                        $body = [
                            'Messages' => [
                                [
                                    'From' => [ 'Email' => env('MAILJET_FROM_EMAIL'), 'Name' => env('MAILJET_FROM_NAME', 'PESO') ],
                                    'To' => [ [ 'Email' => $jobseeker->email, 'Name' => trim($jobseeker->first_name . ' ' . $jobseeker->last_name) ] ],
                                    'TemplateID' => 7865387,
                                    'TemplateLanguage' => true,
                                    'Subject' => 'Application Update: Not Selected',
                                    'Variables' => [
                                        'first_name' => $jobseeker->first_name,
                                        'company_name' => $company,
                                        'job_title' => $job->title,
                                        'update_date' => now()->format('F d, Y')
                                    ]
                                ]
                            ]
                        ];
                        $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                        if (!$response->success()) {
                            \Illuminate\Support\Facades\Log::error('Mailjet API Error Rejected Email: ' . json_encode($response->getData()));
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Mailjet Exception Rejected Email: ' . $e->getMessage());
                    }
                    break;
                default:
                    $subject = 'Application update';
                    $message = "There is an update to your application for **{$job->title}** at **{$company}**.";
                    $type = 'application_update';
            }

            $notification = Notification::create([
                'subject'        => $subject,
                'message'        => $message,
                'type'           => $type,
                'job_listing_id' => $job->id,
                'recipients'     => 'jobseekers',
                'scheduled_at'   => null,
                'sent_at'        => now(),
                'status'         => 'sent',
                'created_by'     => $employer->id,
            ]);

            NotificationRead::create([
                'notification_id' => $notification->id,
                'recipient_type'  => 'jobseeker',
                'recipient_id'    => $jobseeker->id,
                'read_at'         => null,
            ]);

            if (($newStatus === 'hired' || $newStatus === 'rejected') && !$jobseeker->has_received_satisfaction_survey) {
                $surveyNotification = Notification::create([
                    'subject'        => 'Rate your application experience',
                    'message'        => 'How satisfied are you with the application process on PESO Connect?',
                    'type'           => 'satisfaction_survey',
                    'recipients'     => 'jobseekers',
                    'scheduled_at'   => null,
                    'sent_at'        => now(),
                    'status'         => 'sent',
                    'created_by'     => $employer->id, // Or null if system
                ]);

                NotificationRead::create([
                    'notification_id' => $surveyNotification->id,
                    'recipient_type'  => 'jobseeker',
                    'recipient_id'    => $jobseeker->id,
                    'read_at'         => null,
                ]);

                $jobseeker->update(['has_received_satisfaction_survey' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $application,
            'message' => 'Application status updated successfully',
        ]);
    }

    public function potentialApplicants(Request $request)
    {
        $employer = $request->user();
        
        $jobListings = $employer->jobListings()->with('skills')->get();
        $jobSkills = $jobListings->pluck('skills')->flatten()->pluck('skill')->unique();
        
        $query = \App\Models\Jobseeker::with('skills')
            ->where('status', 'active')
            ->whereHas('skills', function ($q) use ($jobSkills) {
                $q->whereIn('skill', $jobSkills);
            });
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('skills', function ($sq) use ($search) {
                      $sq->where('skill', 'like', "%{$search}%");
                  });
            });
        }

        $jobseekers = $query->orderByDesc('created_at')->paginate(15);

        // Calculate match score and best matching job for each
        $jobseekers->getCollection()->transform(function ($jobseeker) use ($jobListings) {
            $maxScore = 0;
            $bestJob  = null;
            foreach ($jobListings as $job) {
                $score = \App\Models\Application::calculateMatchScore($jobseeker, $job);
                if ($score > $maxScore) {
                    $maxScore = $score;
                    $bestJob  = $job;
                }
            }
            $jobseeker->match_score    = $maxScore;
            $jobseeker->best_job_match = $bestJob?->title ?? null;
            $jobseeker->best_job_skills = $bestJob ? $bestJob->skills->pluck('skill')->values() : [];
            $jobseeker->best_job_id = $bestJob?->id ?? null;
            return $jobseeker;
        });

        return response()->json([
            'success' => true,
            'data' => $jobseekers,
        ]);
    }

    /**
     * Send a job invitation email to a potential applicant (jobseeker).
     */
    public function sendInvite(Request $request, $jobseekerId)
    {
        $employer = $request->user();

        $jobseeker = \App\Models\Jobseeker::with('skills')->findOrFail($jobseekerId);

        // Find the best-matching job for this jobseeker
        $jobListings = $employer->jobListings()->with('skills')->get();
        $maxScore = 0;
        $bestJob  = null;
        foreach ($jobListings as $job) {
            $score = \App\Models\Application::calculateMatchScore($jobseeker, $job);
            if ($score > $maxScore) { $maxScore = $score; $bestJob = $job; }
        }

        if (!$bestJob) {
            return response()->json(['success' => false, 'message' => 'No matching job listing found.'], 422);
        }

        $company   = $employer->company_name ?? 'Employer';
        $topSkills = $jobseeker->skills->pluck('skill')->take(3)->implode(', ');
        $applyUrl  = env('FRONTEND_URL', 'http://localhost:8080') . '/jobseeker/jobs/' . $bestJob->id;

        // Send Mailjet email
        try {
            $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
            $body = [
                'Messages' => [[
                    'From' => ['Email' => env('MAILJET_FROM_EMAIL'), 'Name' => env('MAILJET_FROM_NAME', 'PESO')],
                    'To'   => [['Email' => $jobseeker->email, 'Name' => $jobseeker->full_name]],
                    'TemplateID'       => 7869914,
                    'TemplateLanguage' => true,
                    'Subject'          => "You're Invited to Apply — {$bestJob->title} at {$company}",
                    'Variables'        => [
                        'first_name'       => $jobseeker->first_name,
                        'company_name'     => $company,
                        'job_title'        => $bestJob->title,
                        'job_location'     => $bestJob->location ?? 'On-site',
                        'employment_type'  => $bestJob->job_type ?? 'Full-time',
                        'salary_range'     => $bestJob->salary_range ?? 'Negotiable',
                        'work_setup'       => $bestJob->work_setup ?? 'On-site',
                        'match_pct'        => (string) round($maxScore),
                        'top_skills'       => $topSkills ?: 'your listed skills',
                        'apply_url'        => $applyUrl,
                    ],
                ]],
            ];
            $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
            if (!$response->success()) {
                \Illuminate\Support\Facades\Log::error('Mailjet Invite Error: ' . json_encode($response->getData()));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Mailjet Invite Exception: ' . $e->getMessage());
        }

        // Create notification for JOBSEEKER
        $jobseekerNotif = Notification::create([
            'subject'    => 'Job Invitation from ' . $company,
            'message'    => "You have been personally invited by {$company} to apply for the {$bestJob->title} position. Check your email for details.",
            'recipients' => 'jobseekers',
            'scheduled_at' => null,
            'sent_at'    => now(),
            'status'     => 'sent',
            'created_by' => $employer->id,
        ]);
        NotificationRead::create([
            'notification_id' => $jobseekerNotif->id,
            'recipient_type'  => 'jobseeker',
            'recipient_id'    => $jobseeker->id,
            'read_at'         => null,
        ]);

        // Create notification for EMPLOYER
        $employerNotif = Notification::create([
            'subject'    => 'Invitation Sent',
            'message'    => "You successfully invited {$jobseeker->full_name} to apply for {$bestJob->title}.",
            'recipients' => 'employers',
            'scheduled_at' => null,
            'sent_at'    => now(),
            'status'     => 'sent',
            'created_by' => $employer->id,
        ]);
        NotificationRead::create([
            'notification_id' => $employerNotif->id,
            'recipient_type'  => 'employer',
            'recipient_id'    => $employer->id,
            'read_at'         => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Invitation sent to {$jobseeker->full_name}",
        ]);
    }
}

