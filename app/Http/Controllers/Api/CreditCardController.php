<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\CreditCardDto;
use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Queries\CreditCardQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreditCardController extends Controller
{
    public function index(Request $request, CreditCardQueries $queries): JsonResponse
    {
        $cards = $queries->allForUser($request->user());

        return response()->json(CreditCardDto::collect($cards));
    }

    public function show(Request $request, CreditCard $creditCard, CreditCardQueries $queries): JsonResponse
    {
        throw_unless($creditCard->user_id === $request->user()->id, AuthorizationException::class);

        $queries->withSpending($creditCard);

        return response()->json(CreditCardDto::fromModel($creditCard));
    }
}
