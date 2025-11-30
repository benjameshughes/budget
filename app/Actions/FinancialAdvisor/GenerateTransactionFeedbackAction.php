<?php

declare(strict_types=1);

namespace App\Actions\FinancialAdvisor;

use App\Contracts\FinancialAdvisorInterface;
use App\Models\Transaction;
use App\Models\TransactionFeedback;

final readonly class GenerateTransactionFeedbackAction
{
    public function __construct(
        private FinancialAdvisorInterface $advisor,
    ) {}

    public function handle(Transaction $transaction): ?TransactionFeedback
    {
        // Generate feedback using the service
        $feedbackModel = $this->advisor->generateFeedback(
            $transaction->user,
            $transaction
        );

        // If no feedback was generated, return null
        if (! $feedbackModel) {
            return null;
        }

        // Save to database
        $feedbackModel->save();

        return $feedbackModel;
    }
}
