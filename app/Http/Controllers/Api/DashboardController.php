<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Queries\DashboardQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function index(Request $request, DashboardQueries $queries): JsonResponse
    {
        return response()->json($queries->forUser($request->user()));
    }
}
