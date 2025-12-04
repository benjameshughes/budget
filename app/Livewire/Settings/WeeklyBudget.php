<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WeeklyBudget extends Component
{
    public string $weekly_budget = '';

    public string $bills_float_target = '';

    public string $bills_float_multiplier = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->weekly_budget = $user->weekly_budget !== null ? (string) $user->weekly_budget : '0.00';
        $this->bills_float_target = $user->bills_float_target !== null ? (string) $user->bills_float_target : '';
        $this->bills_float_multiplier = $user->getAttributes()['bills_float_multiplier'] ?? '1.0';
    }

    /**
     * Update the weekly budget for the currently authenticated user.
     */
    public function updateWeeklyBudget(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'weekly_budget' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'bills_float_target' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'bills_float_multiplier' => ['required', 'numeric', 'min:0.1', 'max:10.0'],
        ]);

        // Convert empty strings to null for nullable decimal fields
        if ($validated['bills_float_target'] === '' || $validated['bills_float_target'] === null) {
            $validated['bills_float_target'] = null;
        }

        $user->weekly_budget = $validated['weekly_budget'];

        // Set nullable/decimal fields directly to avoid cast issues
        $user->setRawAttributes(array_merge(
            $user->getAttributes(),
            [
                'bills_float_target' => $validated['bills_float_target'],
                'bills_float_multiplier' => $validated['bills_float_multiplier'],
            ]
        ));

        $user->save();

        $this->dispatch('weekly-budget-updated');
    }

    public function render()
    {
        return view('livewire.settings.weekly-budget');
    }
}
