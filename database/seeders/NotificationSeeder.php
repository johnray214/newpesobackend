<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $notifications = [
            // ── Notifications for Jobseekers ──────────────────────────────
            [
                'subject'    => 'Job Fair 2026 — Register Now!',
                'message'    => 'PESO Santiago City is hosting a major Job Fair on April 15, 2026 at the City Gym. Over 30 employers will be present. Register early to secure your slot and bring copies of your resume.',
                'recipients' => 'jobseekers',
                'status'     => 'sent',
                'sent_at'    => now()->subDays(5),
            ],
            [
                'subject'    => 'Free TESDA Livelihood Training — Limited Slots',
                'message'    => 'Free vocational training on Food Processing and Bread Making is available this April. Training is 100% free and includes a TESDA NC II assessment. Slots are limited to 30 participants.',
                'recipients' => 'jobseekers',
                'status'     => 'sent',
                'sent_at'    => now()->subDays(3),
            ],
            [
                'subject'    => 'Profile Verification Reminder',
                'message'    => 'Your profile is incomplete. A complete profile increases your chances of being found by employers. Please upload your resume and fill in your skills, education, and work experience.',
                'recipients' => 'jobseekers',
                'status'     => 'sent',
                'sent_at'    => now()->subDays(7),
            ],
            [
                'subject'    => 'New Job Openings This Week',
                'message'    => 'New job listings have been posted this week in IT, Agriculture, and Construction sectors. Log in to your PESO account to view and apply to jobs that match your skills.',
                'recipients' => 'jobseekers',
                'status'     => 'sent',
                'sent_at'    => now()->subDays(2),
            ],
            [
                'subject'    => 'PESO Upskilling Webinar — Digital Skills for the Modern Workforce',
                'message'    => 'Join us online on April 20, 2026 for a free webinar on digital tools for job seekers. Topics include resume writing, LinkedIn optimization, and online job application tips. Register via your PESO portal.',
                'recipients' => 'jobseekers',
                'status'     => 'sent',
                'sent_at'    => now()->subDay(),
            ],

            // ── Notifications for Employers ───────────────────────────────
            [
                'subject'    => 'New Application Received',
                'message'    => 'A jobseeker has applied to your Web Developer listing. Check new applicants in your dashboard.',
                'recipients' => 'employers',
                'status'     => 'sent',
                'sent_at'    => now()->subMinutes(44),
            ],
            [
                'subject'    => 'Job listing expiring',
                'message'    => 'Your job listing for Graphic Designer is expiring soon. Renew it to continue receiving applications.',
                'recipients' => 'employers',
                'status'     => 'sent',
                'sent_at'    => now()->subDays(1),
            ],
            [
                'subject'    => 'New High Match applicant',
                'message'    => 'A new applicant matches 95% of your requirements for Sales Associate. Review their profile now.',
                'recipients' => 'employers',
                'status'     => 'sent',
                'sent_at'    => now()->subDays(2),
            ],
        ];

        $employers = \App\Models\Employer::pluck('id');
        $jobseekers = \App\Models\Jobseeker::pluck('id');

        foreach ($notifications as $data) {
            $notification = Notification::create(array_merge($data, [
                'created_by'   => $admin?->id,
                'scheduled_at' => null,
            ]));

            // Distribute to recipients
            if ($notification->recipients === 'employers' || $notification->recipients === 'all') {
                foreach ($employers as $empId) {
                    NotificationRead::create([
                        'notification_id' => $notification->id,
                        'recipient_type'  => 'employer',
                        'recipient_id'    => $empId,
                        'read_at'         => null,
                    ]);
                }
            }
            if ($notification->recipients === 'jobseekers' || $notification->recipients === 'all') {
                foreach ($jobseekers as $jsId) {
                    NotificationRead::create([
                        'notification_id' => $notification->id,
                        'recipient_type'  => 'jobseeker',
                        'recipient_id'    => $jsId,
                        'read_at'         => null,
                    ]);
                }
            }
        }
    }
}
