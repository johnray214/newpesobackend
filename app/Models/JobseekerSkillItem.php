<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobseekerSkillItem extends Model
{
    use HasFactory;

    protected $table = 'jobseeker_skill_items';

    protected $fillable = [
        'jobseeker_id',
        'skill_id',
        'proficiency',
        'years_experience',
    ];

    protected $casts = [
        'years_experience' => 'integer',
    ];

    public function jobseeker()
    {
        return $this->belongsTo(Jobseeker::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}

