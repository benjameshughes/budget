<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PayCycle extends Component
{
    public int $pay_day = 4;

    public string $weekly_savings_goal = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->pay_day = $user->pay_day ?? 4;
        $this->weekly_savings_goal = (string) ($user->weekly_savings_goal ?? '');
    }

    /**
     * Update the pay cycle settings for the currently authenticated user.
     */
    public function updatePayCycle(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'pay_day' => ['required', 'integer', 'min:0', 'max:6'],
            'weekly_savings_goal' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        // Convert empty string to null for savings goal
        if ($validated['weekly_savings_goal'] === '' || $validated['weekly_savings_goal'] === null) {
            $validated['weekly_savings_goal'] = null;
        }

        $user->fill($validated);
        $user->save();

        $this->dispatch('pay-cycle-updated');
    }

    /**
     * Get the day name for display.
     */
    public function getDayName(int $day): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return $days[$day] ?? 'Unknown';
    }

    public function render()
    {
        return view('livewire.settings.pay-cycle');
    }
}
