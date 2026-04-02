<?php

use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Http\Controllers\Api\Auth\AdminAuthController;
use App\Http\Controllers\Api\Auth\EmployerAuthController;
use App\Http\Controllers\Api\Auth\JobseekerAuthController;

Route::get('/debug-notifs', function() {
    return \App\Models\NotificationRead::with('notification')->where('recipient_type', 'employer')->orderByDesc('created_at')->limit(10)->get();
});

// Admin Controllers
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminEmployerController;
use App\Http\Controllers\Api\Admin\AdminJobseekerController;
use App\Http\Controllers\Api\Admin\AdminJobListingController;
use App\Http\Controllers\Api\Admin\AdminApplicationController;
use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\AdminArchiveController;
use App\Http\Controllers\Api\Admin\AdminActivityFeedController;
use App\Http\Controllers\Api\Admin\AdminJobseekerDocumentController;
use App\Http\Controllers\Api\Public\PublicEventController;
use App\Http\Controllers\Api\Public\PublicMapController;
use App\Http\Controllers\Api\Public\PublicSkillsController;

// Employer Controllers
use App\Http\Controllers\Api\Employer\EmployerDashboardController;
use App\Http\Controllers\Api\Employer\EmployerJobListingController;
use App\Http\Controllers\Api\Employer\EmployerApplicationController;
use App\Http\Controllers\Api\Employer\EmployerProfileController;
use App\Http\Controllers\Api\Employer\EmployerNotificationController;
use App\Http\Controllers\Api\Employer\EmployerInvitationController;

// Jobseeker Controllers
use App\Http\Controllers\Api\Jobseeker\JobseekerProfileController;
use App\Http\Controllers\Api\Jobseeker\JobseekerJobListingController;
use App\Http\Controllers\Api\Jobseeker\JobseekerApplicationController;
use App\Http\Controllers\Api\Jobseeker\JobseekerNotificationController;
use App\Http\Controllers\Api\Jobseeker\JobseekerSavedJobController;
use App\Http\Controllers\Api\Jobseeker\JobseekerSkillsController;
use App\Http\Controllers\Api\Jobseeker\JobseekerEventRegistrationController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// Admin Auth
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/forgot-password', [AdminAuthController::class, 'forgotPassword']);
Route::post('/admin/reset-password', [AdminAuthController::class, 'resetPassword']);
Route::post('/peso-employee/login', [AdminAuthController::class, 'login']); // Alias for frontend

// Employer Auth
Route::post('/employer/login', [EmployerAuthController::class, 'login']);
Route::post('/employer/register', [EmployerAuthController::class, 'register']);
Route::post('/employer/forgot-password', [EmployerAuthController::class, 'forgotPassword']);
Route::post('/employer/reset-password', [EmployerAuthController::class, 'resetPassword']);

// Jobseeker Auth
Route::post('/jobseeker/login', [JobseekerAuthController::class, 'login']);
Route::get('/jobseeker/check-email', [JobseekerAuthController::class, 'checkEmail']);
Route::post('/jobseeker/register', [JobseekerAuthController::class, 'register']);
Route::post('/jobseeker/verify-otp', [JobseekerAuthController::class, 'verifyOtp']);
Route::post('/jobseeker/resend-otp', [JobseekerAuthController::class, 'resendOtp']);
Route::post('/jobseeker/forgot-password', [JobseekerAuthController::class, 'forgotPassword']);
Route::post('/jobseeker/reset-password', [JobseekerAuthController::class, 'resetPassword']);

// Public Job Listings
Route::get('/public/jobs', [JobseekerJobListingController::class, 'index']);
Route::get('/public/jobs/{id}', [JobseekerJobListingController::class, 'show']);

// Backwards-compatible aliases for mobile app expecting /api/jobs
Route::get('/jobs', [JobseekerJobListingController::class, 'index']);
Route::get('/jobs/{id}', [JobseekerJobListingController::class, 'show']);

// Public Employers
Route::get('/public/employers', [AdminEmployerController::class, 'index']);
Route::get('/public/employers/{id}', [AdminEmployerController::class, 'show']);

// Public Events (used by mobile app Jobs page)
Route::get('/public/events', [PublicEventController::class, 'index']);
Route::get('/public/events/{id}', [PublicEventController::class, 'show']);

// Public Map endpoints (used by mobile app Map tab)
Route::get('/public/map/employers', [PublicMapController::class, 'employers']);

