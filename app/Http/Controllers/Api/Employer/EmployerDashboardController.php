<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Jobseeker;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $employer = $request->user();

        [$from, $to, $prevFrom, $prevTo] = $this->resolvePeriod($request);

        // ── Job Listings (not period-filtered — always show current state) ──
        $jobListings = $employer->jobListings()
            ->withCount('applications')
            ->with('skills')
            ->get();

        $openJobs      = $jobListings->where('status', 'open')->count();
        $totalJobCount = $jobListings->count();

        // ── Applications in period ────────────────────────────────────────
        $baseQuery = fn () => Application::whereHas('jobListing', fn ($q) =>
            $q->where('employer_id', $employer->id)
        );

        $totalApplications = $baseQuery()->whereBetween('applied_at', [$from, $to])->count();
        $prevApplications  = $baseQuery()->whereBetween('applied_at', [$prevFrom, $prevTo])->count();

        $applicationStatusCounts = $baseQuery()
            ->whereBetween('applied_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $hiredCount     = $applicationStatusCounts['hired']     ?? 0;
        $reviewingCount = $applicationStatusCounts['reviewing'] ?? 0;
        $prevHired      = $baseQuery()->where('status', 'hired')->whereBetween('updated_at', [$prevFrom, $prevTo])->count();
        $prevReviewing  = $baseQuery()->where('status', 'reviewing')->whereBetween('applied_at', [$prevFrom, $prevTo])->count();

        // Safe % trend helper
        $trendPct = fn ($cur, $prev) => $prev > 0
            ? round(($cur - $prev) / $prev * 100)
            : ($cur > 0 ? 100 : 0);

        // ── Chart Data ────────────────────────────────────────────────────
        $chartData = $this->buildChartData($request, $employer->id, $from, $to);

        // ── Active Jobs ───────────────────────────────────────────────────
        $activeJobs = $jobListings->where('status', 'open')
            ->map(fn ($job) => [
                'id'         => $job->id,
                'title'      => $job->title,
                'applicants' => $job->applications_count,
                'slots'      => $job->slots ?? 10,
            ])->values();

        // ── Potential Applicants ──────────────────────────────────────────
        $potentialApplicants = $this->buildPotentialApplicants($jobListings);

        // ── Recent Applications ───────────────────────────────────────────
        $recentApplications = Application::whereHas('jobListing', fn ($q) =>
                $q->where('employer_id', $employer->id)
            )
            ->with(['jobseeker' => fn($q) => $q->withTrashed()->with('skills'), 'jobListing:id,title'])
            ->orderByDesc('applied_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_jobs'                => $totalJobCount,
                    'open_jobs'                 => $openJobs,
                    'total_applications'        => $totalApplications,
                    'application_status_counts' => $applicationStatusCounts,
                    'trends' => [
                        'applications' => $trendPct($totalApplications, $prevApplications),
                        'jobs'         => $openJobs,   // raw count, no prev comparison needed
                        'hired'        => $trendPct($hiredCount,     $prevHired),
                        'reviewing'    => $trendPct($reviewingCount, $prevReviewing),
                    ],
                ],
                'chart_data'            => $chartData,
                'active_jobs'           => $activeJobs,
                'potential_applicants'  => array_slice($potentialApplicants, 0, 10),
                'recent_applications'   => $recentApplications,
            ],
        ]);
    }

    // ── Period resolver ───────────────────────────────────────────────────

    private function resolvePeriod(Request $request): array
    {
        $period = $request->input('period', 'monthly');
        $now    = now();

        switch ($period) {
            case 'weekly':
                $from     = $now->copy()->startOfWeek();
                $to       = $now->copy()->endOfWeek();
                $prevFrom = $from->copy()->subWeek();
                $prevTo   = $to->copy()->subWeek();
                break;

            case 'yearly':
                $from     = $now->copy()->startOfYear();
                $to       = $now->copy()->endOfYear();
                $prevFrom = $from->copy()->subYear();
                $prevTo   = $to->copy()->subYear();
                break;

            case 'custom':
                $from     = Carbon::parse($request->input('from', $now->copy()->startOfMonth()))->startOfDay();
                $to       = Carbon::parse($request->input('to',   $now))->endOfDay();
                $diff     = $from->diffInDays($to);
                $prevFrom = $from->copy()->subDays($diff + 1)->startOfDay();
                $prevTo   = $from->copy()->subDay()->endOfDay();
                break;

            default: // monthly
                $from     = $now->copy()->startOfMonth();
                $to       = $now->copy()->endOfMonth();
                $prevFrom = $from->copy()->subMonth()->startOfMonth();
                $prevTo   = $from->copy()->subMonth()->endOfMonth();
                break;
        }

        return [$from, $to, $prevFrom, $prevTo];
    }

    // ── Chart builder ─────────────────────────────────────────────────────

    private function buildChartData(Request $request, int $employerId, $from, $to): array
    {
        $period  = $request->input('period', 'monthly');
        $buckets = $this->buildBuckets($period, $from, $to);

        foreach ($buckets as &$b) {
            $b['applications'] = Application::whereHas('jobListing', fn ($q) =>
                    $q->where('employer_id', $employerId)
                )->whereBetween('applied_at', [$b['start'], $b['end']])->count();

            $b['hired'] = Application::whereHas('jobListing', fn ($q) =>
                    $q->where('employer_id', $employerId)
                )->where('status', 'hired')
                 ->whereBetween('updated_at', [$b['start'], $b['end']])->count();

            unset($b['start'], $b['end']);
        }

        return $buckets;
    }

    private function buildBuckets(string $period, $from, $to): array
    {
        $buckets = [];

        if ($period === 'weekly') {
            // 7 individual days
            for ($i = 0; $i < 7; $i++) {
                $day = $from->copy()->addDays($i);
                $buckets[] = [
                    'label' => $day->format('D'),
                    'start' => $day->copy()->startOfDay(),
                    'end'   => $day->copy()->endOfDay(),
                ];
            }

        } elseif ($period === 'yearly') {
            // Last 5 years
            for ($y = 4; $y >= 0; $y--) {
                $yr = now()->year - $y;
                $buckets[] = [
                    'label' => (string) $yr,
                    'start' => Carbon::create($yr, 1,  1)->startOfDay(),
                    'end'   => Carbon::create($yr, 12, 31)->endOfDay(),
                ];
            }

        } elseif ($period === 'custom') {
            $diff = $from->diffInDays($to);

            if ($diff <= 31) {
                // Daily
                $cur = $from->copy();
                while ($cur->lte($to)) {
                    $buckets[] = [
                        'label' => $cur->format('M d'),
                        'start' => $cur->copy()->startOfDay(),
                        'end'   => $cur->copy()->endOfDay(),
                    ];
                    $cur->addDay();
                }
            } elseif ($diff <= 90) {
                // Weekly
                $cur = $from->copy()->startOfWeek();
                while ($cur->lte($to)) {
                    $weekEnd   = $cur->copy()->endOfWeek();
                    $buckets[] = [
                        'label' => $cur->format('M d'),
                        'start' => $cur->copy(),
                        'end'   => $weekEnd->lte($to) ? $weekEnd : $to->copy()->endOfDay(),
                    ];
                    $cur->addWeek();
                }
            } else {
                // Monthly
                $cur = $from->copy()->startOfMonth();
                while ($cur->lte($to)) {
                    $buckets[] = [
                        'label' => $cur->format('M Y'),
                        'start' => $cur->copy()->startOfMonth(),
                        'end'   => $cur->copy()->endOfMonth(),
                    ];
                    $cur->addMonth();
                }
            }

        } else {
            // Monthly default — all 12 months of current year
            $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            $year   = now()->year;
            for ($m = 1; $m <= 12; $m++) {
                $buckets[] = [
                    'label' => $months[$m - 1],
                    'start' => Carbon::create($year, $m, 1)->startOfMonth(),
                    'end'   => Carbon::create($year, $m, 1)->endOfMonth(),
                ];
            }
        }

        return $buckets;
    }

    // ── Potential applicants ──────────────────────────────────────────────

    private function buildPotentialApplicants($jobListings): array
    {
        $employerJobSkills = $jobListings
            ->flatMap(fn ($job) => $job->skills->pluck('skill'))
            ->unique()
            ->toArray();

        if (empty($employerJobSkills)) return [];

        $jobseekers = Jobseeker::whereHas('skills', fn ($q) =>
                $q->whereIn('skill', $employerJobSkills)
            )->with('skills')->limit(20)->get();

        $potentialApplicants = [];

        foreach ($jobseekers as $js) {
            $jsSkills = $js->skills->pluck('skill')->toArray();

            $bestJob   = null;
            $bestScore = 0;

            foreach ($jobListings as $job) {
                $jobSkills = $job->skills->pluck('skill')->toArray();
                if (empty($jobSkills)) continue;

                $matchCount = count(array_intersect($jsSkills, $jobSkills));
                $score      = ($matchCount / count($jobSkills)) * 100;

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestJob   = $job;
                }
            }

            if ($bestJob && $bestScore > 0) {
                $potentialApplicants[] = [
                    'id'        => $js->id,
                    'name'      => $js->fullName(),
                    'skills'    => array_slice($jsSkills, 0, 3),
                    'score'     => round($bestScore),
                    'job_id'    => $bestJob->id,
                    'job_title' => $bestJob->title,
                    'location'  => $js->address         ?? 'Unknown',
                    'education' => $js->education_level ?? 'Not specified',
                ];
            }
        }

        usort($potentialApplicants, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $potentialApplicants;
    }
}