<?php

declare(strict_types=1);

namespace App\Actions\Bnpl;

use App\Enums\BnplProvider;
use App\Enums\TransactionType;
use App\Events\Bnpl\BnplPurchaseCreated;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class CreatePurchaseAction
{
    public function handle(
        User $user,
        string $merchant,
        float $total,
        BnplProvider $provider,
        Carbon $purchaseDate,
        float $fee = 0,
        ?string $notes = null
    ): BnplPurchase {
        return DB::transaction(function () use ($user, $merchant, $total, $provider, $purchaseDate, $fee, $notes) {
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
                'is_bill' => true, // Exclude from weekly spending - covered by bills pot
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

            event(new BnplPurchaseCreated($purchase));

            return $purchase->load('installments');
        });
    }
}
