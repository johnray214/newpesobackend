<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\Jobseeker;
use App\Models\Employer;
use App\Models\JobseekerSkill;
use App\Models\Event;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;

class AdminReportController extends Controller
{
    public function index()
    {
        $reports = Report::with('generator:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $reports]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'          => ['required', Rule::in(['placement','registration','skills','events','employer','skillmatch','feedback'])],
            'date_from'     => 'nullable|date',
            'date_to'       => 'nullable|date|after_or_equal:date_from',
            'columns'       => 'nullable|array',
            'export_format' => ['nullable', Rule::in(['pdf','xlsx'])],
            'filters'       => 'nullable|array',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo   = $validated['date_to']   ? $validated['date_to'] . ' 23:59:59' : null;
        $filters  = $validated['filters']   ?? [];

        $result = DB::transaction(function () use ($validated, $dateFrom, $dateTo, $filters, $request) {
            $report = Report::create([
                'type'          => $validated['type'],
                'date_from'     => $dateFrom,
                'date_to'       => $dateTo,
                'group_by'      => 'Month',
                'columns'       => $validated['columns']        ?? [],
                'export_format' => $validated['export_format'] ?? 'xlsx',
                'generated_by'  => $request->user()->id,
            ]);

            $data = $this->fetchReportData($validated['type'], $dateFrom, $dateTo, $filters);

            return [$report, $data];
        });

        [$report, $data] = $result;

        return response()->json(['success' => true, 'report' => $report, 'data' => $data], 201);
    }

    public function show($id)
    {
        $report = Report::with('generator:id,first_name,last_name')->findOrFail($id);

        return response()->json(['success' => true, 'data' => $report]);
    }

    public function destroy($id)
    {
        Report::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Report deleted successfully.']);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'type'      => ['required', Rule::in(['placement','registration','skills','events','employer','skillmatch','feedback'])],
            'format'    => ['required', Rule::in(['pdf','xlsx'])],
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
            'columns'   => 'nullable|array',
            'filters'   => 'nullable|array',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo   = $validated['date_to']   ? $validated['date_to'] . ' 23:59:59' : null;
        $filters  = $validated['filters']   ?? [];

        $data    = $this->fetchReportData($validated['type'], $dateFrom, $dateTo, $filters);
        $columns = $validated['columns'] ?? [];

        if (!empty($columns)) {
            $data = $data->map(fn ($row) => collect($row)->only($columns)->all());
        }

        return $validated['format'] === 'pdf'
            ? $this->exportPdf($data, $validated['type'])
            : $this->exportCsv($data, $validated['type']);
    }

    // ── Central dispatcher ────────────────────────────────────────────────────

    private function fetchReportData(string $type, ?string $from, ?string $to, array $filters): \Illuminate\Support\Collection
    {
        return match ($type) {
            'placement'    => $this->getPlacementData($from, $to, $filters),
            'registration' => $this->getRegistrationData($from, $to, $filters),
            'skills'       => $this->getSkillsData($from, $to, $filters),
            'events'       => $this->getEventsData($from, $to, $filters),
            'employer'     => $this->getEmployerData($from, $to, $filters),
            'skillmatch'   => $this->getSkillMatchData($from, $to, $filters),
            'feedback'     => $this->getFeedbackData($from, $to, $filters),
            default        => collect(),
        };
    }

    // ── Data fetchers ─────────────────────────────────────────────────────────

    private function getPlacementData(?string $from, ?string $to, array $filters): \Illuminate\Support\Collection
    {
        $query = Application::with([
            'jobseeker:id,first_name,last_name',
            'jobListing.employer:id,company_name,industry',
        ])->where('status', 'hired');

        if ($from && $to) $query->whereBetween('applied_at', [$from, $to]);
        if (!empty($filters['status']))   $query->where('status', strtolower($filters['status']));
        if (!empty($filters['industry'])) {
            $query->whereHas('jobListing.employer', fn ($q) => $q->where('industry', $filters['industry']));
        }

        return $query->get()->map(fn ($app) => [
            'month'    => $app->applied_at?->format('F'),
            'name'     => $app->jobseeker?->full_name,
            'company'  => $app->jobListing?->employer?->company_name,
            'position' => $app->jobListing?->title,
            'industry' => $app->jobListing?->employer?->industry,
            'status'   => ucfirst($app->status),
            'date'     => $app->applied_at?->format('M d, Y'),
        ]);
    }

    private function getRegistrationData(?string $from, ?string $to, array $filters): \Illuminate\Support\Collection
    {
        $type = $filters['regType'] ?? '';
        $rows = collect();

        if ($type === '' || $type === 'Jobseeker') {
            $q = Jobseeker::withTrashed();
            if ($from && $to)             $q->whereBetween('created_at', [$from, $to]);
            if (!empty($filters['city'])) $q->where('address', 'like', '%' . $filters['city'] . '%');

            $rows = $rows->merge(
                $q->get()->map(fn ($js) => [
                    'month'  => $js->created_at->format('F'),
                    'name'   => $js->full_name,
                    'sex'    => $js->sex ? ucfirst($js->sex) : '—',
                    'type'   => 'Jobseeker',
                    'city'   => $js->address ?? '—',
                    'skills' => $js->skills()->exists()
                        ? $js->skills()->pluck('skill')->implode(', ')
                        : '—',
                    'date'   => $js->created_at->format('M d, Y'),
                ])
            );
        }

        if ($type === '' || $type === 'Employer') {
            $q = Employer::withTrashed();
            if ($from && $to)             $q->whereBetween('created_at', [$from, $to]);
            if (!empty($filters['city'])) $q->where('city', $filters['city']);

            $rows = $rows->merge(
                $q->get()->map(fn ($emp) => [
                    'month'  => $emp->created_at->format('F'),
                    'name'   => $emp->company_name,
                    'sex'    => '—',
                    'type'   => 'Employer',
                    'city'   => $emp->city ?? '—',
                    'skills' => $emp->industry ?? '—',
                    'date'   => $emp->created_at->format('M d, Y'),
                ])
            );
        }

        return $rows->sortBy('date')->values();
    }

    private function getSkillsData(?string $from, ?string $to, array $filters): \Illuminate\Support\Collection
    {
        $query = JobseekerSkill::selectRaw('skill, COUNT(*) as supply_count')
            ->groupBy('skill')
            ->orderByDesc('supply_count')
            ->limit(20);

        if ($from && $to) {
            $query->whereHas('jobseeker', fn ($q) => $q->whereBetween('created_at', [$from, $to]));
        }
        if (!empty($filters['skillCategory'])) {
            $query->where('category', $filters['skillCategory']);
        }

        return $query->get()->map(function ($row) {
            $postings = JobListing::where(function ($q) use ($row) {
                $q->where('title', 'like', '%' . $row->skill . '%')
                  ->orWhere('description', 'like', '%' . $row->skill . '%');
            })->count();
            $totalPostings = JobListing::count() ?: 1;
            $demand        = (int) min(100, round(($postings / $totalPostings) * 100));

            return [
                'skill'    => $row->skill,
                'demand'   => $demand,
                'supply'   => $row->supply_count,
                'gap'      => ($demand - $row->supply_count) . '%',
                'trend'    => '—',
                'postings' => $postings,
            ];
        });
    }

    private function getEventsData(?string $from, ?string $to, array $filters): \Illuminate\Support\Collection
    {
        $query = Event::withCount('registrations');
        if ($from && $to)                    $query->whereBetween('event_date', [$from, $to]);
        if (!empty($filters['eventType']))   $query->where('type',   $filters['eventType']);
        if (!empty($filters['eventStatus'])) $query->where('status', strtolower($filters['eventStatus']));

        return $query->get()->map(fn ($e) => [
            'title'    => $e->title,
            'type'     => $e->type,
            'date'     => $e->event_date?->format('M d, Y'),
            'location' => $e->location,
            'slots'    => $e->max_participants ?? 0,
            'attended' => $e->registrations_count ?? 0,
            'status'   => ucfirst($e->status),
        ]);
    }

    private function getEmployerData(?string $from, ?string $to, array $filters): \Illuminate\Support\Collection
    {
        $query = Employer::withTrashed();
        if ($from && $to)                            $query->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['verificationStatus']))  $query->where('status', strtolower($filters['verificationStatus']));
        if (!empty($filters['industry']))            $query->where('industry', $filters['industry']);

