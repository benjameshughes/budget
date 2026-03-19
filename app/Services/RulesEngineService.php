<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BankProvider;
use App\Enums\PotPurpose;
use App\Enums\RuleActionType;
use App\Enums\TriggerEvent;
use App\Models\AutomationRule;
use App\Models\AutomationRuleLog;
use App\Models\BankPot;
use App\Models\CreditCard;
use App\Models\User;
use App\Repositories\BillRepository;
use App\Repositories\BnplRepository;
use App\Repositories\TransactionRepository;
use App\Services\Bank\MonzoService;
use App\Services\Bank\StarlingService;
use Illuminate\Support\Facades\Log;

final readonly class RulesEngineService
{
    public function __construct(
        private MonzoService $monzoService,
        private StarlingService $starlingService,
        private BillsFloatService $billsFloatService,
        private HonestBudgetService $budgetService,
        private PayPeriodService $payPeriodService,
        private BudgetService $fullBudgetService,
        private TransactionRepository $transactionRepository,
        private BillRepository $billRepository,
        private BnplRepository $bnplRepository,
    ) {}

    /**
     * Enrich context with app-level budget/float variables.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function enrichContext(int $userId, array $context): array
    {
        $user = User::findOrFail($userId);

        $floatStatus = $this->billsFloatService->status($user);
        $budgetBreakdown = $this->budgetService->breakdown($user);
        $payPeriod = $this->payPeriodService->currentPeriod($user);

        // Trigger amount in pounds (convenience)
        $triggerAmount = $context['trigger']['amount'] ?? 0;
        $context['trigger']['amount_pounds'] = round($triggerAmount / 100, 2);

        // Pay period
        $context['pay'] = [
            'days_remaining' => $this->payPeriodService->daysRemaining($user),
            'day_of_period' => $this->payPeriodService->currentDayOfPeriod($user),
            'next_pay_date' => $user->nextPayDate()->toDateString(),
            'last_pay_date' => $user->lastPayDate()->toDateString(),
            'pay_day_name' => $this->payPeriodService->payDayName($user),
        ];

        // Budget
        $context['budget'] = [
            'weekly_budget' => $budgetBreakdown['weekly_budget'],
            'weekly_budget_pence' => (int) round($budgetBreakdown['weekly_budget'] * 100),
            'spent' => $budgetBreakdown['spent'],
            'spent_pence' => (int) round($budgetBreakdown['spent'] * 100),
            'remaining' => $budgetBreakdown['remaining'],
            'remaining_pence' => (int) round($budgetBreakdown['remaining'] * 100),
            'percentage_spent' => $budgetBreakdown['percentage_spent'],
            'is_over_budget' => $budgetBreakdown['remaining'] < 0,
            'status' => $budgetBreakdown['status'],
            'daily_remaining' => $this->fullBudgetService->dailyBudgetRemaining($user),
        ];

        // Bills float
        $context['bills'] = [
            'weekly_contribution' => $floatStatus['weekly_contribution'],
            'weekly_contribution_pence' => (int) round($floatStatus['weekly_contribution'] * 100),
            'monthly_total' => $floatStatus['monthly_total'],
            'monthly_total_pence' => (int) round($floatStatus['monthly_total'] * 100),
            'float_target' => $floatStatus['target'],
            'float_current' => $floatStatus['current'],
            'float_progress' => $floatStatus['progress_percentage'] ?? 0,
            'float_is_healthy' => $floatStatus['is_healthy'],
            'upcoming_7_days' => $this->billRepository->totalDueBetween($user, now(), now()->addDays(7)),
        ];

        // Spending insights
        $context['spending'] = [
            'weekly_expenses' => $this->fullBudgetService->weeklyExpenses($user),
            'monthly_income' => $this->fullBudgetService->monthlyIncome($user),
            'monthly_expenses' => $this->fullBudgetService->monthlyExpenses($user),
            'spending_percentage' => $this->fullBudgetService->spendingPercentage($user),
            'average_daily' => $this->transactionRepository->averageDailyExpenseBetween($user, $payPeriod['start'], $payPeriod['end']),
        ];

        // BNPL
        $context['bnpl'] = [
            'remaining_balance' => $this->bnplRepository->getRemainingBalance($user),
            'remaining_balance_pence' => (int) round($this->bnplRepository->getRemainingBalance($user) * 100),
        ];

        // Savings
        $context['savings'] = [
            'weekly_goal' => (float) ($user->weekly_savings_goal ?? 0),
            'weekly_goal_pence' => (int) round((float) ($user->weekly_savings_goal ?? 0) * 100),
        ];

        // Credit cards
        $creditCards = CreditCard::where('user_id', $userId)->get();
        $totalCreditBalance = $creditCards->sum(fn ($card) => $card->currentBalance());
        $totalCreditLimit = $creditCards->whereNotNull('credit_limit')->sum('credit_limit');
        $context['credit'] = [
            'total_balance' => $totalCreditBalance,
            'total_limit' => $totalCreditLimit,
            'utilisation' => $totalCreditLimit > 0 ? round(($totalCreditBalance / $totalCreditLimit) * 100, 1) : 0,
        ];

        return $context;
    }

    /**
     * Evaluate all active rules for a user given a trigger event and context.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, AutomationRuleLog>
     */
    public function evaluateAllForUser(int $userId, TriggerEvent $event, array $context): array
    {
        $context = $this->enrichContext($userId, $context);

        $rules = AutomationRule::where('user_id', $userId)
            ->where('is_active', true)
            ->where('trigger_event', $event->value)
            ->get();

        $logs = [];

        foreach ($rules as $rule) {
            if ($this->evaluate($rule, $context)) {
                $logs[] = $this->execute($rule, $context);
            }
        }

        return $logs;
    }

    /**
     * Evaluate whether a rule's trigger conditions are met.
     *
     * @param  array<string, mixed>  $context
     */
    public function evaluate(AutomationRule $rule, array $context): bool
    {
        $conditions = $rule->trigger_conditions ?? [];

        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute a rule's actions and log the result.
     *
     * @param  array<string, mixed>  $context
     */
    public function execute(AutomationRule $rule, array $context, bool $dryRun = false): AutomationRuleLog
    {
        $actionsExecuted = [];
        $error = null;

        try {
            foreach ($rule->actions as $action) {
                $resolvedAction = $this->resolveAction($action, $context);

                if ($dryRun) {
                    $actionsExecuted[] = array_merge($resolvedAction, ['dry_run' => true]);

                    continue;
                }

                $this->executeAction($resolvedAction, $rule->user_id);
                $actionsExecuted[] = array_merge($resolvedAction, ['executed' => true]);
            }

            if (! $dryRun) {
                $rule->incrementRunCount();
            }

            $status = 'success';
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $status = 'failed';

            Log::error('Automation rule execution failed', [
                'rule_id' => $rule->id,
                'error' => $error,
            ]);
        }

        return AutomationRuleLog::create([
            'user_id' => $rule->user_id,
            'automation_rule_id' => $rule->id,
            'status' => $status,
            'trigger_data' => $context,
            'actions_executed' => $actionsExecuted,
            'error_message' => $error,
            'ran_at' => now(),
        ]);
    }

    /**
     * Interpolate template variables in a string.
     *
     * Supports: {{trigger.amount}}, {{trigger.merchant}}, {{trigger.description}},
     * {{bills_contribution}}, {{remaining}}, and any other context key.
     *
     * @param  array<string, mixed>  $context
     */
    public function interpolate(string $template, array $context): string
    {
        return (string) preg_replace_callback('/\{\{(\w+(?:\.\w+)*)\}\}/', function ($matches) use ($context) {
            return (string) $this->resolveContextValue($matches[1], $context);
        }, $template);
    }

    /**
     * Evaluate a single condition against the context.
     *
     * @param  array<string, mixed>  $condition
     * @param  array<string, mixed>  $context
     */
    private function evaluateCondition(array $condition, array $context): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        $actual = $this->resolveContextValue($field, $context);

        return match ($operator) {
            '==' => $actual == $value,
            '!=' => $actual != $value,
            '>' => (float) $actual > (float) $value,
            '>=' => (float) $actual >= (float) $value,
            '<' => (float) $actual < (float) $value,
            '<=' => (float) $actual <= (float) $value,
            'contains' => is_string($actual) && str_contains(strtolower($actual), strtolower((string) $value)),
            default => false,
        };
    }

    /**
     * Resolve a dotted key path from the context array.
     *
     * @param  array<string, mixed>  $context
     */
    private function resolveContextValue(string $key, array $context): mixed
    {
        $segments = explode('.', $key);
        $current = $context;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Resolve action templates with interpolated context values.
     *
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function resolveAction(array $action, array $context): array
    {
        $resolved = $action;

        // Interpolate string values
        foreach ($resolved as $key => $value) {
            if (is_string($value) && str_contains($value, '{{')) {
                $resolved[$key] = $this->interpolate($value, $context);
            }
        }

        // Resolve amount if it's a template expression
        if (isset($resolved['amount']) && is_string($resolved['amount'])) {
            $resolved['amount_resolved'] = (int) $resolved['amount'];
        }

        return $resolved;
    }

    /**
     * Execute a single resolved action.
     *
     * @param  array<string, mixed>  $action
     */
    private function executeAction(array $action, int $userId): void
    {
        $type = RuleActionType::from($action['type']);

        match ($type) {
            RuleActionType::DepositToPot => $this->executeDepositToPot($action, $userId),
            RuleActionType::WithdrawFromPot => $this->executeWithdrawFromPot($action, $userId),
            RuleActionType::TransferToAccount => $this->executeTransferToAccount($action, $userId),
            RuleActionType::SendNotification => $this->executeSendNotification($action, $userId),
        };
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function executeDepositToPot(array $action, int $userId): void
    {
        $pot = $this->resolvePot($action, $userId);
        $amount = $action['amount_resolved'] ?? (int) ($action['amount'] ?? 0);

        if ($amount <= 0) {
            throw new \RuntimeException('Deposit amount must be positive');
        }

        $account = $pot->connectedAccount;

        match ($account->provider) {
            BankProvider::Monzo => $this->monzoService->depositToPot($account, $pot->external_id, $amount),
            BankProvider::Starling => $this->starlingService->addToSpace($account, $pot->external_id, $amount),
        };

        Log::info('Deposited to pot', ['pot' => $pot->name, 'amount_pence' => $amount]);
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function executeWithdrawFromPot(array $action, int $userId): void
    {
        $pot = $this->resolvePot($action, $userId);
        $amount = $action['amount_resolved'] ?? (int) ($action['amount'] ?? 0);

        if ($amount <= 0) {
            throw new \RuntimeException('Withdraw amount must be positive');
        }

        $account = $pot->connectedAccount;

        match ($account->provider) {
            BankProvider::Monzo => $this->monzoService->withdrawFromPot($account, $pot->external_id, $amount),
            BankProvider::Starling => $this->starlingService->withdrawFromSpace($account, $pot->external_id, $amount),
        };

        Log::info('Withdrawn from pot', ['pot' => $pot->name, 'amount_pence' => $amount]);
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function executeTransferToAccount(array $action, int $userId): void
    {
        // Transfer between accounts is a withdraw + deposit flow
        // For now, log it — actual inter-bank transfers need Open Banking
        Log::info('Transfer to account action triggered', [
            'user_id' => $userId,
            'action' => $action,
        ]);
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function executeSendNotification(array $action, int $userId): void
    {
        $message = $action['message'] ?? 'Automation rule executed';

        Log::info('Automation notification', [
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    /**
     * Resolve a pot from action config — by pot_id or by purpose.
     *
     * @param  array<string, mixed>  $action
     */
    private function resolvePot(array $action, int $userId): BankPot
    {
        if (isset($action['pot_id'])) {
            return BankPot::where('user_id', $userId)
                ->where('id', $action['pot_id'])
                ->where('is_active', true)
                ->firstOrFail();
        }

        if (isset($action['pot_purpose'])) {
            $purpose = PotPurpose::from($action['pot_purpose']);

            return BankPot::where('user_id', $userId)
                ->where('purpose', $purpose->value)
                ->where('is_active', true)
                ->firstOrFail();
        }

        throw new \RuntimeException('Action must specify pot_id or pot_purpose');
    }
}
