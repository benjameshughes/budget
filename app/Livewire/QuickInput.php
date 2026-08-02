<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\QuickInput\ProcessQuickInputAction;
use App\Exceptions\ExpenseParseException;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class QuickInput extends Component
{
    public string $input = '';

    #[On('quick-input-set')]
    public function setInput(string $text): void
    {
        $this->input = $text;
    }

    public function submit(ProcessQuickInputAction $action): void
    {
        if (empty(trim($this->input))) {
            return;
        }

        try {
            $result = $action->handle(auth()->user(), $this->input);

            Flux::toast(text: $result->message, heading: $result->heading, variant: $result->variant);

            $this->reset('input');
            $this->dispatch('close-quick-input');

            if ($result->event !== 'close-quick-input') {
                $this->dispatch($result->event, transactionId: $result->transactionId);
            }
        } catch (ExpenseParseException $e) {
            Flux::toast(text: $e->getMessage(), heading: 'Parsing Error', variant: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.quick-input');
    }
}
