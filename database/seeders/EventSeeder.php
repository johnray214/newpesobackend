<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $events = [
            [
                'title'            => 'Job Fair 2026 - Santiago City',
                'description'      => 'Annual job fair connecting employers and jobseekers across Isabela. Bring your resume and be ready for on-the-spot interviews.',
                'type'             => 'Job Fair',
                'location'         => 'Santiago City Sports Complex, Santiago City, Isabela',
                'event_date'       => now()->addDays(14)->toDateString(),
                'start_time'       => '08:00:00',
                'end_time'         => '17:00:00',
                'organizer'        => 'PESO Santiago City',
                'max_participants' => 500,
                'status'           => 'upcoming',
                'created_by'       => $admin?->id,
            ],
            [
                'title'            => 'Free Skills Training: Basic Computer Literacy',
                'description'      => 'A free training program covering basic computer skills, Microsoft Office, and internet safety for unemployed residents.',
                'type'             => 'Training',
                'location'         => 'PESO Office, City Hall, Santiago City',
                'event_date'       => now()->addDays(7)->toDateString(),
                'start_time'       => '09:00:00',
                'end_time'         => '16:00:00',
                'organizer'        => 'PESO Santiago City',
                'max_participants' => 30,
                'status'           => 'upcoming',
                'created_by'       => $admin?->id,
            ],
            [
                'title'            => 'Resume Writing Workshop',
                'description'      => 'Learn how to craft a standout resume and prepare for job interviews with guidance from PESO career coaches.',
                'type'             => 'Workshop',
                'location'         => 'Isabela State University - Santiago Campus',
                'event_date'       => now()->toDateString(),
                'start_time'       => '09:00:00',
                'end_time'         => '16:00:00',
                'organizer'        => 'PESO in partnership with ISU',
                'max_participants' => 50,
                'status'           => 'ongoing',
                'created_by'       => $admin?->id,
            ],
            [
                'title'            => 'Overseas Employment Orientation (OWWA)',
                'description'      => 'Mandatory orientation for OFW applicants. Learn your rights, benefits, and what to expect when working abroad.',
                'type'             => 'Seminar',
                'location'         => 'DOLE Regional Office, Cauayan City',
                'event_date'       => now()->subDays(5)->toDateString(),
                'start_time'       => '08:30:00',
                'end_time'         => '15:00:00',
                'organizer'        => 'DOLE & OWWA',
                'max_participants' => 100,
                'status'           => 'completed',
                'created_by'       => $admin?->id,
            ],
            [
                'title'            => 'Livelihood Program: Soap Making for Beginners',
                'description'      => 'Hands-on training designed for homemakers interested in starting a small business making natural soaps and personal care products.',
                'type'             => 'Livelihood Program',
                'location'         => 'Barangay Hall, Mabini, Santiago City',
                'event_date'       => now()->addDays(20)->toDateString(),
                'start_time'       => '13:00:00',
                'end_time'         => '17:00:00',
                'organizer'        => 'PESO Livelihood Unit',
                'max_participants' => 25,
                'status'           => 'upcoming',
                'created_by'       => $admin?->id,
            ],
            [
                'title'            => 'BPO Industry Hiring Fair',
                'description'      => 'Top BPO companies from Metro Manila will be recruiting directly in Santiago City. Walk-in applicants are welcome.',
                'type'             => 'Job Fair',
                'location'         => 'Robinson\'s Place Event Center, Santiago City',
                'event_date'       => now()->addDays(2)->toDateString(),
                'start_time'       => '10:00:00',
                'end_time'         => '18:00:00',
                'organizer'        => 'PESO & Partner Agencies',
                'max_participants' => 300,
                'status'           => 'upcoming',
                'created_by'       => $admin?->id,
            ],
            [
                'title'            => 'Financial Literacy for Freelancers',
                'description'      => 'A seminar discussing budgeting, tax filing, and smart saving strategies for freelancers, gig workers, and self-employed individuals.',
                'type'             => 'Seminar',
                'location'         => 'Online via Zoom',
                'event_date'       => now()->subDays(15)->toDateString(),
                'start_time'       => '14:00:00',
                'end_time'         => '17:00:00',
                'organizer'        => 'Department of Trade and Industry (DTI)',
                'max_participants' => 200,
                'status'           => 'completed',
                'created_by'       => $admin?->id,
            ]
        ];

        foreach ($events as $eventData) {
            Event::updateOrCreate(
                ['title' => $eventData['title'], 'event_date' => $eventData['event_date']],
                $eventData
            );
        }
    }
}
