<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobseekerSkill extends Model
{
    use HasFactory;

    protected $fillable = ['jobseeker_id', 'skill'];

    public function jobseeker()
    {
        return $this->belongsTo(Jobseeker::class);
    }
}
