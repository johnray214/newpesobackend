<?php

namespace Database\Seeders;

use App\Models\Jobseeker;
use App\Models\JobseekerSkill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class JobseekerSeeder extends Seeder
{
    // ── Skill pools that overlap with job listings ─────────────────────────
    // Each pool maps to the skills used by the employer job listings
    private array $skillPools = [
        'it'           => ['PHP', 'Laravel', 'JavaScript', 'Vue.js', 'MySQL', 'Networking', 'Windows OS', 'Linux', 'Hardware Troubleshooting', 'Python'],
        'design'       => ['Figma', 'Adobe XD', 'CSS', 'Prototyping', 'User Research', 'Adobe Photoshop', 'Adobe Illustrator', 'Canva', 'Branding'],
        'food'         => ['Food Safety', 'Quality Control', 'Packaging', 'Sanitation', 'Driving', 'Route Planning', 'Customer Service', 'Time Management'],
        'construction' => ['AutoCAD', 'Project Management', 'Structural Analysis', 'Cost Estimation', 'Concrete Work', 'Masonry', 'Carpentry', 'Welding'],
        'marketing'    => ['Facebook Ads', 'Copywriting', 'Canva', 'Content Creation', 'SEO', 'Adobe Photoshop', 'Adobe Illustrator', 'Branding'],
        'agriculture'  => ['Farming', 'Irrigation', 'Pest Control', 'Harvesting', 'Soil Science', 'Crop Management', 'Data Analysis'],
    ];

    public function run(): void
    {
        $jobseekers = [
            // ── IT / Web cluster (matches TechCorp jobs) ──────────────────
            ['first' => 'Mark',     'last' => 'Villanueva', 'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '2 years as a web developer at a local startup.',     'pool' => ['PHP','Laravel','JavaScript','Vue.js','MySQL'],                          'dob' => '1998-05-14', 'city' => 'Santiago City'],
            ['first' => 'Carlo',    'last' => 'Ramos',      'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '3 years full-stack development experience.',          'pool' => ['PHP','Laravel','Vue.js','MySQL','Python'],                               'dob' => '1997-08-20', 'city' => 'Santiago City'],
            ['first' => 'Liza',     'last' => 'Chua',       'sex' => 'female', 'edu' => 'college_graduate', 'exp' => '1 year IT support at a BPO company.',                'pool' => ['Networking','Windows OS','Hardware Troubleshooting','Linux'],            'dob' => '2000-02-11', 'city' => 'Cauayan City'],
            ['first' => 'Arjun',    'last' => 'Mendez',     'sex' => 'male',   'edu' => 'college_graduate', 'exp' => 'Intern at a software firm for 6 months.',             'pool' => ['JavaScript','Vue.js','CSS','MySQL'],                                     'dob' => '2001-07-30', 'city' => 'Santiago City'],
            ['first' => 'Raiza',    'last' => 'Aquino',     'sex' => 'female', 'edu' => 'college_level',    'exp' => '1 year part-time web dev for a local business.',      'pool' => ['PHP','JavaScript','MySQL','CSS'],                                        'dob' => '2000-11-22', 'city' => 'Ilagan City'],
            ['first' => 'Dennis',   'last' => 'Salazar',    'sex' => 'male',   'edu' => 'vocational',       'exp' => '2 years IT support and LAN cabling.',                 'pool' => ['Networking','Windows OS','Hardware Troubleshooting','Linux'],            'dob' => '1999-03-15', 'city' => 'Santiago City'],
            ['first' => 'Kristine', 'last' => 'Castillo',   'sex' => 'female', 'edu' => 'college_graduate', 'exp' => 'Fresh graduate, completed internship at tech firm.',   'pool' => ['PHP','Laravel','MySQL','Linux'],                                         'dob' => '2002-01-09', 'city' => 'Echague'],
            ['first' => 'Rommel',   'last' => 'Santos',     'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '4 years systems administration.',                     'pool' => ['Networking','Linux','Windows OS','Hardware Troubleshooting'],            'dob' => '1996-06-25', 'city' => 'Santiago City'],

            // ── Design cluster (matches TechCorp UI/UX + GlobalTech) ──────
            ['first' => 'Ana',      'last' => 'Torres',     'sex' => 'female', 'edu' => 'college_graduate', 'exp' => '2 years as UI/UX designer for a mobile app company.', 'pool' => ['Figma','Adobe XD','CSS','Prototyping','User Research'],                  'dob' => '1999-04-18', 'city' => 'Cauayan City'],
            ['first' => 'Mika',     'last' => 'Bernardo',   'sex' => 'female', 'edu' => 'college_graduate', 'exp' => '1 year freelance graphic and UI design.',              'pool' => ['Figma','Adobe Photoshop','Adobe Illustrator','Canva','Branding'],       'dob' => '2001-09-03', 'city' => 'Santiago City'],
            ['first' => 'Ryan',     'last' => 'Flores',     'sex' => 'male',   'edu' => 'college_level',    'exp' => 'Self-taught designer with 2 years portfolio work.',    'pool' => ['Canva','Adobe Photoshop','Branding','Content Creation'],                 'dob' => '2000-12-17', 'city' => 'Echague'],
            ['first' => 'Sheila',   'last' => 'Nacino',     'sex' => 'female', 'edu' => 'college_graduate', 'exp' => '3 years graphic design at marketing agency.',          'pool' => ['Adobe Photoshop','Adobe Illustrator','Canva','Branding','Figma'],       'dob' => '1997-10-05', 'city' => 'Santiago City'],
            ['first' => 'JR',       'last' => 'Magno',      'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '1 year UI design for e-commerce startup.',             'pool' => ['Figma','Adobe XD','Prototyping','User Research','CSS'],                  'dob' => '2000-06-14', 'city' => 'Cauayan City'],

            // ── Marketing cluster (matches GlobalTech) ────────────────────
            ['first' => 'Nicole',   'last' => 'Reyes',      'sex' => 'female', 'edu' => 'college_graduate', 'exp' => '2 years social media management for local brands.',   'pool' => ['Facebook Ads','Copywriting','Canva','Content Creation','SEO'],          'dob' => '1999-07-22', 'city' => 'Echague'],
            ['first' => 'Jed',      'last' => 'Ocampo',     'sex' => 'male',   'edu' => 'college_graduate', 'exp' => 'Digital marketing specialist for 1 year.',             'pool' => ['Facebook Ads','SEO','Content Creation','Copywriting'],                   'dob' => '2001-03-10', 'city' => 'Santiago City'],
            ['first' => 'Clarisse', 'last' => 'Tan',        'sex' => 'female', 'edu' => 'college_level',    'exp' => 'Intern at advertising firm, 6 months.',                'pool' => ['Canva','Copywriting','Content Creation','Branding'],                     'dob' => '2002-08-28', 'city' => 'Echague'],
            ['first' => 'Kevin',    'last' => 'Paral',      'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '2 years running Facebook Ads for e-commerce clients.', 'pool' => ['Facebook Ads','SEO','Canva','Content Creation'],                         'dob' => '1998-02-02', 'city' => 'Cauayan City'],

            // ── Food / Service cluster (matches Harvest Foods) ────────────
            ['first' => 'Maria',    'last' => 'Gonzales',   'sex' => 'female', 'edu' => 'highschool',       'exp' => '1 year packing and labeling at a bottling plant.',     'pool' => ['Food Safety','Packaging','Sanitation','Quality Control'],                'dob' => '1997-12-30', 'city' => 'Cauayan City'],
            ['first' => 'Ben',      'last' => 'Lagman',     'sex' => 'male',   'edu' => 'highschool',       'exp' => 'Fresh applicant. Willing to learn.',                   'pool' => ['Food Safety','Packaging','Sanitation'],                                  'dob' => '2003-04-01', 'city' => 'Cauayan City'],
            ['first' => 'Elvie',    'last' => 'Daquigan',   'sex' => 'female', 'edu' => 'highschool',       'exp' => '2 years food preparation and kitchen assembly.',        'pool' => ['Food Safety','Quality Control','Sanitation','Packaging'],                'dob' => '1998-09-19', 'city' => 'Cauayan City'],
            ['first' => 'Ramon',    'last' => 'Caliboso',   'sex' => 'male',   'edu' => 'highschool',       'exp' => '3 years delivery driver for wholesale distributor.',    'pool' => ['Driving','Route Planning','Customer Service','Time Management'],         'dob' => '1995-01-08', 'city' => 'Santiago City'],
            ['first' => 'Grace',    'last' => 'Punzalan',   'sex' => 'female', 'edu' => 'senior_highschool','exp' => '1 year barista and food handler at a cafe.',            'pool' => ['Food Safety','Customer Service','Sanitation'],                           'dob' => '2001-11-14', 'city' => 'Isabela'],
            ['first' => 'Romeo',    'last' => 'Encabo',     'sex' => 'male',   'edu' => 'highschool',       'exp' => '2 years delivery and logistics.',                       'pool' => ['Driving','Route Planning','Time Management'],                            'dob' => '1996-07-07', 'city' => 'Cauayan City'],
            ['first' => 'Jennie',   'last' => 'Alvarado',   'sex' => 'female', 'edu' => 'vocational',       'exp' => 'TESDA-trained food processing graduate.',               'pool' => ['Food Safety','Quality Control','Packaging','Sanitation'],                'dob' => '2000-05-20', 'city' => 'Agustin'],

            // ── Construction cluster (matches BuildRight) ─────────────────
            ['first' => 'Jose',     'last' => 'Mendoza',    'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '5 years civil engineer at a construction firm.',       'pool' => ['AutoCAD','Project Management','Structural Analysis','Cost Estimation'],  'dob' => '1991-03-22', 'city' => 'Ilagan City'],
            ['first' => 'Rolando',  'last' => 'Crisologo',  'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '3 years as licensed civil engineer.',                  'pool' => ['AutoCAD','Structural Analysis','Cost Estimation','Project Management'], 'dob' => '1994-10-11', 'city' => 'Ilagan City'],
            ['first' => 'Marlon',   'last' => 'Bello',      'sex' => 'male',   'edu' => 'elementary',       'exp' => '10 years construction labor experience.',               'pool' => ['Concrete Work','Masonry','Carpentry','Welding'],                         'dob' => '1985-06-15', 'city' => 'Ilagan City'],
            ['first' => 'Edgar',    'last' => 'Dela Rosa',  'sex' => 'male',   'edu' => 'highschool',       'exp' => '3 years masonry and concrete work.',                   'pool' => ['Masonry','Concrete Work','Carpentry'],                                   'dob' => '1990-02-28', 'city' => 'Isabela'],
            ['first' => 'Ronnie',   'last' => 'Carino',     'sex' => 'male',   'edu' => 'highschool',       'exp' => 'Experienced welder and carpenter, 4 years.',            'pool' => ['Welding','Carpentry','Concrete Work'],                                   'dob' => '1988-08-08', 'city' => 'Ilagan City'],
            ['first' => 'Nonito',   'last' => 'Peralta',    'sex' => 'male',   'edu' => 'elementary',       'exp' => '8 years construction worker for various projects.',     'pool' => ['Concrete Work','Masonry','Carpentry','Welding'],                         'dob' => '1983-11-01', 'city' => 'Cordon'],

            // ── Agriculture cluster (matches GreenValley) ─────────────────
            ['first' => 'Rogelio',  'last' => 'Ferrer',     'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '3 years agricultural technician at PHILRICE.',         'pool' => ['Soil Science','Crop Management','Pest Control','Data Analysis','Farming'],'dob' => '1993-04-12', 'city' => 'Cordon'],
            ['first' => 'Dominga',  'last' => 'Castaneda',  'sex' => 'female', 'edu' => 'college_graduate', 'exp' => '2 years agronomy practitioner.',                       'pool' => ['Soil Science','Crop Management','Pest Control','Farming'],               'dob' => '1996-09-25', 'city' => 'Cordon'],
            ['first' => 'Leandro',  'last' => 'Paguio',     'sex' => 'male',   'edu' => 'highschool',       'exp' => '5 years farm worker in rice and corn production.',     'pool' => ['Farming','Irrigation','Harvesting','Pest Control'],                      'dob' => '1987-01-17', 'city' => 'Cordon'],
            ['first' => 'Natividad','last' => 'Bugarin',    'sex' => 'female', 'edu' => 'elementary',       'exp' => 'Lifelong farmer experience.',                          'pool' => ['Farming','Harvesting','Irrigation'],                                     'dob' => '1980-12-05', 'city' => 'Cordon'],
            ['first' => 'Emilio',   'last' => 'Tomas',      'sex' => 'male',   'edu' => 'highschool',       'exp' => '3 years harvest and post-harvest handling.',            'pool' => ['Harvesting','Farming','Pest Control','Irrigation'],                      'dob' => '1989-07-21', 'city' => 'Cordon'],
            ['first' => 'Aling',    'last' => 'Divina',     'sex' => 'female', 'edu' => 'elementary',       'exp' => 'Community farmer for over 10 years.',                  'pool' => ['Farming','Harvesting'],                                                  'dob' => '1975-03-30', 'city' => 'Cordon'],

            // ── Mixed / General cluster ───────────────────────────────────
            ['first' => 'Rina',     'last' => 'Sabado',     'sex' => 'female', 'edu' => 'college_graduate', 'exp' => '1 year customer service representative.',               'pool' => ['Customer Service','Time Management','Copywriting'],                      'dob' => '2001-06-06', 'city' => 'Santiago City'],
            ['first' => 'Fred',     'last' => 'Villanueva', 'sex' => 'male',   'edu' => 'vocational',       'exp' => 'TESDA grad, electrician and wireman 2 years.',          'pool' => ['Welding','Carpentry','Hardware Troubleshooting'],                        'dob' => '1999-01-23', 'city' => 'Isabela'],
            ['first' => 'Lovely',   'last' => 'Montoya',    'sex' => 'female', 'edu' => 'college_graduate', 'exp' => 'Accounting graduate, BS Accountancy.',                  'pool' => ['Time Management','Data Analysis','Cost Estimation'],                     'dob' => '2000-04-04', 'city' => 'Santiago City'],
            ['first' => 'Angelo',   'last' => 'Lucero',     'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '2 years project coordinator.',                          'pool' => ['Project Management','Time Management','Customer Service'],               'dob' => '1998-08-15', 'city' => 'Cauayan City'],
            ['first' => 'Patricia', 'last' => 'Cruz',       'sex' => 'female', 'edu' => 'college_graduate', 'exp' => 'HR assistant 1 year.',                                  'pool' => ['Customer Service','Time Management','Copywriting','Content Creation'],   'dob' => '2000-10-10', 'city' => 'Santiago City'],
            ['first' => 'Baltazar', 'last' => 'Nolasco',    'sex' => 'male',   'edu' => 'highschool',       'exp' => '4 years warehouse and inventory helper.',               'pool' => ['Time Management','Quality Control','Packaging'],                         'dob' => '1994-02-19', 'city' => 'Cauayan City'],
            ['first' => 'Irene',    'last' => 'Belen',      'sex' => 'female', 'edu' => 'senior_highschool','exp' => 'Part-time sales associate 1 year.',                     'pool' => ['Customer Service','Time Management'],                                    'dob' => '2003-05-08', 'city' => 'Isabela'],
            ['first' => 'Cesar',    'last' => 'Talamayan',  'sex' => 'male',   'edu' => 'college_graduate', 'exp' => 'Fresh IT graduate, network+ certified.',                'pool' => ['Networking','Linux','Windows OS'],                                       'dob' => '2002-11-30', 'city' => 'Santiago City'],
            ['first' => 'Helen',    'last' => 'Oliva',      'sex' => 'female', 'edu' => 'college_graduate', 'exp' => '3 years marketing analyst.',                            'pool' => ['SEO','Facebook Ads','Data Analysis','Copywriting'],                      'dob' => '1997-04-27', 'city' => 'Echague'],
            ['first' => 'Dino',     'last' => 'Pagdilao',   'sex' => 'male',   'edu' => 'highschool',       'exp' => '2 years as security guard and driver.',                 'pool' => ['Driving','Time Management','Route Planning'],                            'dob' => '1993-09-13', 'city' => 'Isabela'],
            ['first' => 'Rosario',  'last' => 'Aguilar',    'sex' => 'female', 'edu' => 'vocational',       'exp' => 'Dressmaking and garments production, 5 years.',         'pool' => ['Time Management','Quality Control'],                                     'dob' => '1989-07-04', 'city' => 'Santiago City'],
            ['first' => 'Arvin',    'last' => 'Galo',       'sex' => 'male',   'edu' => 'college_graduate', 'exp' => '1 year data encoder and IT assistant.',                 'pool' => ['MySQL','Data Analysis','Windows OS','Time Management'],                  'dob' => '2001-02-14', 'city' => 'Santiago City'],
            ['first' => 'Precious', 'last' => 'Lising',     'sex' => 'female', 'edu' => 'college_graduate', 'exp' => '2 years content writer and social media handler.',      'pool' => ['Copywriting','Content Creation','SEO','Canva'],                          'dob' => '1999-12-25', 'city' => 'Cauayan City'],
        ];

        $cities = [
            'Santiago City' => ['province' => 'Isabela',  'province_name' => 'Isabela',  'city_code' => '033104', 'province_code' => '0331'],
            'Cauayan City'  => ['province' => 'Isabela',  'province_name' => 'Isabela',  'city_code' => '033101', 'province_code' => '0331'],
            'Ilagan City'   => ['province' => 'Isabela',  'province_name' => 'Isabela',  'city_code' => '033116', 'province_code' => '0331'],
            'Echague'       => ['province' => 'Isabela',  'province_name' => 'Isabela',  'city_code' => '033109', 'province_code' => '0331'],
            'Cordon'        => ['province' => 'Isabela',  'province_name' => 'Isabela',  'city_code' => '033108', 'province_code' => '0331'],
            'Isabela'       => ['province' => 'Isabela',  'province_name' => 'Isabela',  'city_code' => '033100', 'province_code' => '0331'],
            'Agustin'       => ['province' => 'Isabela',  'province_name' => 'Isabela',  'city_code' => '033102', 'province_code' => '0331'],
        ];

        $n = 0;
        foreach ($jobseekers as $js) {
            $n++;
            $cityData = $cities[$js['city']] ?? $cities['Santiago City'];
            $fullName = "{$js['first']} {$js['last']}";
            $email    = strtolower(str_replace(' ', '.', "{$js['first']}.{$js['last']}")) . "@gmail.com";

            $seeker = Jobseeker::updateOrCreate(
                ['email' => $email],
                [
                    'first_name'      => $js['first'],
                    'last_name'       => $js['last'],
                    'email'           => $email,
                    'password'        => Hash::make('password123'),
                    'sex'             => $js['sex'],
                    'date_of_birth'   => $js['dob'],
                    'education_level' => $js['edu'],
                    'job_experience'  => $js['exp'],
                    'bio'             => "Experienced professional from {$js['city']}, Isabela looking for new opportunities.",
                    'contact'         => '091' . rand(10000000, 99999999),
                    'address'         => "{$js['city']}, {$cityData['province']}",
                    'city_name'       => $js['city'],
                    'city_code'       => $cityData['city_code'],
                    'province_name'   => $cityData['province_name'],
                    'province_code'   => $cityData['province_code'],
                    'barangay_name'   => 'Poblacion',
                    'status'          => 'active',
                    'latitude'        => 16.6 + ($n * 0.005),
                    'longitude'       => 121.5 + ($n * 0.004),
                ]
            );

            // Attach skills (avoid duplicates on re-seed)
            foreach ($js['pool'] as $skill) {
                JobseekerSkill::firstOrCreate([
                    'jobseeker_id' => $seeker->id,
                    'skill'        => $skill,
                ]);
            }
        }
    }
}
