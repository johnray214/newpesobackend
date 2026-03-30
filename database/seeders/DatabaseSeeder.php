<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,          // Admin & staff accounts
            EmployerSeeder::class,      // 5 employers + job listings + job skills
            JobseekerSeeder::class,     // 50 jobseekers + jobseeker skills
            ApplicationSeeder::class,   // Skill-matched applications with real scores
            NotificationSeeder::class,  // PESO notifications for employers & jobseekers
            EventSeeder::class,         // PESO events (job fairs, trainings, etc.)
            SkillCatalogSeeder::class,  // Skill catalog (auto-built from job_skills & jobseeker_skills)
        ]);
    }
}
