<?php

namespace App\Http\Controllers\Api\Jobseeker;

use App\Http\Controllers\Controller;
use App\Models\JobseekerSkill;
use App\Support\JobseekerPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class JobseekerProfileController extends Controller
{
    public function show(Request $request)
    {
        $jobseeker = $request->user()->load('skills');
        
        return response()->json([
            'success' => true,
            'data' => $jobseeker,
        ]);
    }

    public function update(Request $request)
    {
        $jobseeker = $request->user();
        
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'contact' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
            'email' => 'sometimes|email|unique:jobseekers,email,' . $jobseeker->id,
            'sex' => 'sometimes|in:male,female',
            'date_of_birth' => 'sometimes|date',
            'bio' => 'nullable|string',
            'education_level' => 'sometimes|nullable|string|max:120|in:No Formal Education,Elementary Level,Elementary Graduate,Secondary Level,Secondary Graduate,Tertiary Level,Tertiary Graduate',
            'job_experience' => 'sometimes|nullable|string|max:1000',
            'province_code' => 'sometimes|nullable|string|max:20',
            'province_name' => 'sometimes|nullable|string|max:120',
            'city_code' => 'sometimes|nullable|string|max:20',
            'city_name' => 'sometimes|nullable|string|max:120',
            'barangay_code' => 'sometimes|nullable|string|max:20',
            'barangay_name' => 'sometimes|nullable|string|max:120',
            'street_address' => 'sometimes|nullable|string|max:255',
            'longitude' => 'nullable|numeric',
            'is_onboarding_done' => 'sometimes|boolean',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $skills = $validated['skills'] ?? null;
            unset($validated['skills']);

            if (!array_key_exists('address', $validated)) {
                $parts = [
                    $validated['street_address'] ?? $jobseeker->street_address,
                    $validated['barangay_name'] ?? $jobseeker->barangay_name,
                    $validated['city_name'] ?? $jobseeker->city_name,
                    $validated['province_name'] ?? $jobseeker->province_name,
                ];
                $parts = array_values(array_filter(array_map(
                    fn ($p) => is_string($p) ? trim($p) : '',
                    $parts
                )));
                if (!empty($parts)) {
                    $validated['address'] = implode(', ', $parts);
                }
            }
            
            $jobseeker->update($validated);
            
            if ($skills !== null) {
                $jobseeker->skills()->delete();
                foreach ($skills as $skill) {
                    JobseekerSkill::create([
                        'jobseeker_id' => $jobseeker->id,
                        'skill' => $skill,
                    ]);
                }
            }
            
            DB::commit();

            if ($skills !== null && !empty($skills)) {
                $jobseeker->load('skills');
                $openJobs = \App\Models\JobListing::where('status', 'Open')->with('skills')->get();
                foreach ($openJobs as $job) {
                    /** @var \App\Models\JobListing $job */
                    $score = \App\Models\Application::calculateMatchScore($jobseeker, $job);
                    if ($score >= 70) {
                        $exists = \App\Models\Notification::where('type', 'match')
                            ->where('created_by', $jobseeker->id)
                            ->where('job_listing_id', $job->id)
                            ->exists();
                            
                        if (!$exists) {
                            $employerNotification = \App\Models\Notification::create([
                                'subject'        => 'New Potential Applicant Match',
                                'message'        => "{$jobseeker->full_name} is a high match ({$score}%) for your {$job->title} position.",
                                'type'           => 'match',
                                'job_listing_id' => $job->id,
                                'recipients'     => 'specific',
                                'scheduled_at'   => null,
                                'sent_at'        => now(),
                                'status'         => 'sent',
                                'created_by'     => null,
                            ]);
                    
                            \App\Models\NotificationRead::create([
                                'notification_id' => $employerNotification->id,
                                'recipient_type'  => 'employer',
                                'recipient_id'    => $job->employer_id,
                                'read_at'         => null,
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $jobseeker->load('skills'),
                'message' => 'Profile updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => JobseekerPassword::createRules(),
        ]);

        $jobseeker = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $jobseeker->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $jobseeker->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    public function uploadResume(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf|max:5120',
        ]);

        $jobseeker = $request->user();

        if ($jobseeker->resume_path) {
            Storage::disk('public')->delete($jobseeker->resume_path);
        }

        $path = $request->file('resume')->store(
            "resumes/{$jobseeker->id}",
            'public'
        );

        $jobseeker->update(['resume_path' => $path]);

        return response()->json([
            'success' => true,
            'data' => [
                'resume_path' => $path,
            ],
            'message' => 'Resume uploaded successfully',
        ]);
    }

    public function uploadCertificate(Request $request)
    {
        $request->validate([
            'certificate' => 'required|file|mimes:pdf|max:5120',
        ]);

        $jobseeker = $request->user();

        if ($jobseeker->certificate_path) {
            Storage::disk('public')->delete($jobseeker->certificate_path);
        }

        $path = $request->file('certificate')->store(
            "certificates/{$jobseeker->id}",
            'public'
        );

        $jobseeker->update(['certificate_path' => $path]);

        return response()->json([
            'success' => true,
            'data' => [
                'certificate_path' => $path,
            ],
            'message' => 'Certificate uploaded successfully',
        ]);
    }

    public function uploadBarangayClearance(Request $request)
    {
        $request->validate([
            'barangay_clearance' => 'required|file|mimes:pdf|max:5120',
        ]);

        $jobseeker = $request->user();

        if ($jobseeker->barangay_clearance_path) {
            Storage::disk('public')->delete($jobseeker->barangay_clearance_path);
        }

        $path = $request->file('barangay_clearance')->store(
            "barangay_clearances/{$jobseeker->id}",
            'public'
        );

        $jobseeker->update(['barangay_clearance_path' => $path]);

        return response()->json([
            'success' => true,
            'data' => [
                'barangay_clearance_path' => $path,
            ],
            'message' => 'Barangay clearance uploaded successfully',
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:3072', // 3MB
        ]);

        $jobseeker = $request->user();

        $path = $request->file('avatar')->store(
            "avatars/jobseekers/{$jobseeker->id}",
            'public'
        );

        $jobseeker->update(['avatar_path' => $path]);

        return response()->json([
            'success' => true,
            'data' => [
                'avatar_path' => $path,
            ],
            'message' => 'Avatar uploaded successfully',
        ]);
    }

    public function avatar(Request $request)
    {
        $jobseeker = $request->user();
        $path = $jobseeker->avatar_path;

        if (!$path) {
            return response()->noContent();
        }

        if (!Storage::disk('public')->exists($path)) {
            return response()->noContent();
        }

        return response()->file(Storage::disk('public')->path($path));
    }

    /**
     * View own document (PDF) with auth — avoids public /storage 403 issues.
     *
     * @param  string  $type  resume|certificate|clearance
     */
    public function downloadDocument(Request $request, string $type)
    {
        $column = match ($type) {
            'resume' => 'resume_path',
            'certificate' => 'certificate_path',
            'clearance' => 'barangay_clearance_path',
            default => null,
        };

        if ($column === null) {
            abort(404);
        }

        $jobseeker = $request->user();
        $path = $jobseeker->getAttribute($column);

        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($path);

        return response()->file($fullPath, [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }
}
