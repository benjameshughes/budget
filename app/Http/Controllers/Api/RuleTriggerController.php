<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TriggerEvent;
use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Services\RulesEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RuleTriggerController extends Controller
{
    /**
     * Fire all manual_trigger rules for the authenticated user.
     * Optionally pass context (amount, description, etc.) in the request body.
     *
     * POST /api/rules/trigger
     * POST /api/rules/{rule}/trigger — fire a specific rule
     */
    public function trigger(Request $request, RulesEngineService $rulesEngine, ?int $ruleId = null): JsonResponse
    {
        $userId = $request->user()->id;

        $context = [
            'trigger' => [
                'amount' => (int) $request->input('amount', 0),
                'description' => $request->input('description', 'Manual API trigger'),
                'merchant' => $request->input('merchant', ''),
                'is_credit' => (bool) $request->input('is_credit', false),
                'provider' => $request->input('provider', ''),
            ],
        ];

        if ($ruleId) {
            $rule = AutomationRule::where('user_id', $userId)
                ->where('id', $ruleId)
                ->where('is_active', true)
                ->firstOrFail();

            $context = $rulesEngine->enrichContext($userId, $context);
            $log = $rulesEngine->execute($rule, $context);

            return response()->json([
                'status' => $log->status,
                'actions' => $log->actions_executed,
                'error' => $log->error_message,
            ]);
        }

        $logs = $rulesEngine->evaluateAllForUser($userId, TriggerEvent::ManualTrigger, $context);

        return response()->json([
            'triggered' => count($logs),
            'results' => collect($logs)->map(fn ($log) => [
                'rule' => $log->automationRule->name ?? 'Unknown',
                'status' => $log->status,
                'error' => $log->error_message,
            ])->all(),
        ]);
    }
}