        return $query->get()->map(fn ($emp) => [
            'company'            => $emp->company_name,
            'industry'           => $emp->industry,
            'city'               => $emp->city,
            'verificationStatus' => ucfirst($emp->status),
            'vacancies'          => $emp->jobListings()->where('status', 'open')->count(),
            'date'               => $emp->created_at->format('M d, Y'),
        ]);
    }

    private function getSkillMatchData(?string $from, ?string $to, array $filters): \Illuminate\Support\Collection
    {
        $query = Application::with([
            'jobseeker:id,first_name,last_name,address',
            'jobListing:id,title,category',
        ])->whereNotNull('match_score');

        if ($from && $to)                    $query->whereBetween('applied_at', [$from, $to]);
        if (!empty($filters['minMatch']))    $query->where('match_score', '>=', (int) $filters['minMatch']);
        if (!empty($filters['jobCategory'])) {
            $query->whereHas('jobListing', fn ($q) => $q->where('category', $filters['jobCategory']));
        }

        return $query->get()->map(fn ($app) => [
            'name'       => $app->jobseeker?->full_name,
            'topSkill'   => $app->jobseeker?->skills()->orderBy('id')->first()?->skill ?? '—',
            'matchScore' => $app->match_score,
            'bestFor'    => $app->jobListing?->title ?? '—',
            'city'       => $app->jobseeker?->address ?? '—',
        ]);
    }

    private function getFeedbackData(?string $from, ?string $to, array $filters): \Illuminate\Support\Collection
    {
        $query = Feedback::query();
        if ($from && $to)                    $query->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['rating']))      $query->where('rating', (int) $filters['rating']);
        if (!empty($filters['submittedBy'])) $query->where('submitter_type', $filters['submittedBy']);

        return $query->get()->map(fn ($fb) => [
            'name'     => $fb->submitter_name,
            'type'     => $fb->submitter_type,
            'rating'   => $fb->rating,
            'comment'  => $fb->comment,
            'category' => $fb->category ?? '—',
            'date'     => $fb->created_at->format('M d, Y'),
        ]);
    }

    // ── Export helpers ────────────────────────────────────────────────────────

    /**
     * Export as CSV with Excel-compatible content type.
     * Works without any third-party library.
     */
    private function exportCsv(\Illuminate\Support\Collection $data, string $type)
    {
        $filename = "{$type}_report_" . now()->format('Ymd_His') . '.xlsx';
        $tmpPath  = tempnam(sys_get_temp_dir(), 'report_') . '.xlsx';

        $writer = SimpleExcelWriter::create($tmpPath);

        if ($data->isNotEmpty()) {
            $headers = array_keys($data->first());
            // Write header row manually
            $writer->addRow(array_combine(
                $headers,
                array_map(fn ($h) => ucwords(str_replace('_', ' ', $h)), $headers)
            ));
            foreach ($data as $row) {
                $writer->addRow($row);
            }
        }

        $writer->close();
        $xlsxContent = file_get_contents($tmpPath);
        @unlink($tmpPath);

        return response($xlsxContent, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    /**
     * Export as a styled HTML file served with PDF content type.
     * Opens in the browser's built-in PDF viewer when downloaded.
     */
    private function exportPdf(\Illuminate\Support\Collection $data, string $type)
    {
        $filename  = "{$type}_report_" . now()->format('Ymd_His') . '.pdf';
        $title     = ucfirst($type) . ' Report';
        $generated = now()->format('F d, Y H:i');

        $headers = $data->isNotEmpty() ? array_keys($data->first()) : [];

        $thRows = implode('', array_map(
            fn ($h) => '<th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $h))) . '</th>',
            $headers
        ));

        $tbRows = $data->map(function ($row) {
            $tds = implode('', array_map(
                fn ($v) => '<td>' . htmlspecialchars((string) ($v ?? '—')) . '</td>',
                array_values($row)
            ));
            return "<tr>{$tds}</tr>";
        })->implode('');

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>{$title}</title>
  <style>
    body{font-family:Arial,sans-serif;font-size:11px;color:#1e293b;padding:24px;margin:0}
    h2{font-size:17px;margin:0 0 4px}
    p.meta{font-size:10px;color:#64748b;margin:0 0 20px}
    table{width:100%;border-collapse:collapse}
    th{background:#f1f5f9;padding:8px 10px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e2e8f0}
    td{padding:7px 10px;border-bottom:1px solid #f1f5f9;font-size:11px}
    tr:nth-child(even) td{background:#f8fafc}
  </style>
</head>
<body>
  <h2>{$title}</h2>
  <p class="meta">Generated: {$generated}</p>
  <table>
    <thead><tr>{$thRows}</tr></thead>
    <tbody>{$tbRows}</tbody>
  </table>
</body>
</html>
HTML;

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}