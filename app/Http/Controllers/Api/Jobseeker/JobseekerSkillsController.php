<?php

namespace App\Http\Controllers\Api\Jobseeker;

use App\Http\Controllers\Controller;
use App\Models\JobseekerSkillItem;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobseekerSkillsController extends Controller
{
    public function index(Request $request)
    {
        $jobseeker = $request->user();

        $items = JobseekerSkillItem::query()
            ->where('jobseeker_id', $jobseeker->id)
            ->with('skill:id,name,category')
            ->get([
                'id',
                'jobseeker_id',
                'skill_id',
                'proficiency',
                'years_experience',
            ]);

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($i) {
                return [
                    'skill_id' => $i->skill_id,
                    'name' => $i->skill?->name,
                    'category' => $i->skill?->category,
                    'proficiency' => $i->proficiency,
                    'years_experience' => $i->years_experience,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $jobseeker = $request->user();

        $validated = $request->validate([
            'skills' => 'sometimes|array',
            'skills.*.skill_id' => 'required_with:skills|integer|exists:skills,id',
            'skills.*.proficiency' => 'nullable|in:beginner,intermediate,advanced',
            'skills.*.years_experience' => 'nullable|integer|min:0',

            // Backward/alternate format:
            'skill_ids' => 'sometimes|array',
            'skill_ids.*' => 'integer|exists:skills,id',
        ]);

        DB::beginTransaction();
        try {
            $skillIds = [];
            JobseekerSkillItem::query()
                ->where('jobseeker_id', $jobseeker->id)
                ->delete();

            if (isset($validated['skills'])) {
                $skillIds = array_map(fn ($s) => (int) $s['skill_id'], $validated['skills']);
                $rows = array_map(function ($s) use ($jobseeker) {
                    return [
                        'jobseeker_id' => $jobseeker->id,
                        'skill_id' => $s['skill_id'],
                        'proficiency' => $s['proficiency'] ?? null,
                        'years_experience' => $s['years_experience'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $validated['skills']);

                if (!empty($rows)) {
                    JobseekerSkillItem::query()->insert($rows);
                }
            } elseif (isset($validated['skill_ids'])) {
                $skillIds = array_map(fn ($id) => (int) $id, $validated['skill_ids']);
                $rows = [];
                foreach ($validated['skill_ids'] as $skillId) {
                    $rows[] = [
                        'jobseeker_id' => $jobseeker->id,
                        'skill_id' => $skillId,
                        'proficiency' => null,
                        'years_experience' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (!empty($rows)) {
                    JobseekerSkillItem::query()->insert($rows);
                }
            }

            // Compatibility: update the legacy string-based `jobseeker_skills` table too,
            // because your current match scoring uses Jobseeker->skills (string skills).
            DB::table('jobseeker_skills')->where('jobseeker_id', $jobseeker->id)->delete();

            if (!empty($skillIds)) {
                $idToName = Skill::query()
                    ->whereIn('id', $skillIds)
                    ->pluck('name', 'id')
                    ->toArray(); // [id => name]

                $legacyRows = [];
                foreach ($skillIds as $skillId) {
                    if (!isset($idToName[$skillId])) continue;
                    $legacyRows[] = [
                        'jobseeker_id' => $jobseeker->id,
                        'skill' => trim((string) $idToName[$skillId]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($legacyRows)) {
                    DB::table('jobseeker_skills')->insert($legacyRows);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Skills saved successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save skills',
            ], 500);
        }
    }
}

