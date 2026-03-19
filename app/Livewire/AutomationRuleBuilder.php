<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\PotPurpose;
use App\Enums\RuleActionType;
use App\Enums\TriggerEvent;
use App\Models\AutomationRule;
use App\Models\AutomationRuleLog;
use App\Models\BankPot;
use App\Services\RulesEngineService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AutomationRuleBuilder extends Component
{
    // Rule form fields
    public string $name = '';

    public string $description = '';

    public string $triggerEvent = '';

    /** @var array<int, array<string, mixed>> */
    public array $conditions = [];

    /** @var array<int, array<string, mixed>> */
    public array $actions = [];

    // Editing state
    public ?int $editingRuleId = null;

    public bool $showBuilder = false;

    public function mount(): void
    {
        $this->triggerEvent = TriggerEvent::SalaryDetected->value;
    }

    public function createRule(): void
    {
        $this->resetForm();
        $this->showBuilder = true;
    }

    public function editRule(int $ruleId): void
    {
        $rule = AutomationRule::forUser()->findOrFail($ruleId);

        $this->editingRuleId = $rule->id;
        $this->name = $rule->name;
        $this->description = $rule->description ?? '';
        $this->triggerEvent = $rule->trigger_event->value;
        $this->conditions = $rule->trigger_conditions ?? [];
        $this->actions = $rule->actions ?? [];
        $this->showBuilder = true;
    }

    public function saveRule(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'triggerEvent' => ['required', 'string'],
            'actions' => ['required', 'array', 'min:1'],
        ]);

        $data = [
            'user_id' => Auth::id(),
            'name' => $this->name,
            'description' => $this->description ?: null,
            'trigger_event' => $this->triggerEvent,
            'trigger_conditions' => $this->conditions,
            'actions' => $this->actions,
            'is_active' => true,
        ];

        if ($this->editingRuleId) {
            $rule = AutomationRule::forUser()->findOrFail($this->editingRuleId);
            $rule->update($data);
        } else {
            AutomationRule::create($data);
        }

        $this->showBuilder = false;
        $this->resetForm();

        Flux::toast(
            text: $this->editingRuleId ? 'Rule updated.' : 'Rule created.',
            variant: 'success',
        );
    }

    public function deleteRule(int $ruleId): void
    {
        AutomationRule::forUser()->findOrFail($ruleId)->delete();

        Flux::toast(text: 'Rule deleted.', variant: 'success');
    }

    public function toggleRule(int $ruleId): void
    {
        $rule = AutomationRule::forUser()->findOrFail($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function dryRun(int $ruleId): void
    {
        $rule = AutomationRule::forUser()->findOrFail($ruleId);
        $rulesEngine = app(RulesEngineService::class);
        $context = $rulesEngine->enrichContext(Auth::id(), $this->sampleContext());
        $log = $rulesEngine->execute($rule, $context, dryRun: true);

        Flux::toast(
            text: 'Dry run complete — '.$log->status.'. Check logs for details.',
            variant: $log->isSuccess() ? 'success' : 'warning',
        );
    }

    public function manualTrigger(int $ruleId): void
    {
        $rule = AutomationRule::forUser()->findOrFail($ruleId);
        $rulesEngine = app(RulesEngineService::class);
        $context = $rulesEngine->enrichContext(Auth::id(), $this->sampleContext());
        $log = $rulesEngine->execute($rule, $context);

        Flux::toast(
            text: $log->isSuccess()
                ? 'Rule executed successfully!'
                : 'Rule execution failed: '.($log->error_message ?? 'Unknown error'),
            variant: $log->isSuccess() ? 'success' : 'danger',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleContext(): array
    {
        return [
            'trigger' => [
                'amount' => 150000,
                'amount_signed' => 150000,
                'merchant' => 'Employer Ltd',
                'description' => 'SALARY PAYMENT',
                'is_credit' => true,
                'provider' => 'monzo',
            ],
        ];
    }

    public function addCondition(): void
    {
        $this->conditions[] = [
            'field' => 'trigger.amount',
            'operator' => '>=',
            'value' => '',
        ];
    }

    public function removeCondition(int $index): void
    {
        unset($this->conditions[$index]);
        $this->conditions = array_values($this->conditions);
    }

    public function addAction(): void
    {
        $this->actions[] = [
            'type' => RuleActionType::DepositToPot->value,
            'pot_purpose' => '',
            'amount' => '',
        ];
    }

    public function removeAction(int $index): void
    {
        unset($this->actions[$index]);
        $this->actions = array_values($this->actions);
    }

    public function cancelBuilder(): void
    {
        $this->showBuilder = false;
        $this->resetForm();
    }

    #[Computed]
    public function rules(): \Illuminate\Database\Eloquent\Collection
    {
        return AutomationRule::forUser()
            ->withCount('logs')
            ->latest()
            ->get();
    }

    #[Computed]
    public function recentLogs(): \Illuminate\Database\Eloquent\Collection
    {
        return AutomationRuleLog::forUser()
            ->with('automationRule')
            ->latest('ran_at')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function triggerOptions(): array
    {
        return TriggerEvent::options();
    }

    #[Computed]
    public function actionTypeOptions(): array
    {
        return RuleActionType::options();
    }

    #[Computed]
    public function potPurposeOptions(): array
    {
        return PotPurpose::options();
    }

    #[Computed]
    public function availableVariables(): array
    {
        return [
            'Trigger' => [
                ['key' => '{{trigger.amount}}', 'label' => 'Amount (pence)'],
                ['key' => '{{trigger.amount_pounds}}', 'label' => 'Amount (pounds)'],
                ['key' => '{{trigger.merchant}}', 'label' => 'Merchant name'],
                ['key' => '{{trigger.description}}', 'label' => 'Description'],
                ['key' => '{{trigger.is_credit}}', 'label' => 'Is credit'],
            ],
            'Pay' => [
                ['key' => '{{pay.days_remaining}}', 'label' => 'Days until payday'],
                ['key' => '{{pay.next_pay_date}}', 'label' => 'Next pay date'],
                ['key' => '{{pay.pay_day_name}}', 'label' => 'Pay day name'],
            ],
            'Budget' => [
                ['key' => '{{budget.weekly_budget}}', 'label' => 'Weekly budget (£)'],
                ['key' => '{{budget.weekly_budget_pence}}', 'label' => 'Weekly budget (pence)'],
                ['key' => '{{budget.spent}}', 'label' => 'Spent this period (£)'],
                ['key' => '{{budget.remaining}}', 'label' => 'Remaining (£)'],
                ['key' => '{{budget.remaining_pence}}', 'label' => 'Remaining (pence)'],
                ['key' => '{{budget.percentage_spent}}', 'label' => 'Percentage spent'],
            ],
            'Bills' => [
                ['key' => '{{bills.weekly_contribution}}', 'label' => 'Weekly set-aside (£)'],
                ['key' => '{{bills.weekly_contribution_pence}}', 'label' => 'Weekly set-aside (pence)'],
                ['key' => '{{bills.monthly_total}}', 'label' => 'Monthly bills (£)'],
                ['key' => '{{bills.float_target}}', 'label' => 'Float target (£)'],
                ['key' => '{{bills.float_current}}', 'label' => 'Float current (£)'],
                ['key' => '{{bills.float_progress}}', 'label' => 'Float progress (%)'],
                ['key' => '{{bills.upcoming_7_days}}', 'label' => 'Bills due next 7 days (£)'],
            ],
            'Spending' => [
                ['key' => '{{spending.weekly_expenses}}', 'label' => 'Weekly expenses (£)'],
                ['key' => '{{spending.monthly_income}}', 'label' => 'Monthly income (£)'],
                ['key' => '{{spending.monthly_expenses}}', 'label' => 'Monthly expenses (£)'],
                ['key' => '{{spending.average_daily}}', 'label' => 'Average daily spend (£)'],
            ],
            'BNPL' => [
                ['key' => '{{bnpl.remaining_balance}}', 'label' => 'BNPL remaining (£)'],
                ['key' => '{{bnpl.remaining_balance_pence}}', 'label' => 'BNPL remaining (pence)'],
            ],
            'Savings' => [
                ['key' => '{{savings.weekly_goal}}', 'label' => 'Weekly goal (£)'],
                ['key' => '{{savings.weekly_goal_pence}}', 'label' => 'Weekly goal (pence)'],
            ],
            'Credit' => [
                ['key' => '{{credit.total_balance}}', 'label' => 'Total credit balance (£)'],
                ['key' => '{{credit.total_limit}}', 'label' => 'Total credit limit (£)'],
                ['key' => '{{credit.utilisation}}', 'label' => 'Credit utilisation (%)'],
            ],
        ];
    }

    #[Computed]
    public function todayRunCount(): int
    {
        return AutomationRuleLog::forUser()
            ->whereDate('ran_at', today())
            ->count();
    }

    #[Computed]
    public function todaySuccessCount(): int
    {
        return AutomationRuleLog::forUser()
            ->whereDate('ran_at', today())
            ->where('status', 'success')
            ->count();
    }

    #[Computed]
    public function todayFailCount(): int
    {
        return AutomationRuleLog::forUser()
            ->whereDate('ran_at', today())
            ->where('status', 'failed')
            ->count();
    }

    #[Computed]
    public function activeRuleCount(): int
    {
        return AutomationRule::forUser()
            ->where('is_active', true)
            ->count();
    }

    #[Computed]
    public function availablePots(): \Illuminate\Database\Eloquent\Collection
    {
        return BankPot::forUser()
            ->where('is_active', true)
            ->with('connectedAccount')
            ->orderBy('name')
            ->get();
    }

    private function resetForm(): void
    {
        $this->editingRuleId = null;
        $this->name = '';
        $this->description = '';
        $this->triggerEvent = TriggerEvent::SalaryDetected->value;
        $this->conditions = [];
        $this->actions = [];
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.automation-rule-builder');
    }
}
