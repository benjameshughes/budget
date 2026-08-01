<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\BnplPurchaseDto;
use App\Http\Controllers\Controller;
use App\Models\BnplPurchase;
use App\Queries\BnplQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BnplPurchaseController extends Controller
{
    public function index(Request $request, BnplQueries $queries): JsonResponse
    {
        $purchases = $queries->allForUser($request->user());

        return response()->json(BnplPurchaseDto::collect($purchases));
    }

    public function show(Request $request, BnplPurchase $bnplPurchase): JsonResponse
    {
        throw_unless($bnplPurchase->user_id === $request->user()->id, AuthorizationException::class);

        $bnplPurchase->load('installments');

        return response()->json(BnplPurchaseDto::fromModel($bnplPurchase));
    }
}
