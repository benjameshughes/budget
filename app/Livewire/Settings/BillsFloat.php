<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Repositories\BillRepository;
use App\Repositories\BnplRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BillsFloat extends Component
{
    public string $bills_float_target = '';

    public string $bills_float_multiplier = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->bills_float_target = $user->bills_float_target !== null ? (string) $user->bills_float_target : '';
        $this->bills_float_multiplier = (string) ($user->bills_float_multiplier ?? '1.0');
    }

    public function save(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'bills_float_target' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'bills_float_multiplier' => ['required', 'numeric', 'min:0', 'max:10'],
        ]);

        if ($validated['bills_float_target'] === '' || $validated['bills_float_target'] === null) {
            $validated['bills_float_target'] = null;
        }

        $user->bills_float_target = $validated['bills_float_target'];
        $user->bills_float_multiplier = $validated['bills_float_multiplier'];
        $user->save();

        $this->dispatch('bills-float-updated');
    }

    #[Computed]
    public function monthlyTotal(): float
    {
        $user = Auth::user();

        return app(BillRepository::class)->monthlyTotal($user)
            + app(BnplRepository::class)->getRemainingBalance($user);
    }

    public function render(): mixed
    {
        return view('livewire.settings.bills-float');
    }
}
