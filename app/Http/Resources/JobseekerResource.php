<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class JobseekerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'contact' => $this->contact,
            'address' => $this->address,
            'sex' => $this->sex,
            'date_of_birth' => $this->date_of_birth,
            'bio' => $this->bio,
            'resume_url' => $this->resume_path ? Storage::disk('public')->url($this->resume_path) : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'skills' => $this->whenLoaded('skills', fn() => $this->skills->pluck('skill')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
