<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\JobSkill;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployerJobListingController extends Controller
{
    /**
     * Normalise type/status from the frontend to lowercase for DB storage.
     * DB enum was updated to Title Case but we accept both.
     */
    private function normalise(string $value): string
    {
        return strtolower(trim($value));
    }

    private function normaliseSkillInput(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /**
     * Safe #2 flow:
     * - snap to existing catalog skill when close enough
     * - otherwise auto-create a new catalog skill
     * Returns canonical skill name.
     */
    private function resolveSkillName(string $rawSkill): ?string
    {
        $input = $this->normaliseSkillInput($rawSkill);
        if ($input === '') return null;

        $inputLower = mb_strtolower($input);

        // Exact (case-insensitive) catalog match first.
        $exact = Skill::query()
            ->whereRaw('LOWER(name) = ?', [$inputLower])
            ->first();
        if ($exact) {
            return $exact->name;
        }

        // Fuzzy snap to nearest catalog skill (typo/case variations).
        $all = Skill::query()->where('is_active', true)->get(['id', 'name']);
        $best = null;
        $bestDist = PHP_INT_MAX;
        $bestPct = 0.0;

        foreach ($all as $skill) {
            $candidate = (string) $skill->name;
            $candidateLower = mb_strtolower($candidate);
            $dist = levenshtein($inputLower, $candidateLower);
            similar_text($inputLower, $candidateLower, $pct);

            if ($dist < $bestDist || ($dist === $bestDist && $pct > $bestPct)) {
                $bestDist = $dist;
                $bestPct = $pct;
                $best = $candidate;
            }
        }

        $len = max(mb_strlen($inputLower), 1);
        $distanceThreshold = $len <= 5 ? 1 : ($len <= 10 ? 2 : 3);
        $isCloseEnough = $best !== null && ($bestDist <= $distanceThreshold || $bestPct >= 88.0);

        if ($isCloseEnough) {
            return $best;
        }

        // No close match: allow custom skill, but add it into the catalog.
        $slugBase = Str::slug($inputLower);
        $slug = $slugBase !== '' ? $slugBase : Str::random(12);
        $i = 2;
        while (Skill::where('slug', $slug)->exists()) {
            $slug = $slugBase !== '' ? "{$slugBase}-{$i}" : Str::random(12);
            $i++;
        }

        $created = Skill::create([
            'name' => $input,
            'slug' => $slug,
            'category' => null,
            'is_active' => true,
        ]);

        return $created->name;
    }

    private function resolveSkillNames(array $skills): array
    {
        $canonical = [];
        foreach ($skills as $raw) {
            if (!is_string($raw)) continue;
            $name = $this->resolveSkillName($raw);
            if (!$name) continue;
            $canonical[mb_strtolower($name)] = $name; // de-dupe by canonical value
        }
        return array_values($canonical);
    }

    public function index(Request $request)
    {
        $employer = $request->user();

        $query = JobListing::where('employer_id', $employer->id);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== '') {
            // Accept both 'open' and 'Open'
            $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)]);
        }

        if ($request->has('type') && $request->type !== '') {
            $query->whereRaw('LOWER(type) = ?', [strtolower($request->type)]);
        }

        $jobListings = $query
            ->withCount([
                'applications',
                'applications as hired_count' => fn ($q) => $q->where('status', 'hired'),
            ])
            ->with('skills')
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data'    => $jobListings,
        ]);
    }

    public function show(Request $request, $id)
    {
        $employer = $request->user();

        $jobListing = JobListing::query()
            ->with(['skills', 'applications.jobseeker:id,first_name,last_name'])
            ->withCount([
                'applications',
                'applications as hired_count' => fn ($q) => $q->where('status', 'hired'),
            ])
            ->where('employer_id', $employer->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $jobListing,
        ]);
    }

    public function store(Request $request)
    {
        $employer = $request->user();

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'type'                => 'required|string|max:50',
            'location'            => 'required|string|max:255',
            'salary'              => 'nullable|string|max:100',
            'salary_range'        => 'nullable|string|max:100',
            'education_level'     => 'nullable|string|max:80',
            'experience_required' => 'nullable|string|max:80',
            'description'         => 'required|string',
            'slots'               => 'required|integer|min:1',
            'status'              => 'sometimes|string|max:20',
            'posted_date'         => 'nullable|date',
            'deadline'            => 'nullable|date',
            'daysLeft'            => 'nullable|integer|min:0',
            'skills'              => 'nullable|array',
            'skills.*'            => 'string|max:100',
        ]);

        // Map frontend `salary` field to `salary_range`
        if (!isset($validated['salary_range']) && isset($validated['salary'])) {
            $validated['salary_range'] = $validated['salary'];
        }
        unset($validated['salary']);

        // Map frontend `daysLeft` to an actual `deadline` date
        if (!isset($validated['deadline']) && isset($validated['daysLeft']) && $validated['daysLeft'] > 0) {
            $validated['deadline'] = now()->addDays((int) $validated['daysLeft'])->toDateString();
        }
        unset($validated['daysLeft']);

        // Normalise type and status to lowercase to match DB enum
        $validated['type']   = $this->normalise($validated['type']);
        $validated['status'] = isset($validated['status']) ? $this->normalise($validated['status']) : 'open';

        // Auto-set posted_date when status is open/Open
        if ($validated['status'] === 'open' && empty($validated['posted_date'])) {
            $validated['posted_date'] = now();
        }

        $validated['employer_id'] = $employer->id;

        DB::beginTransaction();
        try {
            $jobListing = JobListing::create($validated);

            if (!empty($validated['skills'])) {
                foreach ($this->resolveSkillNames($validated['skills']) as $skill) {
                    JobSkill::create([
                        'job_listing_id' => $jobListing->id,
                        'skill'          => $skill,
                    ]);
                }
            }

            DB::commit();

            $jobListing->refresh();
            $jobListing->load('skills');
            $jobListing->loadCount([
                'applications',
                'applications as hired_count' => fn ($q) => $q->where('status', 'hired'),
            ]);

            return response()->json([
                'success' => true,
                'data'    => $jobListing,
                'message' => 'Job listing created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function update(Request $request, $id)
    {
        $employer   = $request->user();
        $jobListing = JobListing::where('employer_id', $employer->id)->findOrFail($id);

        $validated = $request->validate([
            'title'               => 'sometimes|string|max:255',
            'type'                => 'sometimes|string|max:50',
            'location'            => 'sometimes|string|max:255',
            'salary'              => 'nullable|string|max:100',
            'salary_range'        => 'nullable|string|max:100',
            'education_level'     => 'nullable|string|max:80',
            'experience_required' => 'nullable|string|max:80',
            'description'         => 'sometimes|string',
            'slots'               => 'sometimes|integer|min:1',
            'status'              => 'sometimes|string|max:20',
            'posted_date'         => 'nullable|date',
            'deadline'            => 'nullable|date',
            'daysLeft'            => 'nullable|integer|min:0',
            'skills'              => 'nullable|array',
            'skills.*'            => 'string|max:100',
        ]);

        // Map frontend `salary` field to `salary_range`
        if (!isset($validated['salary_range']) && isset($validated['salary'])) {
            $validated['salary_range'] = $validated['salary'];
        }
        unset($validated['salary']);

        // Map frontend `daysLeft` to an actual `deadline` date
        if (!isset($validated['deadline']) && isset($validated['daysLeft']) && $validated['daysLeft'] > 0) {
            $validated['deadline'] = now()->addDays((int) $validated['daysLeft'])->toDateString();
        }
        unset($validated['daysLeft']);

        // Normalise type and status
        if (isset($validated['type']))   $validated['type']   = $this->normalise($validated['type']);
        if (isset($validated['status'])) $validated['status'] = $this->normalise($validated['status']);

        DB::beginTransaction();
        try {
            $jobListing->update($validated);

            if (isset($validated['skills'])) {
                $jobListing->skills()->delete();
                foreach ($this->resolveSkillNames($validated['skills']) as $skill) {
                    JobSkill::create([
                        'job_listing_id' => $jobListing->id,
                        'skill'          => $skill,
                    ]);
                }
            }

            DB::commit();

            $jobListing->refresh();
            $jobListing->load('skills');
            $jobListing->loadCount([
                'applications',
                'applications as hired_count' => fn ($q) => $q->where('status', 'hired'),
            ]);

            return response()->json([
                'success' => true,
                'data'    => $jobListing,
                'message' => 'Job listing updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy(Request $request, $id)
    {
        $employer   = $request->user();
        $jobListing = JobListing::where('employer_id', $employer->id)->findOrFail($id);
        $jobListing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job listing deleted successfully',
        ]);
    }

    public function close(Request $request, $id)
    {
        $employer   = $request->user();
        $jobListing = JobListing::where('employer_id', $employer->id)->findOrFail($id);
        
        $jobListing->update(['status' => 'closed']);

        return response()->json([
            'success' => true,
            'message' => 'Job listing closed successfully',
        ]);
    }
}