// Public Skills Catalog (used by jobseekers to pick skills)
Route::get('/public/skills', [PublicSkillsController::class, 'index']);

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', \App\Http\Middleware\EnsureAdmin::class])->prefix('admin')->group(function () {
    
    // Auth
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/me', [AdminAuthController::class, 'me']);

    // Profile
    Route::get('/profile', [AdminAuthController::class, 'profile']);
    Route::put('/profile', [AdminAuthController::class, 'updateProfile']);
    Route::post('/profile/password', [AdminAuthController::class, 'changePassword']);
    Route::post('/profile/photo', [AdminAuthController::class, 'uploadPhoto']);
    
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    
    // Users
    Route::apiResource('users', AdminUserController::class);
    
    // Employers
    Route::get('/employers', [AdminEmployerController::class, 'index']);
    Route::get('/employers/{id}', [AdminEmployerController::class, 'show']);
    Route::put('/employers/{id}', [AdminEmployerController::class, 'update']);
    Route::patch('/employers/{id}/status', [AdminEmployerController::class, 'updateStatus']);
    Route::delete('/employers/{id}', [AdminEmployerController::class, 'destroy']);
    
    // Jobseekers
    Route::get('/jobseekers', [AdminJobseekerController::class, 'index']);
    Route::get('/jobseekers/{jobseeker}/documents/{type}', [AdminJobseekerDocumentController::class, 'show'])
        ->where('type', 'resume|certificate|clearance');
    Route::get('/jobseekers/{id}', [AdminJobseekerController::class, 'show']);
    Route::patch('/jobseekers/{id}/status', [AdminJobseekerController::class, 'updateStatus']);
    Route::delete('/jobseekers/{id}', [AdminJobseekerController::class, 'destroy']);
    
    // Job Listings
    Route::get('/job-listings', [AdminJobListingController::class, 'index']);
    Route::get('/job-listings/{id}', [AdminJobListingController::class, 'show']);
    Route::patch('/job-listings/{id}/status', [AdminJobListingController::class, 'updateStatus']);
    Route::delete('/job-listings/{id}', [AdminJobListingController::class, 'destroy']);
    
    // Applications
    Route::get('/applications/reviewing-count', [AdminApplicationController::class, 'reviewingCount']);
    Route::get('/applications', [AdminApplicationController::class, 'index']);
    Route::get('/applications/{id}', [AdminApplicationController::class, 'show']);
    Route::patch('/applications/{id}/status', [AdminApplicationController::class, 'updateStatus']);
    Route::delete('/applications/{id}', [AdminApplicationController::class, 'destroy']);
    
    // Events (specific routes before apiResource)
    Route::get('/events/{id}/registrations', [AdminEventController::class, 'registrations']);
    Route::apiResource('events', AdminEventController::class);
    
    // Notifications
    Route::apiResource('notifications', AdminNotificationController::class);
    Route::post('/notifications/{id}/send', [AdminNotificationController::class, 'send']);
    
    // Reports
    Route::get('/reports',              [AdminReportController::class, 'index']);
    Route::post('/reports',             [AdminReportController::class, 'store']);
    Route::post('/reports/export',      [AdminReportController::class, 'export']); // static segment first
    Route::get('/reports/{id}',         [AdminReportController::class, 'show']);
    Route::delete('/reports/{id}',      [AdminReportController::class, 'destroy']);
    
    // Archive
    Route::get('/archive', [AdminArchiveController::class, 'index']);
    Route::post('/archive/{type}/{id}/restore', [AdminArchiveController::class, 'restore']);
    Route::delete('/archive/{type}/{id}', [AdminArchiveController::class, 'destroy']);

    // Activity Feed (topbar notifications)
    Route::get('/activity-feed', [AdminActivityFeedController::class, 'index']);
    Route::patch('/activity-feed/{id}/read', [AdminActivityFeedController::class, 'markRead']);
    Route::post('/activity-feed/read-all', [AdminActivityFeedController::class, 'markAllRead']);

    Route::get('/download', function (\Illuminate\Http\Request $request) {
        $path = $request->query('path');
        $fullPath = storage_path('app/public/' . $path);

        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->download($fullPath);
    })->middleware('auth:sanctum');
});

