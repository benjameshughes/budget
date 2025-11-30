<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\PayCadence as PayCadenceEnum;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PayCadence extends Component
{
    public string $pay_cadence = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->pay_cadence = Auth::user()->pay_cadence->value;
    }

    /**
     * Update the pay cadence for the currently authenticated user.
     */
    public function updatePayCadence(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'pay_cadence' => ['required', 'string', 'in:weekly,biweekly,twice_monthly,monthly'],
        ]);

        $user->fill($validated);
        $user->save();

        $this->dispatch('pay-cadence-updated');
    }

    public function render()
    {
        return view('livewire.settings.pay-cadence', [
            'cadenceOptions' => PayCadenceEnum::cases(),
        ]);
    }
}
