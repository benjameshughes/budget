<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\CategoryDto;
use App\Http\Controllers\Controller;
use App\Queries\CategoryQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoryController extends Controller
{
    public function index(Request $request, CategoryQueries $queries): JsonResponse
    {
        $categories = $queries->allForUser($request->user());

        return response()->json(CategoryDto::collect($categories));
    }
}
