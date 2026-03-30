<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employer;
use App\Models\Jobseeker;
use App\Models\JobListing;
use App\Models\Event;
use Illuminate\Http\Request;

class AdminArchiveController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type;

        switch ($type) {
            case 'users':
                $results = User::onlyTrashed()->orderByDesc('deleted_at')->paginate(15);
                break;
            case 'employers':
                $results = Employer::onlyTrashed()->orderByDesc('deleted_at')->paginate(15);
                break;
            case 'jobseekers':
                $results = Jobseeker::onlyTrashed()->orderByDesc('deleted_at')->paginate(15);
                break;
            case 'job_listings':
                $results = JobListing::onlyTrashed()->orderByDesc('deleted_at')->paginate(15);
                break;
            case 'events':
                $results = Event::onlyTrashed()->orderByDesc('deleted_at')->paginate(15);
                break;

            case 'all':
                // Return all soft-deleted records across all types in one request
                $users       = User::onlyTrashed()->get()->map(fn($m) => $this->formatItem($m, 'users'));
                $employers   = Employer::onlyTrashed()->get()->map(fn($m) => $this->formatItem($m, 'employers'));
                $jobseekers  = Jobseeker::onlyTrashed()->get()->map(fn($m) => $this->formatItem($m, 'jobseekers'));
                $jobListings = JobListing::onlyTrashed()->get()->map(fn($m) => $this->formatItem($m, 'job_listings'));
                $events      = Event::onlyTrashed()->get()->map(fn($m) => $this->formatItem($m, 'events'));

                $all = $users
                    ->concat($employers)
                    ->concat($jobseekers)
                    ->concat($jobListings)
                    ->concat($events)
                    ->sortByDesc('deleted_at')
                    ->values();

                return response()->json([
                    'success' => true,
                    'data'    => $all,
                    'counts'  => [
                        'users'        => $users->count(),
                        'employers'    => $employers->count(),
                        'jobseekers'   => $jobseekers->count(),
                        'job_listings' => $jobListings->count(),
                        'events'       => $events->count(),
                    ],
                ]);

            default:
                // No type = counts only
                return response()->json([
                    'success' => true,
                    'data'    => [
                        'users'        => User::onlyTrashed()->count(),
                        'employers'    => Employer::onlyTrashed()->count(),
                        'jobseekers'   => Jobseeker::onlyTrashed()->count(),
                        'job_listings' => JobListing::onlyTrashed()->count(),
                        'events'       => Event::onlyTrashed()->count(),
                    ],
                ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    /**
     * Normalize any model into a consistent shape for the frontend.
     */
    private function formatItem($model, string $type): array
    {
        $name   = 'Unknown';
        $detail = '';

        switch ($type) {
            case 'users':
                $name   = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? '')) ?: ($model->name ?? 'User');
                $detail = $model->email ?? '';
                break;
            case 'employers':
                $name   = $model->company_name ?? 'Employer';
                $detail = $model->email ?? '';
                break;
            case 'jobseekers':
                $name   = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? '')) ?: 'Jobseeker';
                $detail = $model->email ?? '';
                break;
            case 'job_listings':
                $name   = $model->title ?? 'Job Listing';
                $detail = 'ID: ' . $model->id;
                break;
            case 'events':
                $name   = $model->title ?? 'Event';
                $detail = $model->location ?? '';
                break;
        }

        return [
            'id'         => $model->id,
            'type'       => $type,
            'name'       => $name,
            'detail'     => $detail,
            'deleted_at' => $model->deleted_at,
        ];
    }

    public function restore(Request $request, $type, $id)
    {
        $model = $this->getModel($type);

        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 400);
        }

        $record = $model::onlyTrashed()->findOrFail($id);
        $record->restore();

        return response()->json(['success' => true, 'message' => 'Record restored successfully']);
    }

    public function destroy(Request $request, $type, $id)
    {
        $model = $this->getModel($type);

        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 400);
        }

        $record = $model::onlyTrashed()->findOrFail($id);
        $record->forceDelete();

        return response()->json(['success' => true, 'message' => 'Record permanently deleted']);
    }

    private function getModel($type)
    {
        return match ($type) {
            'users'        => User::class,
            'employers'    => Employer::class,
            'jobseekers'   => Jobseeker::class,
            'job_listings' => JobListing::class,
            'events'       => Event::class,
            default        => null,
        };
    }
}