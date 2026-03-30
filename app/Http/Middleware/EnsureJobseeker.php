<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJobseeker
{
    public function handle(Request $request, Closure $next): Response
    {
        $jobseeker = $request->user('jobseeker');
        
        if (!$jobseeker) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login as jobseeker.'
            ], 401);
        }

        if (!$jobseeker instanceof \App\Models\Jobseeker) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Jobseeker access required.'
            ], 403);
        }

        return $next($request);
    }
}
