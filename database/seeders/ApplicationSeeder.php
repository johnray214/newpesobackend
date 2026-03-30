<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Employer;
use App\Models\JobListing;
use App\Models\Jobseeker;
use App\Models\JobSkill;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        // For each job listing, find jobseekers whose skills overlap,
        // calculate a real match score, and seed applications & potential applicants.
        $jobs = JobListing::with(['employer', 'skills'])->get();

        $statuses    = ['reviewing', 'shortlisted', 'interview', 'hired', 'rejected'];
        $statusWeights = [50, 20, 15, 10, 5]; // % distribution

        foreach ($jobs as $job) {
            $jobSkills = $job->skills->pluck('skill')->map(fn($s) => strtolower($s))->toArray();
            if (empty($jobSkills)) continue;

            // Find jobseekers with matching skills
            $matchingSeekers = Jobseeker::whereHas('skills', function ($q) use ($jobSkills) {
                $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(skill)'), $jobSkills);
            })->with('skills')->get();

            $applied = 0;
            foreach ($matchingSeekers as $seeker) {
                $seekerSkills = $seeker->skills->pluck('skill')->map(fn($s) => strtolower($s))->toArray();
                $intersection = array_intersect($jobSkills, $seekerSkills);
                $matchScore   = (int) round(count($intersection) / count($jobSkills) * 100);

                // Only apply if match score is meaningful
                if ($matchScore < 30) continue;

                // Limit to a reasonable number of applications per job
                if ($applied >= ($job->slots * 5 + 5)) break;

                // Pick a status weighted toward reviewing
                $rand   = rand(1, 100);
                $cumul  = 0;
                $status = 'reviewing';
                foreach ($statuses as $i => $s) {
                    $cumul += $statusWeights[$i];
                    if ($rand <= $cumul) { $status = $s; break; }
                }

                // Ensure we don't duplicate applications
                Application::firstOrCreate(
                    ['job_listing_id' => $job->id, 'jobseeker_id' => $seeker->id],
                    [
                        'status'     => $status,
                        'match_score' => $matchScore,
                        'applied_at'  => now()->subDays(rand(1, 20)),
                    ]
                );
                $applied++;
            }
        }
    }
}
