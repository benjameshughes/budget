<div class="flex h-full w-full flex-col gap-8">
    {{-- 1. Status Message (Hero) --}}
    <div class="flex flex-col items-center justify-center gap-2 pt-8">
        <h1 class="text-4xl font-semibold {{ $this->statusMessage['color'] }} text-center">
            {{ $this->statusMessage['text'] }}
        </h1>
    </div>

    {{-- 2. Quick Input (The Hero Action) --}}
    <div class="mx-auto w-full max-w-2xl">
        <form wire:submit="submitInput">
            <flux:composer
                wire:model="input"
                placeholder="What did you spend? (e.g., £25 at Tesco for groceries)"
                submit="enter"
                rows="1"
                inline
            >
                <x-slot name="actionsTrailing">
                    <flux:button type="submit" size="sm" variant="primary" icon="sparkles">
                        Add
                    </flux:button>
                </x-slot>
            </flux:composer>
        </form>

        <p class="mt-2 text-center text-sm text-neutral-500 dark:text-neutral-400">
            AI-powered parsing - just type naturally!
        </p>
    </div>

    {{-- Traditional Add Transaction Form (Fallback) --}}
    <div class="mx-auto w-full max-w-2xl">
        <livewire:components.add-transaction />
    </div>

    {{-- 3. Recent Transactions --}}
    <div class="mx-auto w-full max-w-2xl">
        <flux:heading size="lg" class="mb-4">Recent Transactions</flux:heading>

        @if($this->recentTransactions->isEmpty())
            <flux:card>
                <div class="py-8 text-center text-neutral-500 dark:text-neutral-400">
                    No transactions yet. Add your first one above!
                </div>
            </flux:card>
        @else
            <div class="space-y-2">
                @foreach($this->recentTransactions as $transaction)
                    <flux:card class="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">
                                        {{ $transaction->name ?? 'Transaction' }}
                                    </span>
                                    @if($transaction->category)
                                        <flux:badge size="sm" color="zinc" inset="top bottom">
                                            {{ $transaction->category->name }}
                                        </flux:badge>
                                    @endif
                                </div>
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $transaction->payment_date->format('M j, Y') }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-semibold {{ $transaction->type->value === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $transaction->type->value === 'income' ? '+' : '-' }}£{{ number_format($transaction->amount, 2) }}
                                </span>
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @endif
    </div>
</div>
