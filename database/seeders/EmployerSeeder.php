<?php

namespace Database\Seeders;

use App\Models\Employer;
use App\Models\JobListing;
use App\Models\JobSkill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployerSeeder extends Seeder
{
    // ── The skill pools used by each employer job listing ──────────────────
    // Jobseekers with overlapping skills become "potential applicants"
    public static array $jobsByEmployer = [
        'employer1@techcorp.com' => [
            [
                'title'               => 'Web Developer',
                'type'                => 'full-time',
                'salary_range'        => 'Above Minimum Wage',
                'education_level'     => 'college_graduate',
                'experience_required' => '2_years',
                'slots'               => 3,
                'days_back'           => 10,
                'days_ahead'          => 20,
                'description'         => 'We are looking for a skilled Web Developer to build and maintain modern web applications using PHP, Laravel, and Vue.js.',
                'skills'              => ['PHP', 'Laravel', 'JavaScript', 'Vue.js', 'MySQL'],
            ],
            [
                'title'               => 'UI/UX Designer',
                'type'                => 'full-time',
                'salary_range'        => 'Minimum Wage',
                'education_level'     => 'college_graduate',
                'experience_required' => '1_year',
                'slots'               => 2,
                'days_back'           => 5,
                'days_ahead'          => 25,
                'description'         => 'Join our creative team as a UI/UX Designer. You will create beautiful and intuitive interfaces for our products.',
                'skills'              => ['Figma', 'Adobe XD', 'CSS', 'Prototyping', 'User Research'],
            ],
            [
                'title'               => 'IT Support Technician',
                'type'                => 'full-time',
                'salary_range'        => 'Minimum Wage',
                'education_level'     => 'vocational',
                'experience_required' => 'less_than_1',
                'slots'               => 1,
                'days_back'           => 15,
                'days_ahead'          => 15,
                'description'         => 'Provide technical support to internal users. Troubleshoot hardware and software issues.',
                'skills'              => ['Networking', 'Windows OS', 'Hardware Troubleshooting', 'Linux'],
            ],
        ],
        'employer2@harvestfoods.com' => [
            [
                'title'               => 'Food Processing Worker',
                'type'                => 'full-time',
                'salary_range'        => 'Minimum Wage',
                'education_level'     => 'highschool',
                'experience_required' => 'fresh_grad',
                'slots'               => 10,
                'days_back'           => 3,
                'days_ahead'          => 27,
                'description'         => 'Work in our food processing facility. No experience required, training is provided on-site.',
                'skills'              => ['Food Safety', 'Quality Control', 'Packaging', 'Sanitation'],
            ],
            [
                'title'               => 'Delivery Driver',
                'type'                => 'full-time',
                'salary_range'        => 'Minimum Wage',
                'education_level'     => 'highschool',
                'experience_required' => '1_year',
                'slots'               => 3,
                'days_back'           => 7,
                'days_ahead'          => 23,
                'description'         => 'Deliver fresh goods to partner stores and markets across Isabela. Must have a valid driver\'s license.',
                'skills'              => ['Driving', 'Route Planning', 'Customer Service', 'Time Management'],
            ],
        ],
        'employer3@buildright.com' => [
            [
                'title'               => 'Civil Engineer',
                'type'                => 'full-time',
                'salary_range'        => 'Above Minimum Wage',
                'education_level'     => 'college_graduate',
                'experience_required' => '3_years',
                'slots'               => 2,
                'days_back'           => 8,
                'days_ahead'          => 22,
                'description'         => 'Oversee and manage construction projects in Isabela. Must be a licensed civil engineer with strong project management skills.',
                'skills'              => ['AutoCAD', 'Project Management', 'Structural Analysis', 'Cost Estimation'],
            ],
            [
                'title'               => 'Construction Worker',
                'type'                => 'full-time',
                'salary_range'        => 'Minimum Wage',
                'education_level'     => 'elementary',
                'experience_required' => 'fresh_grad',
                'slots'               => 20,
                'days_back'           => 2,
                'days_ahead'          => 28,
                'description'         => 'Join our construction crew for residential and commercial building projects. Tools and protective equipment provided.',
                'skills'              => ['Concrete Work', 'Masonry', 'Carpentry', 'Welding'],
            ],
        ],
        'employer4@globaltech.com' => [
            [
                'title'               => 'Social Media Manager',
                'type'                => 'full-time',
                'salary_range'        => 'Above Minimum Wage',
                'education_level'     => 'college_graduate',
                'experience_required' => '1_year',
                'slots'               => 2,
                'days_back'           => 4,
                'days_ahead'          => 26,
                'description'         => 'Manage and grow our clients\' brand presence across all social media platforms. Create engaging content and analyze performance.',
                'skills'              => ['Facebook Ads', 'Copywriting', 'Canva', 'Content Creation', 'SEO'],
            ],
            [
                'title'               => 'Graphic Designer',
                'type'                => 'contract',
                'salary_range'        => 'Minimum Wage',
                'education_level'     => 'college_level',
                'experience_required' => 'less_than_1',
                'slots'               => 1,
                'days_back'           => 6,
                'days_ahead'          => 24,
                'description'         => 'Create compelling visual content for digital and print media. Proficient in Adobe products required.',
                'skills'              => ['Adobe Photoshop', 'Adobe Illustrator', 'Canva', 'Branding'],
            ],
        ],
        'employer5@greenvalley.com' => [
            [
                'title'               => 'Farm Worker',
                'type'                => 'full-time',
                'salary_range'        => 'Minimum Wage',
                'education_level'     => 'elementary',
                'experience_required' => 'fresh_grad',
                'slots'               => 15,
                'days_back'           => 1,
                'days_ahead'          => 29,
                'description'         => 'Assist with planting, harvesting, and irrigation of our fruit orchards. Training provided for new hires.',
                'skills'              => ['Farming', 'Irrigation', 'Pest Control', 'Harvesting'],
            ],
            [
                'title'               => 'Agricultural Technician',
                'type'                => 'full-time',
                'salary_range'        => 'Above Minimum Wage',
                'education_level'     => 'college_graduate',
                'experience_required' => '2_years',
                'slots'               => 1,
                'days_back'           => 12,
                'days_ahead'          => 18,
                'description'         => 'Oversee soil testing, crop health monitoring, and yield optimization. Background in agriculture or agronomy required.',
                'skills'              => ['Soil Science', 'Crop Management', 'Pest Control', 'Data Analysis', 'Farming'],
            ],
        ],
    ];

    public function run(): void
    {
        $employers = [
            [
                'company_name'    => 'TechCorp Solutions',
                'contact_person'  => 'Juan Dela Cruz',
                'email'           => 'employer1@techcorp.com',
                'password'        => Hash::make('password123'),
                'industry'        => 'Information Technology',
                'company_size'    => '51-200',
                'tagline'         => 'Building the future, one line at a time.',
                'about'           => 'TechCorp Solutions provides IT services and software development for businesses across Isabela.',
                'business_type'   => 'Corporation',
                'founded'         => 2015,
                'tin'             => '123-456-789-000',
                'city'            => 'Santiago City',
                'province'        => 'Isabela',
                'barangay'        => 'Villasis',
                'address_full'    => 'Villasis, Santiago City, Isabela',
                'phone'           => '09171234567',
                'website'         => 'https://techcorp.com',
                'latitude'        => 16.69156558588959,
                'longitude'       => 121.55512678252572,
                'status'          => 'verified',
                'verified_at'     => now()->subDays(30),
                'total_hired'     => 4,
            ],
            [
                'company_name'    => 'Harvest Foods Inc.',
                'contact_person'  => 'Ana Reyes',
                'email'           => 'employer2@harvestfoods.com',
                'password'        => Hash::make('password123'),
                'industry'        => 'Food & Beverage',
                'company_size'    => '11-50',
                'tagline'         => 'Fresh produce, fresh opportunities.',
                'about'           => 'Harvest Foods Inc. specializes in processing and distributing local agricultural products across Cagayan Valley.',
                'business_type'   => 'Partnership',
                'founded'         => 2010,
                'tin'             => '234-567-890-000',
                'city'            => 'Cauayan City',
                'province'        => 'Isabela',
                'barangay'        => 'Minante',
                'address_full'    => 'Minante, Cauayan City, Isabela',
                'phone'           => '09209876543',
                'latitude'        => 16.93301234567890,
                'longitude'       => 121.77284567890123,
                'status'          => 'verified',
                'verified_at'     => now()->subDays(60),
                'total_hired'     => 8,
            ],
            [
                'company_name'    => 'BuildRight Construction',
                'contact_person'  => 'Pedro Santos',
                'email'           => 'employer3@buildright.com',
                'password'        => Hash::make('password123'),
                'industry'        => 'Construction',
                'company_size'    => '201-500',
                'about'           => 'BuildRight Construction handles residential and commercial projects across Isabela and Cagayan.',
                'business_type'   => 'Corporation',
                'founded'         => 2008,
                'tin'             => '345-678-901-000',
                'city'            => 'Ilagan City',
                'province'        => 'Isabela',
                'address_full'    => 'Ilagan City, Isabela',
                'phone'           => '09351112233',
                'latitude'        => 17.14891234567890,
                'longitude'       => 121.89043456789012,
                'status'          => 'pending',
                'total_hired'     => 0,
            ],
            [
                'company_name'    => 'GlobalTech Marketing',
                'contact_person'  => 'Sara Lim',
                'email'           => 'employer4@globaltech.com',
                'password'        => Hash::make('password123'),
                'industry'        => 'Marketing',
                'company_size'    => '11-50',
                'tagline'         => 'Your brand. Our expertise.',
                'about'           => 'GlobalTech Marketing is a forward-thinking digital marketing agency specializing in social media and SEO.',
                'business_type'   => 'Corporation',
                'founded'         => 2018,
                'tin'             => '456-789-012-000',
                'city'            => 'Echague',
                'province'        => 'Isabela',
                'address_full'    => 'Echague, Isabela',
                'phone'           => '09191234567',
                'latitude'        => 16.7118,
                'longitude'       => 121.6167,
                'status'          => 'verified',
                'verified_at'     => now()->subDays(15),
                'total_hired'     => 2,
            ],
            [
                'company_name'    => 'GreenValley Orchards',
                'contact_person'  => 'David Bautista',
                'email'           => 'employer5@greenvalley.com',
                'password'        => Hash::make('password123'),
                'industry'        => 'Agriculture',
                'company_size'    => '1-10',
                'tagline'         => 'Growing fresh, growing together.',
                'about'           => 'A sustainable farm producing export-quality mangoes and other organic fruits for local and international markets.',
                'business_type'   => 'Sole Proprietorship',
                'founded'         => 2012,
                'tin'             => '567-890-123-000',
                'city'            => 'Cordon',
                'province'        => 'Isabela',
                'address_full'    => 'Cordon, Isabela',
                'phone'           => '09455556666',
                'latitude'        => 16.6667,
                'longitude'       => 121.4500,
                'status'          => 'verified',
                'verified_at'     => now()->subDays(45),
                'total_hired'     => 6,
            ],
        ];

        foreach ($employers as $data) {
            $employer = Employer::updateOrCreate(['email' => $data['email']], $data);

            // Only create job listings for verified employers
            if ($employer->status !== 'verified') continue;

            $jobDefs = self::$jobsByEmployer[$data['email']] ?? [];
            foreach ($jobDefs as $def) {
                $skills    = $def['skills'];
                $daysBack  = $def['days_back'];
                $daysAhead = $def['days_ahead'];
                unset($def['skills'], $def['days_back'], $def['days_ahead']);

                $barangay = $data['barangay'] ?? '';
                $location = $barangay
                    ? $barangay . ', ' . ucwords(strtolower($data['city'])) . ', ' . ucwords(strtolower($data['province']))
                    : ucwords(strtolower($data['city'])) . ', ' . ucwords(strtolower($data['province']));

                $job = JobListing::create(array_merge($def, [
                    'employer_id' => $employer->id,
                    'location'    => $location,
                    'posted_date' => now()->subDays($daysBack),
                    'deadline'    => now()->addDays($daysAhead),
                    'status'      => 'open',
                ]));

                foreach ($skills as $skill) {
                    JobSkill::create(['job_listing_id' => $job->id, 'skill' => $skill]);
                }
            }
        }
    }
}
