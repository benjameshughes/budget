<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait PaginatesApiResponse
{
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $dtoClass): JsonResponse
    {
        return response()->json([
            'data' => $dtoClass::collect(collect($paginator->items())),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
