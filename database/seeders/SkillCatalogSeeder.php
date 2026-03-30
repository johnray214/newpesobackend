<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SkillCatalogSeeder extends Seeder
{
    private function normaliseName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        return $name;
    }

    private function slugFor(string $name): string
    {
        $slug = Str::slug(mb_strtolower($name));
        return $slug !== '' ? $slug : Str::random(12);
    }

    public function run(): void
    {
        $rawSkills = collect();

        if (Schema::hasTable('job_skills')) {
            $rawSkills = $rawSkills->merge(
                DB::table('job_skills')->select('skill')->pluck('skill')
            );
        }

        if (Schema::hasTable('jobseeker_skills')) {
            $rawSkills = $rawSkills->merge(
                DB::table('jobseeker_skills')->select('skill')->pluck('skill')
            );
        }

        $uniqueNames = $rawSkills
            ->map(fn ($s) => is_string($s) ? $this->normaliseName($s) : '')
            ->filter(fn ($s) => $s !== '')
            ->unique(fn ($s) => mb_strtolower($s))
            ->values();

        $slugToId = [];
        foreach ($uniqueNames as $name) {
            $slug = $this->slugFor($name);

            // Guard against rare slug collisions (e.g. different unicode forms).
            $base = $slug;
            $i = 2;
            while (isset($slugToId[$slug]) || Skill::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }

            $skill = Skill::create([
                'name' => $name,
                'slug' => $slug,
                'category' => null,
                'is_active' => true,
            ]);
            $slugToId[$slug] = $skill->id;
        }

        // Backfill new pivot tables from old string tables.
        // (We intentionally keep old tables for backward compatibility.)
        if (Schema::hasTable('job_listing_skill_items') && Schema::hasTable('job_skills')) {
            $jobSkills = DB::table('job_skills')->select('job_listing_id', 'skill')->get();
            foreach ($jobSkills as $row) {
                $name = $this->normaliseName((string) $row->skill);
                if ($name === '') continue;

                $skill = Skill::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
                if (!$skill) continue;

                DB::table('job_listing_skill_items')->updateOrInsert(
                    ['job_listing_id' => $row->job_listing_id, 'skill_id' => $skill->id],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        if (Schema::hasTable('jobseeker_skill_items') && Schema::hasTable('jobseeker_skills')) {
            $jsSkills = DB::table('jobseeker_skills')->select('jobseeker_id', 'skill')->get();
            foreach ($jsSkills as $row) {
                $name = $this->normaliseName((string) $row->skill);
                if ($name === '') continue;

                $skill = Skill::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
                if (!$skill) continue;

                DB::table('jobseeker_skill_items')->updateOrInsert(
                    ['jobseeker_id' => $row->jobseeker_id, 'skill_id' => $skill->id],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }
}

