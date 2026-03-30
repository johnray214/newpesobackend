<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_listing_id',
        'jobseeker_id',
        'status',
        'match_score',
        'applied_at',
    ];

    protected $casts = [
        'match_score' => 'integer',
        'applied_at' => 'datetime',
    ];

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function jobseeker()
    {
        return $this->belongsTo(Jobseeker::class);
    }

    public static function calculateMatchScore(Jobseeker $jobseeker, JobListing $jobListing): int
    {
        $jobSkills = $jobListing->skills->pluck('skill')->map(fn($s) => strtolower($s))->toArray();
        
        if (empty($jobSkills)) {
            return 0;
        }

        $jobseekerSkills = $jobseeker->skills->pluck('skill')->map(fn($s) => strtolower($s))->toArray();
        
        $matchingSkills = array_intersect($jobseekerSkills, $jobSkills);
        
        return (int) round((count($matchingSkills) / count($jobSkills)) * 100);
    }
}
