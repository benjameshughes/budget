<?php

namespace App\Services;

use App\Enums\BnplProvider;
use App\Enums\TransactionType;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BnplService
{
    public function createPurchase(
        User $user,
        string $merchant,
        float $total,
        BnplProvider $provider,
        Carbon $purchaseDate,
        float $fee = 0,
        ?string $notes = null
    ): BnplPurchase {
        $purchase = BnplPurchase::create([
            'user_id' => $user->id,
            'merchant' => $merchant,
            'total_amount' => $total,
            'provider' => $provider,
            'fee' => $fee,
            'purchase_date' => $purchaseDate,
            'notes' => $notes,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'name' => "BNPL: {$merchant}",
            'amount' => $total,
            'type' => TransactionType::Expense,
            'payment_date' => $purchaseDate,
        ]);

        // Split purchase total evenly across 4 installments
        $baseAmount = floor(($total / 4) * 100) / 100;
        $lastBaseAmount = $total - ($baseAmount * 3); // Handle rounding on last

        for ($i = 1; $i <= 4; $i++) {
            $weeksToAdd = ($i - 1) * 2;
            $dueDate = $purchaseDate->copy()->addWeeks($weeksToAdd);

            // Fee is added to first installment only (how Zilch works)
            $amount = match ($i) {
                1 => $baseAmount + $fee,
                4 => $lastBaseAmount,
                default => $baseAmount,
            };

            BnplInstallment::create([
                'user_id' => $user->id,
                'bnpl_purchase_id' => $purchase->id,
                'installment_number' => $i,
                'amount' => $amount,
                'due_date' => $dueDate,
                'is_paid' => false,
            ]);
        }

        return $purchase->load('installments');
    }

    public function markInstallmentPaid(BnplInstallment $installment, ?Carbon $paidDate = null): BnplInstallment
    {
        $installment->update([
            'is_paid' => true,
            'paid_date' => $paidDate ?? today(),
        ]);

        return $installment->fresh();
    }

    public function markInstallmentsPaid(array $installmentIds, ?Carbon $paidDate = null): void
    {
        BnplInstallment::whereIn('id', $installmentIds)
            ->update([
                'is_paid' => true,
                'paid_date' => $paidDate ?? today(),
            ]);
    }

    public function getRemainingBalance(BnplPurchase $purchase): float
    {
        return (float) $purchase->installments()
            ->where('is_paid', false)
            ->sum('amount');
    }

    public function getUpcomingInstallments(User $user, int $days = 30): Collection
    {
        return BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->where('due_date', '<=', now()->addDays($days))
            ->with('purchase')
            ->orderBy('due_date')
            ->get();
    }
}
