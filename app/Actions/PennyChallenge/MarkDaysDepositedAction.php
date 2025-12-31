<?php

declare(strict_types=1);

namespace App\Actions\PennyChallenge;

use App\Actions\Transaction\CreateTransactionAction;
use App\DataTransferObjects\Actions\CreateTransactionData;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\PennyChallenge;
use App\Models\PennyChallengeDay;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class MarkDaysDepositedAction
{
    public function __construct(
        private CreateTransactionAction $createTransactionAction,
    ) {}

    /**
     * Mark the given days as deposited and create an expense transaction.
     *
     * @param  array<int>  $dayIds  Array of PennyChallengeDay IDs to mark as deposited
     */
    public function handle(PennyChallenge $challenge, array $dayIds): Transaction
    {
        return DB::transaction(function () use ($challenge, $dayIds) {
            $days = $challenge->days()
                ->whereIn('id', $dayIds)
                ->whereNull('deposited_at')
                ->get();

            if ($days->isEmpty()) {
                throw new \InvalidArgumentException('No valid pending days found to mark as deposited.');
            }

            $totalAmount = $this->calculateTotalAmount($days);
            $category = $this->getOrCreateCategory($challenge->user_id);

            $transaction = $this->createTransactionAction->handle(new CreateTransactionData(
                userId: $challenge->user_id,
                name: $this->generateTransactionName($challenge, $days),
                amount: $totalAmount,
                type: TransactionType::Expense,
                paymentDate: Carbon::now(),
                categoryId: $category->id,
                description: $this->generateDescription($days),
            ));

            $this->markDaysAsDeposited($days, $transaction);

            return $transaction;
        });
    }

    /**
     * @param  Collection<int, PennyChallengeDay>  $days
     */
    private function calculateTotalAmount(Collection $days): float
    {
        return $days->sum(fn (PennyChallengeDay $day) => $day->amount());
    }

    private function getOrCreateCategory(int $userId): Category
    {
        return Category::firstOrCreate(
            [
                'user_id' => $userId,
                'name' => 'Penny Challenge',
            ],
            [
                'user_id' => $userId,
                'name' => 'Penny Challenge',
            ]
        );
    }

    /**
     * @param  Collection<int, PennyChallengeDay>  $days
     */
    private function generateTransactionName(PennyChallenge $challenge, Collection $days): string
    {
        $count = $days->count();

        if ($count === 1) {
            return sprintf('%s - Day %d', $challenge->name, $days->first()->day_number);
        }

        return sprintf('%s - %d days', $challenge->name, $count);
    }

    /**
     * @param  Collection<int, PennyChallengeDay>  $days
     */
    private function generateDescription(Collection $days): string
    {
        $dayNumbers = $days->pluck('day_number')->sort()->values();

        if ($dayNumbers->count() <= 5) {
            return 'Days: '.$dayNumbers->implode(', ');
        }

        return sprintf(
            'Days: %s... and %d more',
            $dayNumbers->take(5)->implode(', '),
            $dayNumbers->count() - 5
        );
    }

    /**
     * @param  Collection<int, PennyChallengeDay>  $days
     */
    private function markDaysAsDeposited(Collection $days, Transaction $transaction): void
    {
        $now = now();

        PennyChallengeDay::whereIn('id', $days->pluck('id'))
            ->update([
                'deposited_at' => $now,
                'transaction_id' => $transaction->id,
            ]);
    }
}
