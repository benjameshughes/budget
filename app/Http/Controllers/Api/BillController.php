<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\BillDto;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Queries\BillQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BillController extends Controller
{
    public function index(Request $request, BillQueries $queries): JsonResponse
    {
        $bills = $queries->allForUser($request->user());

        return response()->json(BillDto::collect($bills));
    }

    public function show(Request $request, Bill $bill): JsonResponse
    {
        throw_unless($bill->user_id === $request->user()->id, AuthorizationException::class);

        $bill->load('category');

        return response()->json(BillDto::fromModel($bill));
    }
}
