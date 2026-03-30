<?php

namespace App\Http\Resources;

use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'photo'          => PublicStorageUrl::fromRequest($request, $this->photo),
            'company_name'   => $this->company_name,
            'contact_person' => $this->contact_person,
            'email'          => $this->email,
            'industry'       => $this->industry,
            'company_size'   => $this->company_size,

            // ── Address ───────────────────────────────────────────────
            'barangay'       => $this->barangay,
            'city'           => $this->city,
            'province'       => $this->province,
            'address_full'   => $this->address_full,
            'latitude'       => $this->latitude,
            'longitude'      => $this->longitude,
            'map_visible'    => $this->map_visible,

            // ── Contact ───────────────────────────────────────────────
            'phone'          => $this->phone,
            'tin'            => $this->tin,
            'website'        => $this->website,

            // ── Extended Profile ──────────────────────────────────────
            'tagline'        => $this->tagline,
            'about'          => $this->about,
            // FIX #2: use real legal_name column, fall back to company_name
            'legal_name'     => $this->legal_name ?? $this->company_name,
            'business_type'  => $this->business_type,
            'founded'        => $this->founded,
            // FIX #6: perks is cast to array on the model, always returns array
            'perks'          => $this->perks ?? [],

            // ── Status ────────────────────────────────────────────────
            'status'         => $this->status,
            'verified_at'    => $this->verified_at,

            // ── Documents ─────────────────────────────────────────────
            'biz_permit_url' => PublicStorageUrl::fromRequest($request, $this->biz_permit_path),
            'bir_cert_url'   => PublicStorageUrl::fromRequest($request, $this->bir_cert_path),

            // ── Admin Dashboard Employer Properties ─────────────────────
            'total_hired'      => $this->total_hired ?? 0,
            'hired_applicants' => $this->hired_applicants ?? [],
            'job_listings'     => $this->whenLoaded('jobListings', fn() => $this->jobListings),

            // ── Stats ─────────────────────────────────────────────────
            // FIX #3: prefer the eager loadCount value, fall back to collection count
            'stats' => [
                'active_listings'  => $this->active_listings_count
                    ?? $this->whenLoaded('jobs', fn() =>
                        $this->jobs->where('status', 'open')->count(), 0),
                'total_applicants' => $this->whenLoaded('jobs', fn() =>
                    $this->jobs->sum('applications_count'), 0),
                'total_hired'      => $this->total_hired ?? 0,
                'member_since'     => $this->created_at?->year,
            ],

            // ── Jobs Preview ───────────────────aa───────────────────────
            // FIX #4: include bg + color so Vue doesn't crash on undefined
            'jobs' => $this->whenLoaded('jobs', function () {
                $palette = [
                    ['bg' => '#eff6ff', 'color' => '#2563eb'],
                    ['bg' => '#faf5ff', 'color' => '#8b5cf6'],
                    ['bg' => '#fdf4ff', 'color' => '#d946ef'],
                    ['bg' => '#f0fdf4', 'color' => '#16a34a'],
                    ['bg' => '#f8fafc', 'color' => '#94a3b8'],
                ];
                return $this->jobs->values()->map(fn($j, $i) => [
                    'title'      => $j->title,
                    'type'       => $j->employment_type,
                    'location'   => $j->location,
                    'salary'     => $j->salary_range,
                    'status'     => ucfirst($j->status),
                    'applicants' => $j->applications_count ?? 0,
                    'bg'         => $palette[$i % count($palette)]['bg'],
                    'color'      => $palette[$i % count($palette)]['color'],
                ]);
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}