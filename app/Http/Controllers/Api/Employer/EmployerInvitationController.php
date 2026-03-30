<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\Jobseeker;
use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;

class EmployerInvitationController extends Controller
{
    public function sendInvitation(Request $request, $jobseekerId)
    {
        $employer = $request->user();

        $validated = $request->validate([
            'job_listing_id' => 'required|integer|exists:job_listings,id',
        ]);

        // Ensure the job listing belongs to this employer
        $job = JobListing::where('id', $validated['job_listing_id'])
            ->where('employer_id', $employer->id)
            ->firstOrFail();

        // Ensure the jobseeker exists and is active
        $jobseeker = Jobseeker::with('skills')
            ->where('id', $jobseekerId)
            ->where('status', 'active')
            ->firstOrFail();

        $company = $employer->company_name ?? 'An employer';

        $notification = Notification::create([
            'subject'        => "You've Been Personally Invited!",
            'message'        => "{$company} has personally invited you to explore and apply for the {$job->title} position.",
            'type'           => 'invitation',
            'job_listing_id' => $job->id,
            'recipients'     => 'specific',
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

        // Attempt to send email via Mailjet
        try {
            $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
            
            $applyUrl  = env('FRONTEND_URL', 'http://localhost:5173') . '/jobseeker/jobs/' . $job->id;
            $topSkills = $jobseeker->skills ? $jobseeker->skills->pluck('skill')->take(3)->implode(', ') : 'your listed skills';

            $body = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => env('MAILJET_FROM_EMAIL', 'peso@posuechague.site'),
                            'Name'  => env('MAILJET_FROM_NAME', 'PESO')
                        ],
                        'To' => [
                            [
                                'Email' => $jobseeker->email,
                                'Name'  => trim($jobseeker->first_name . ' ' . $jobseeker->last_name)
                            ]
                        ],
                        'TemplateID'       => 7869914,
                        'TemplateLanguage' => true,
                        'Subject'          => "You're Invited to Apply — {$job->title} at {$company}",
                        'Variables'        => [
                            'first_name'       => $jobseeker->first_name,
                            'company_name'     => $company,
                            'job_title'        => $job->title,
                            'job_location'     => $job->location ?? 'On-site',
                            'employment_type'  => $job->type ?? 'Full-time',
                            'salary_range'     => $job->salary_range ?? 'Negotiable',
                            'work_setup'       => $job->work_setup ?? 'On-site',
                            'match_pct'        => 'High',
                            'top_skills'       => $topSkills ?: 'your listed skills',
                            'apply_url'        => $applyUrl,
                        ],
                    ]
                ]
            ];
            
            $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
            
            if (!$response->success()) {
                \Illuminate\Support\Facades\Log::error('Mailjet API Error Invitation Email: ' . json_encode($response->getData()));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Mailjet Exception Invitation Email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully.',
        ]);
    }
}
