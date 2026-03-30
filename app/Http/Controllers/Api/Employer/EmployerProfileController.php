<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployerResource;
use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployerProfileController extends Controller
{
    public function show(Request $request)
    {
        $employer = $request->user()->loadCount([
            'jobs as active_listings_count' => fn($q) => $q->where('status', 'open'),
        ])->load(['jobs' => fn($q) => $q->withCount('applications')]);

        return response()->json([
            'success' => true,
            'data'    => new EmployerResource($employer),
        ]);
    }

    public function update(Request $request)
    {
        $employer = $request->user();

        $validated = $request->validate([
            'company_name'   => 'sometimes|string|max:255',
            'contact_person' => 'sometimes|string|max:255',
            'email'          => 'sometimes|email|max:255',
            'industry'       => 'sometimes|string|max:100',
            'company_size'   => 'sometimes|string|max:30',

            // ── Address ───────────────────────────────────────────────
            'barangay'       => 'nullable|string|max:100',
            'city'           => 'sometimes|string|max:100',
            'province'       => 'nullable|string|max:100',
            'address_full'   => 'nullable|string|max:255',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
            'map_visible'    => 'sometimes|boolean',

            // ── Contact ───────────────────────────────────────────────
            'phone'          => 'sometimes|string|max:20',
            'tin'            => 'nullable|string|max:50',
            'website'        => 'nullable|string|max:255',

            // ── Extended Profile ──────────────────────────────────────
            'tagline'        => 'nullable|string|max:500',
            'about'          => 'nullable|string',
            'business_type'  => 'nullable|string|max:100',
            'founded'        => 'nullable|integer|min:1900|max:' . date('Y'),
            'perks'          => 'nullable|array',
            'perks.*'        => 'string|max:100',
        ]);

        // Auto-build address_full if not provided
        if (!isset($validated['address_full'])) {
            $parts = array_filter([
                $validated['barangay'] ?? $employer->barangay,
                $validated['city']     ?? $employer->city,
                $validated['province'] ?? $employer->province,
            ])  ;
            if ($parts) {
                $validated['address_full'] = implode(', ', $parts);
            }
        }

        $employer->update($validated);

        return response()->json([
            'success' => true,
            'data'    => new EmployerResource($employer),
            'message' => 'Profile updated successfully',
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $employer = $request->user();

        if (!Hash::check($request->current_password, $employer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $employer->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $employer = $request->user();

        if ($employer->photo) {
            Storage::disk('public')->delete($employer->photo);
        }

        $path = $request->file('photo')->store("employers/photos/{$employer->id}", 'public');

        $employer->update(['photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'data'    => [
                'photo' => PublicStorageUrl::fromRequest($request, $path),
            ],
        ]);
    }

    public function uploadDocuments(Request $request)
    {
        $employer = $request->user();

        $request->validate([
            'biz_permit' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'bir_cert'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $paths = [];

        foreach (['biz_permit' => 'biz_permit_path', 'bir_cert' => 'bir_cert_path'] as $field => $column) {
            if ($request->hasFile($field)) {
                // delete old file first
                if ($employer->$column) {
                    Storage::disk('public')->delete($employer->$column);
                }
                $path = $request->file($field)->store(
                    "employer-docs/{$employer->id}", 'public'
                );
                $employer->update([$column => $path]);
                $paths[$field] = PublicStorageUrl::fromRequest($request, $path);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => ['paths' => $paths],
            'message' => 'Documents uploaded successfully',
        ]);
    }
}