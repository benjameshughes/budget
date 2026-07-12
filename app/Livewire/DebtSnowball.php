<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Queries\DebtSnowballQueries;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DebtSnowball extends Component
{
    public string $strategy = 'snowball';

    public string $extraMonthly = '0';

    public function updatedStrategy(): void
    {
        $this->clearCache();
    }

    public function updatedExtraMonthly(): void
    {
        $this->clearCache();
    }

    #[On(['debt-payment-recorded'])]
    public function refresh(): void
    {
        $this->clearCache();
    }

    #[Computed]
    public function debts(): Collection
    {
        return app(DebtSnowballQueries::class)->getAllDebts(auth()->user(), $this->strategy);
    }

    #[Computed]
    public function summary(): array
    {
        return app(DebtSnowballQueries::class)->summary(
            auth()->user(),
            (float) $this->extraMonthly,
            $this->strategy,
        );
    }

    #[Computed]
    public function projection(): Collection
    {
        return app(DebtSnowballQueries::class)->projection(
            auth()->user(),
            (float) $this->extraMonthly,
            $this->strategy,
        );
    }

    public function render(): mixed
    {
        return view('livewire.debt-snowball');
    }

    private function clearCache(): void
    {
        unset($this->debts);
        unset($this->summary);
        unset($this->projection);
    }
}
