<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobListingSkillItem extends Model
{
    use HasFactory;

    protected $table = 'job_listing_skill_items';

    protected $fillable = [
        'job_listing_id',
        'skill_id',
        'is_required',
        'priority',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'priority' => 'integer',
    ];

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}

