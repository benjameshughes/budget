<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Concerns\PaginatesApiResponse;
use App\DataTransferObjects\CategoryDto;
use App\Http\Controllers\Controller;
use App\Queries\CategoryQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoryController extends Controller
{
    use PaginatesApiResponse;

    public function index(Request $request, CategoryQueries $queries): JsonResponse
    {
        return $this->paginatedResponse(
            $queries->paginatedForUser($request->user()),
            CategoryDto::class,
        );
    }
}
