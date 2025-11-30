<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Transaction;
use App\Models\TransactionFeedback;
use App\Models\User;

interface FinancialAdvisorInterface
{
    public function generateFeedback(User $user, Transaction $transaction): ?TransactionFeedback;
}
