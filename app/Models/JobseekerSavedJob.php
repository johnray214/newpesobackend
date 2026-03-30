<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobseekerSavedJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'jobseeker_id',
        'job_listing_id',
    ];

    public function jobseeker()
    {
        return $this->belongsTo(Jobseeker::class);
    }

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }
}

