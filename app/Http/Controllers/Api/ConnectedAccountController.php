<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Concerns\PaginatesApiResponse;
use App\DataTransferObjects\ConnectedAccountDto;
use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use App\Queries\ConnectedAccountQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConnectedAccountController extends Controller
{
    use PaginatesApiResponse;

    public function index(Request $request, ConnectedAccountQueries $queries): JsonResponse
    {
        return $this->paginatedResponse(
            $queries->paginatedForUser($request->user()),
            ConnectedAccountDto::class,
        );
    }

    public function show(Request $request, ConnectedAccount $connectedAccount): JsonResponse
    {
        throw_unless($connectedAccount->user_id === $request->user()->id, AuthorizationException::class);

        $connectedAccount->load('bankPots');

        return response()->json(ConnectedAccountDto::fromModel($connectedAccount));
    }
}