/*
|--------------------------------------------------------------------------
| EMPLOYER ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:employer', \App\Http\Middleware\EnsureEmployer::class])->prefix('employer')->group(function () {
    
    // Auth
    Route::post('/logout', [EmployerAuthController::class, 'logout']);
    Route::get('/me', [EmployerAuthController::class, 'me']);
    
    // Dashboard
    Route::get('/dashboard', [EmployerDashboardController::class, 'index']);
    
    // Job Listings
    // Jobs
    Route::apiResource('jobs', EmployerJobListingController::class);
    Route::patch('/jobs/{id}/close', [EmployerJobListingController::class, 'close']);
    
    // Applications
    Route::get('/applications', [EmployerApplicationController::class, 'index']);
    Route::get('/applications/{id}/resume', [EmployerApplicationController::class, 'downloadResume']);
    Route::get('/applications/{id}', [EmployerApplicationController::class, 'show']);
    Route::patch('/applications/{id}/status', [EmployerApplicationController::class, 'updateStatus']);
    Route::get('/potential-applicants', [EmployerApplicationController::class, 'potentialApplicants']);
    Route::post('/potential-applicants/{id}/invite', [EmployerApplicationController::class, 'sendInvite']);
    
    // Profile
    Route::get('/profile', [EmployerProfileController::class, 'show']);
    Route::put('/profile', [EmployerProfileController::class, 'update']);
    Route::post('/profile/password', [EmployerProfileController::class, 'changePassword']);
    Route::post('/profile/photo', [EmployerProfileController::class, 'uploadPhoto']);
    Route::post('/profile/documents', [EmployerProfileController::class, 'uploadDocuments']);
    
    // Notifications (specific routes MUST come before {id} wildcard)
    Route::get('/notifications', [EmployerNotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [EmployerNotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [EmployerNotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/mark-read', [EmployerNotificationController::class, 'markRead']);
    Route::get('/notifications/{id}', [EmployerNotificationController::class, 'show']);

    // Invitations (send to jobseekers)
    Route::post('/invite/{jobseeker_id}', [EmployerInvitationController::class, 'sendInvitation']);
});

/*
|--------------------------------------------------------------------------
| JOBSEEKER ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:jobseeker', \App\Http\Middleware\EnsureJobseeker::class])->prefix('jobseeker')->group(function () {
    
    // Auth
    Route::post('/logout', [JobseekerAuthController::class, 'logout']);
    Route::get('/me', [JobseekerAuthController::class, 'me']);

    // Events (registrations — must be before /jobs/{id} style conflicts)
    Route::get('/events/registered-ids', [JobseekerEventRegistrationController::class, 'registeredEventIds']);
    Route::post('/events/{id}/register', [JobseekerEventRegistrationController::class, 'register']);
    Route::delete('/events/{id}/register', [JobseekerEventRegistrationController::class, 'unregister']);
    
    // Job Listings
    Route::get('/jobs', [JobseekerJobListingController::class, 'index']);
    Route::get('/jobs/{id}', [JobseekerJobListingController::class, 'show']);
    
    // Applications
    Route::get('/applications', [JobseekerApplicationController::class, 'index']);
    Route::get('/applications/{id}', [JobseekerApplicationController::class, 'show']);
    Route::post('/applications', [JobseekerApplicationController::class, 'store']);
    Route::delete('/applications/{id}', [JobseekerApplicationController::class, 'withdraw']);

    // Saved Jobs
    Route::get('/saved-jobs', [JobseekerSavedJobController::class, 'index']);
    Route::post('/saved-jobs', [JobseekerSavedJobController::class, 'store']);
    Route::delete('/saved-jobs/{job_listing_id}', [JobseekerSavedJobController::class, 'destroyByJobListing']);
    
    // Profile
    Route::get('/profile', [JobseekerProfileController::class, 'show']);
    Route::put('/profile', [JobseekerProfileController::class, 'update']);
    Route::post('/profile/password', [JobseekerProfileController::class, 'changePassword']);
    Route::post('/profile/resume', [JobseekerProfileController::class, 'uploadResume']);
    Route::post('/profile/certificate', [JobseekerProfileController::class, 'uploadCertificate']);
    Route::post('/profile/barangay-clearance', [JobseekerProfileController::class, 'uploadBarangayClearance']);
    Route::get('/profile/documents/{type}', [JobseekerProfileController::class, 'downloadDocument'])
        ->where('type', 'resume|certificate|clearance');
    Route::post('/profile/avatar', [JobseekerProfileController::class, 'uploadAvatar']);
    Route::get('/profile/avatar', [JobseekerProfileController::class, 'avatar']);
    
    // Satisfaction Rating
    Route::post('/satisfaction-rating', [JobseekerProfileController::class, 'submitSatisfactionRating']);
    Route::post('/save-fcm-token', [JobseekerProfileController::class, 'saveFcmToken']);

    // Skills catalog for jobseekers (stores skill_id selections)
    Route::get('/skills', [JobseekerSkillsController::class, 'index']);
    Route::post('/skills', [JobseekerSkillsController::class, 'store']);
    
    // Notifications (specific routes MUST come before {id} wildcard)
    Route::get('/notifications', [JobseekerNotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [JobseekerNotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [JobseekerNotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/{id}', [JobseekerNotificationController::class, 'show']);
    Route::delete('/notifications/{id}', [JobseekerNotificationController::class, 'destroy']);
    Route::delete('/notifications', [JobseekerNotificationController::class, 'destroyAllRead']);
});
