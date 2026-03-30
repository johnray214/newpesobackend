<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'employer' => $this->whenLoaded('employer', fn() => [
                'id' => $this->employer->id,
                'company_name' => $this->employer->company_name,
                'photo' => $this->employer->photo,
            ]),
            'title' => $this->title,
            'type' => $this->type,
            'location' => $this->location,
            'salary_range' => $this->salary_range,
            'description' => $this->description,
            'slots' => $this->slots,
            'status' => $this->status,
            'posted_date' => $this->posted_date,
            'deadline' => $this->deadline,
            'skills' => $this->whenLoaded('skills', fn() => $this->skills->pluck('skill')),
            'applications_count' => $this->whenCounted('applications'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
