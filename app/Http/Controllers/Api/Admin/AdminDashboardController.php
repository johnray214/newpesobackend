<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Employer;
use App\Models\Event;
use App\Models\JobListing;
use App\Models\Jobseeker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'monthly');
        $year   = now()->year;

        [$from, $to, $prevFrom, $prevTo] = $this->resolvePeriod($period, $request);

        // ── Stat Cards ───────────────────────────────────────────────
        $totalJobseekers  = Jobseeker::withTrashed()->whereBetween('created_at', [$from, $to])->count();
        $totalEmployers   = Employer::withTrashed()->whereBetween('created_at',  [$from, $to])->count();
        $totalJobListings = JobListing::withTrashed()->whereBetween('created_at', [$from, $to])->count();
        $totalPlacements  = Application::where('status', 'hired')
                                ->whereBetween('updated_at', [$from, $to])->count();

        // Previous period for trend comparison
        $prevJobseekers  = Jobseeker::withTrashed()->whereBetween('created_at', [$prevFrom, $prevTo])->count();
        $prevEmployers   = Employer::withTrashed()->whereBetween('created_at',  [$prevFrom, $prevTo])->count();
        $prevListings    = JobListing::withTrashed()->whereBetween('created_at', [$prevFrom, $prevTo])->count();
        $prevPlacements  = Application::where('status', 'hired')
                                ->whereBetween('updated_at', [$prevFrom, $prevTo])->count();

        // Safe % change: ((current - prev) / max(prev, 1)) * 100, capped at ±999%
        $trendPct = fn($cur, $prev) => $prev > 0
            ? min(round(abs($cur - $prev) / $prev * 100), 999)
            : ($cur > 0 ? 100 : 0);

        $periodLabel = $this->periodLabel($period, $from, $to);

        $stats = [
            [
                'label'    => 'Registered Jobseekers',
                'value'    => number_format($totalJobseekers),
                'sub'      => $periodLabel,
                'trendVal' => $trendPct($totalJobseekers, $prevJobseekers) . '%',
                'trendUp'  => $totalJobseekers >= $prevJobseekers,
            ],
            [
                'label'    => 'Active Employers',
                'value'    => number_format($totalEmployers),
                'sub'      => $periodLabel,
                'trendVal' => $trendPct($totalEmployers, $prevEmployers) . '%',
                'trendUp'  => $totalEmployers >= $prevEmployers,
            ],
            [
                'label'    => 'Job Vacancies',
                'value'    => number_format($totalJobListings),
                'sub'      => $periodLabel,
                'trendVal' => $trendPct($totalJobListings, $prevListings) . '%',
                'trendUp'  => $totalJobListings >= $prevListings,
            ],
            [
                'label'    => 'Successful Placements',
                'value'    => number_format($totalPlacements),
                'sub'      => $periodLabel,
                'trendVal' => $trendPct($totalPlacements, $prevPlacements) . '%',
                'trendUp'  => $totalPlacements >= $prevPlacements,
            ],
        ];

        // ── Registration Chart ───────────────────────────────────────
        $registrationChart = $this->buildRegistrationChart($period, $from, $to);

        // ── Placement Chart ──────────────────────────────────────────
        $placementChart = $this->buildPlacementChart($period, $from, $to);

        // ── Trending Jobs ─────────────────────────────────────────────
        $trendingJobs = JobListing::select(
                'job_listings.id',
                'job_listings.title',
                DB::raw('employers.industry'),
                DB::raw('COUNT(DISTINCT applications.id) as apps'),
                'job_listings.slots'
            )
            ->leftJoin('applications', 'applications.job_listing_id', '=', 'job_listings.id')
            ->leftJoin('employers', 'employers.id', '=', 'job_listings.employer_id')
            ->where('job_listings.status', 'open')
            ->groupBy('job_listings.id', 'job_listings.title', 'employers.industry', 'job_listings.slots')
            ->orderByDesc('apps')
            ->limit(6)
            ->get()
            ->map(fn($j) => [
                'title'     => $j->title,
                'industry'  => $j->industry ?? 'General',
                'vacancies' => (int) $j->slots,
                'apps'      => (int) $j->apps,
            ]);

        // ── Trending Skills ───────────────────────────────────────────
        $trendingSkills = DB::table('job_skills')
            ->select('skill as name', DB::raw('COUNT(*) as count'))
            ->join('job_listings', 'job_listings.id', '=', 'job_skills.job_listing_id')
            ->where('job_listings.status', 'open')
            ->groupBy('skill')
            ->orderByDesc('count')
            ->limit(6)
            ->get()
            ->map(fn($s) => [
                'name'  => $s->name,
                'count' => (int) $s->count,
            ]);

        // ── Skill Gaps ────────────────────────────────────────────────
        $totalOpenJobs   = max(JobListing::where('status', 'open')->count(), 1);
        $totalJsCount    = max(Jobseeker::count(), 1);

        $demandRaw = DB::table('job_skills')
            ->select('skill', DB::raw('COUNT(DISTINCT job_listing_id) as cnt'))
            ->join('job_listings', 'job_listings.id', '=', 'job_skills.job_listing_id')
            ->where('job_listings.status', 'open')
            ->groupBy('skill')
            ->orderByDesc('cnt')
            ->limit(6)
            ->get()->keyBy('skill');

        $supplyRaw = DB::table('jobseeker_skills')
            ->select('skill', DB::raw('COUNT(DISTINCT jobseeker_id) as cnt'))
            ->whereIn('skill', $demandRaw->keys())
            ->groupBy('skill')
            ->get()->keyBy('skill');

        $skillGaps = $demandRaw->map(fn($d, $skill) => [
            'skill'     => $skill,
            'required'  => min((int) round($d->cnt / $totalOpenJobs * 100), 100),
            'available' => min((int) round(($supplyRaw[$skill]->cnt ?? 0) / $totalJsCount * 100), 100),
        ])->values();

        // ── Recent Applicants ─────────────────────────────────────────
        $recentApplicants = Application::with([
            'jobseeker' => fn($q) => $q->withTrashed(),
            'jobListing' => fn($q) => $q->withTrashed()
        ])
            ->orderByDesc('applied_at')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'name'        => $a->jobseeker ? $a->jobseeker->fullName() : 'Unknown',
                'location'    => optional($a->jobseeker)->city ?? '',
                'skill'       => $a->jobseeker 
                                    ? ($a->jobseeker->skills()->pluck('skill')->first() ?? $a->jobseeker->primary_skill ?? 'General')
                                    : 'General',
                'job'         => optional($a->jobListing)->title ?? 'Unknown Job',
                'date'        => optional($a->applied_at)->format('M d, Y'),
                'status'      => ucfirst($a->status),
                'statusClass' => strtolower($a->status),
            ]);

        // ── Upcoming Events ───────────────────────────────────────────
        $upcomingEvents = Event::where('event_date', '>=', now())
            ->orderBy('event_date')
            ->limit(3)
            ->get()
            ->map(fn($e) => [
                'day'      => $e->event_date->format('d'),
                'month'    => strtoupper($e->event_date->format('M')),
                'title'    => $e->title,
                'location' => $e->location ?? '',
                'slots'    => $e->slots ?? 0,
                'type'     => $e->type ?? 'Event',
            ]);

        // ── Top Employers ─────────────────────────────────────────────
        $topEmployers = Employer::select(
                'employers.id', 'employers.company_name', 'employers.industry',
                DB::raw('COUNT(job_listings.id) as vacancies')
            )
            ->leftJoin('job_listings', function ($j) {
                $j->on('job_listings.employer_id', '=', 'employers.id')
                  ->where('job_listings.status', 'open');
            })
            ->where('employers.status', 'verified')
            ->groupBy('employers.id', 'employers.company_name', 'employers.industry')
            ->orderByDesc('vacancies')
            ->limit(4)
            ->get()
            ->map(fn($e) => [
                'name'      => $e->company_name,
                'industry'  => $e->industry ?? '',
                'vacancies' => (int) $e->vacancies,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'stats'             => $stats,
                'registrationChart' => $registrationChart,
                'placementChart'    => $placementChart,
                'trendingJobs'      => $trendingJobs,
                'trendingSkills'    => $trendingSkills,
                'skillGaps'         => $skillGaps,
                'recentApplicants'  => $recentApplicants,
                'upcomingEvents'    => $upcomingEvents,
                'topEmployers'      => $topEmployers,
            ],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Returns [from, to, prevFrom, prevTo] as Carbon instances.
     */
    private function resolvePeriod(string $period, Request $request): array
    {
        $now = now();

        switch ($period) {
            case 'weekly':
                $from    = $now->copy()->startOfWeek();
                $to      = $now->copy()->endOfWeek();
                $prevFrom = $from->copy()->subWeek();
                $prevTo   = $to->copy()->subWeek();
                break;

            case 'yearly':
                $from    = $now->copy()->startOfYear();
                $to      = $now->copy()->endOfYear();
                $prevFrom = $from->copy()->subYear();
                $prevTo   = $to->copy()->subYear();
                break;

            case 'custom':
                $from    = Carbon::parse($request->input('from', $now->copy()->startOfMonth()));
                $to      = Carbon::parse($request->input('to',   $now))->endOfDay();
                $diff    = $from->diffInDays($to);
                $prevFrom = $from->copy()->subDays($diff + 1);
                $prevTo   = $from->copy()->subDay()->endOfDay();
                break;

            default: // monthly
                $from    = $now->copy()->startOfMonth();
                $to      = $now->copy()->endOfMonth();
                $prevFrom = $from->copy()->subMonth()->startOfMonth();
                $prevTo   = $from->copy()->subMonth()->endOfMonth();
                break;
        }

        return [$from, $to, $prevFrom, $prevTo];
    }

    private function periodLabel(string $period, Carbon $from, Carbon $to): string
    {
        return match ($period) {
            'weekly'  => 'This week (' . $from->format('M d') . '–' . $to->format('M d') . ')',
            'yearly'  => 'This year (' . $from->format('Y') . ')',
            'custom'  => $from->format('M d') . ' – ' . $to->format('M d, Y'),
            default   => 'This month (' . $from->format('M Y') . ')',
        };
    }

    /**
     * Build registration chart data points based on period.
     * weekly  → 7 days
     * monthly → 12 months of current year
     * yearly  → last 5 years
     * custom  → daily if ≤31 days, weekly if ≤90 days, monthly otherwise
     */
    private function buildRegistrationChart(string $period, Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        $buckets = $this->buildBuckets($period, $from, $to);

        foreach ($buckets as &$b) {
            $b['jobseekers'] = Jobseeker::withTrashed()->whereBetween('created_at', [$b['start'], $b['end']])->count();
            $b['employers']  = Employer::withTrashed()->whereBetween('created_at',  [$b['start'], $b['end']])->count();
            unset($b['start'], $b['end']);
        }

        return collect($buckets);
    }

    private function buildPlacementChart(string $period, Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        $buckets = $this->buildBuckets($period, $from, $to);
        foreach ($buckets as &$b) {
            $b['placement'] = Application::where('status', 'hired')
                ->whereBetween('updated_at', [$b['start'], $b['end']])
                ->count();
                
            $b['processing'] = Application::whereIn('status', ['shortlisted', 'interview'])
                ->whereBetween('updated_at', [$b['start'], $b['end']])
                ->count();
                
            $b['registration'] = Application::where('status', 'reviewing')
                ->whereBetween('updated_at', [$b['start'], $b['end']])
                ->count();
                
            $b['rejection'] = Application::where('status', 'rejected')
                ->whereBetween('updated_at', [$b['start'], $b['end']])
                ->count();
                
            unset($b['start'], $b['end']);
        }

        return collect($buckets);
    }

    /**
     * Build time buckets with label, start, end based on period.
     */
    private function buildBuckets(string $period, Carbon $from, Carbon $to): array
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
            // Last 5 years including current
            for ($y = 4; $y >= 0; $y--) {
                $yr = now()->year - $y;
                $buckets[] = [
                    'label' => (string) $yr,
                    'start' => Carbon::create($yr, 1, 1)->startOfDay(),
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
                    $weekEnd = $cur->copy()->endOfWeek();
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
            // Monthly — all 12 months of current year
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
}