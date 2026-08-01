<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Concerns\PaginatesApiResponse;
use App\DataTransferObjects\AutomationRuleDto;
use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Queries\AutomationRuleQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AutomationRuleController extends Controller
{
    use PaginatesApiResponse;

    public function index(Request $request, AutomationRuleQueries $queries): JsonResponse
    {
        return $this->paginatedResponse(
            $queries->paginatedForUser($request->user()),
            AutomationRuleDto::class,
        );
    }

    public function show(Request $request, AutomationRule $automationRule): JsonResponse
    {
        throw_unless($automationRule->user_id === $request->user()->id, AuthorizationException::class);

        $automationRule->load('logs');

        return response()->json(AutomationRuleDto::fromModel($automationRule));
    }
}
