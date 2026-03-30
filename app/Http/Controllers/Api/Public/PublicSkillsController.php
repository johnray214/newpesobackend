<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;

class PublicSkillsController extends Controller
{
    public function index(Request $request)
    {
        $query = Skill::query()
            ->where('is_active', true);

        if ($request->filled('q')) {
            $q = (string) $request->query('q');
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            });
        }

        $skills = $query
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return response()->json([
            'success' => true,
            'data' => $skills,
        ]);
    }
}

