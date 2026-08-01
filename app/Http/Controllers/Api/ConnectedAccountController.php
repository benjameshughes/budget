<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\ConnectedAccountDto;
use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use App\Queries\ConnectedAccountQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConnectedAccountController extends Controller
{
    public function index(Request $request, ConnectedAccountQueries $queries): JsonResponse
    {
        $accounts = $queries->allForUser($request->user());

        return response()->json(ConnectedAccountDto::collect($accounts));
    }

    public function show(Request $request, ConnectedAccount $connectedAccount): JsonResponse
    {
        throw_unless($connectedAccount->user_id === $request->user()->id, AuthorizationException::class);

        $connectedAccount->load('bankPots');

        return response()->json(ConnectedAccountDto::fromModel($connectedAccount));
    }
}
