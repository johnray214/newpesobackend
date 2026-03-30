<?php

namespace App\Http\Controllers\Api\Jobseeker;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobseekerJobListingController extends Controller
{
    public function index(Request $request)
    {
        // Load full employer row so `photo` is always present in JSON (avoid partial select quirks).
        $query = JobListing::with(['employer', 'skills'])
            ->where('status', 'open');
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('skills', function ($sq) use ($search) {
                      $sq->where('skill', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }
        
        if ($request->has('skills')) {
            $skills = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
            $query->whereHas('skills', function ($q) use ($skills) {
                $q->whereIn('skill', $skills);
            });
        }

        $jobseeker = $request->user();
        
        // Haversine distance calculation if jobseeker has location
        if ($request->has('distance') && $jobseeker->latitude && $jobseeker->longitude) {
            $distance = $request->distance; // in kilometers
            $lat = $jobseeker->latitude;
            $lng = $jobseeker->longitude;
            
            $query->selectRaw("*, (
                6371000 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )
            ) AS distance_meters", [$lat, $lng, $lat])
            ->having('distance_meters', '<=', $distance * 1000)
            ->orderBy('distance_meters');
        } else {
            $query->orderByDesc('posted_date');
        }

        $jobListings = $query->paginate(15);

        // Match % (employer_photo_url comes from JobListing model accessor + $appends).
        $jobListings->getCollection()->transform(function ($job) use ($jobseeker) {
            if ($jobseeker) {
                $job->setAttribute('match_percentage', Application::calculateMatchScore($jobseeker, $job));
            } else {
                $job->setAttribute('match_percentage', 0);
            }

            return $job;
        });

        return response()->json([
            'success' => true,
            'data' => $jobListings,
        ]);
    }

    public function show(Request $request, $id)
    {
        $jobseeker = $request->user();
        
        $jobListing = JobListing::with(['employer', 'skills'])->findOrFail($id);
        
        // Calculate match score
        $matchScore = Application::calculateMatchScore($jobseeker, $jobListing);
        
        // Check if already applied
        $hasApplied = Application::where('job_listing_id', $id)
            ->where('jobseeker_id', $jobseeker->id)
            ->exists();
        
        // Calculate Haversine distance
        $distanceMeters = null;
        if ($jobseeker->latitude && $jobseeker->longitude && $jobListing->employer->latitude && $jobListing->employer->longitude) {
            $distanceMeters = $this->haversineDistance(
                $jobseeker->latitude,
                $jobseeker->longitude,
                $jobListing->employer->latitude,
                $jobListing->employer->longitude
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'job_listing' => $jobListing,
                'match_score' => $matchScore,
                'has_applied' => $hasApplied,
                'distance_meters' => $distanceMeters,
            ],
        ]);
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return round($earthRadius * $c);
    }
}
