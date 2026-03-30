<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployerResource;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminEmployerController extends Controller
{
    public function index(Request $request)
    {
        $query = Employer::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Eager load job listings with counts and hired applicants
        $query->with([
            'jobListings.skills',
            'jobListings' => function ($q) {
                $q->withCount('applications');
            },
            'jobListings.applications' => function ($q) {
                $q->where('status', 'hired')->with(['jobseeker' => function ($q) {
                    $q->withTrashed()->select('id', 'first_name', 'last_name');
                }]);
            }
        ]);

        $employers = $query->orderByDesc('created_at')->paginate(15);

        // Attach derived counts before passing to resource
        $employers->getCollection()->transform(function ($emp) {
            $hiredApplicants = [];
            $totalHired = 0;

            foreach ($emp->jobListings as $listing) {
                foreach ($listing->applications as $app) {
                    if ($app->status === 'hired') {
                        $totalHired++;
                        $name = $app->jobseeker
                            ? trim($app->jobseeker->first_name . ' ' . $app->jobseeker->last_name)
                            : 'Unknown';
                        $hiredApplicants[] = [
                            'name' => $name,
                            'job'  => $listing->title,
                            'date' => $app->updated_at ? $app->updated_at->format('M d, Y') : 'Recently',
                        ];
                    }
                }
            }

            $emp->total_hired      = $totalHired;
            $emp->hired_applicants = $hiredApplicants;
            return $emp;
        });

        return response()->json([
            'success' => true,
            // EmployerResource builds biz_permit_url / bir_cert_url via Storage::url()
            'data' => EmployerResource::collection($employers),
        ]);
    }

    public function show($id)
    {
        $employer = Employer::with('jobListings')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new EmployerResource($employer),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status'  => ['required', Rule::in(['verified', 'rejected', 'suspended'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $employer = Employer::findOrFail($id);

        $wasNotVerified = $employer->status !== 'verified';

        if ($validated['status'] === 'verified' && $wasNotVerified) {
            $validated['verified_at'] = now();
        }

        $employer->update($validated);

        if ($validated['status'] === 'verified' && $wasNotVerified) {
            try {
                $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
                $body = [
                    'Messages' => [
                        [
                            'From' => [
                                'Email' => env('MAILJET_FROM_EMAIL'),
                                'Name' => env('MAILJET_FROM_NAME', 'PESO')
                            ],
                            'To' => [
                                [
                                    'Email' => $employer->email,
                                    'Name' => trim($employer->contact_person ?: $employer->company_name)
                                ]
                            ],
                            'TemplateID' => 7861214,
                            'TemplateLanguage' => true,
                            'Subject' => 'PESO: Your Employer Account Has Been Verified',
                            'Variables' => [
                                'company_name' => $employer->company_name
                            ]
                        ]
                    ]
                ];
                $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
                
                if (!$response->success()) {
                    \Illuminate\Support\Facades\Log::error('Mailjet API Error: ' . json_encode($response->getData()));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Mailjet Exception: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'data'    => new EmployerResource($employer),
            'message' => 'Employer status updated successfully',
        ]);
    }

    public function update(Request $request, $id)
    {
        $employer = Employer::findOrFail($id);

        $validated = $request->validate([
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $employer->update($validated);

        return response()->json([
            'success' => true,
            'data'    => new EmployerResource($employer),
            'message' => 'Employer updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $employer = Employer::findOrFail($id);
        $employer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employer deleted successfully',
        ]);
    }
}