<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployer
{
    public function handle(Request $request, Closure $next): Response
    {
        $employer = $request->user('employer');
        
        if (!$employer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login as employer.'
            ], 401);
        }

        if (!$employer instanceof \App\Models\Employer) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Employer access required.'
            ], 403);
        }

        if ($employer->status !== 'verified') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not verified yet.'
            ], 403);
        }

        return $next($request);
    }
}
