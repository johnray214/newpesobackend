<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_listing_id' => $this->job_listing_id,
            'job_listing' => $this->whenLoaded('jobListing', fn() => [
                'id' => $this->jobListing->id,
                'title' => $this->jobListing->title,
                'type' => $this->jobListing->type,
                'location' => $this->jobListing->location,
                'salary_range' => $this->jobListing->salary_range,
            ]),
            'jobseeker_id' => $this->jobseeker_id,
            'jobseeker' => $this->whenLoaded('jobseeker', function () {
                return [
                    'id' => $this->jobseeker->id,
                    'first_name' => $this->jobseeker->first_name,
                    'last_name' => $this->jobseeker->last_name,
                    'full_name' => $this->jobseeker->fullName(),
                    'photo' => $this->jobseeker->photo ? (str_starts_with($this->jobseeker->photo, 'http') ? $this->jobseeker->photo : asset('storage/' . $this->jobseeker->photo)) : null,
                    'email' => $this->jobseeker->email,
                    'address' => $this->jobseeker->address,
                    'contact' => $this->jobseeker->contact,
                    'sex' => $this->jobseeker->sex,
                    'date_of_birth' => $this->jobseeker->date_of_birth,
                    'skills' => $this->jobseeker->skills->pluck('skill')->toArray(),
                    'education_level' => $this->jobseeker->education_level,
                    'job_experience' => $this->jobseeker->job_experience,
                    'has_resume' => ! empty($this->jobseeker->resume_path),
                ];
            }),
            'status' => $this->status,
            'match_score' => $this->match_score,
            'applied_at' => $this->applied_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
