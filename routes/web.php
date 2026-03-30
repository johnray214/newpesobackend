<?php

/**
 * Public disk files (/storage/...) must be served through Laravel when using
 * `php artisan serve`: the dev server's router returns early for existing files
 * under public/, so /storage/* never hit middleware and get no CORS headers.
 * Flutter web (origin http://localhost:PORT) then blocks Image.network to
 * http://127.0.0.1:8000/storage/... as a cross-origin request.
 *
 * Remove the public/storage symlink so requests fall through to index.php and
 * this route (re-run `php artisan storage:link` only if you need the symlink
 * for another server; for local Flutter web, keep the symlink removed).
 */
use App\Http\Controllers\JobseekerPasswordResetWebController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::match(['GET', 'HEAD', 'OPTIONS'], '/storage/{path}', function (Request $request, string $path) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 204);
    }

    $path = str_replace(['..', "\0"], '', $path);
    $fullPath = storage_path('app/public/'.$path);
    $resolved = realpath($fullPath);
    $base = realpath(storage_path('app/public'));

    if ($resolved === false || $base === false || ! str_starts_with($resolved, $base) || ! is_file($resolved)) {
        abort(404);
    }

    return response()->file($resolved);
})->where('path', '.*')->withoutMiddleware([
    // Stateless public files: CSRF touches session() on the response path → 500 without session.
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
]);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/jobseeker/reset-password', [JobseekerPasswordResetWebController::class, 'show'])
    ->name('jobseeker.password.reset');
Route::post('/jobseeker/reset-password', [JobseekerPasswordResetWebController::class, 'submit'])
    ->name('jobseeker.password.reset.submit');
