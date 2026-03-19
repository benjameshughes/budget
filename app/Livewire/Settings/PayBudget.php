<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\PayCadence as PayCadenceEnum;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PayBudget extends Component
{
    public string $pay_cadence = '';

    public int $pay_day = 4;

    public string $weekly_budget = '';

    public string $weekly_savings_goal = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->pay_cadence = $user->pay_cadence->value;
        $this->pay_day = $user->pay_day ?? 4;
        $this->weekly_budget = $user->weekly_budget !== null ? (string) $user->weekly_budget : '0.00';
        $this->weekly_savings_goal = (string) ($user->weekly_savings_goal ?? '');
    }

    public function save(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'pay_cadence' => ['required', 'string', 'in:weekly,monthly'],
            'pay_day' => ['required', 'integer', 'min:0', 'max:31'],
            'weekly_budget' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'weekly_savings_goal' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        if ($validated['weekly_savings_goal'] === '' || $validated['weekly_savings_goal'] === null) {
            $validated['weekly_savings_goal'] = null;
        }

        $user->fill($validated);
        $user->save();

        $this->dispatch('pay-budget-updated');
    }

    #[Computed]
    public function isMonthly(): bool
    {
        return $this->pay_cadence === PayCadenceEnum::Monthly->value;
    }

    public function render(): mixed
    {
        return view('livewire.settings.pay-budget', [
            'cadenceOptions' => PayCadenceEnum::cases(),
        ]);
    }
}
