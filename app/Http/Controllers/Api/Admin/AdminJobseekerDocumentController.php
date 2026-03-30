<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jobseeker;
use Illuminate\Support\Facades\Storage;

class AdminJobseekerDocumentController extends Controller
{
    /**
     * Stream a jobseeker document for inline viewing (PDF). Auth: admin.
     *
     * @param  string  $type  resume|certificate|clearance
     */
    public function show(int $jobseeker, string $type)
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

        $js = Jobseeker::findOrFail($jobseeker);
        $path = $js->getAttribute($column);

        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($path);

        return response()->file($fullPath, [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }
}
