<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\FinancialAdvisor\GenerateTransactionFeedbackAction;
use App\Events\Transaction\TransactionCreated;
use Illuminate\Support\Facades\Log;

final readonly class GenerateFinancialFeedbackListener
{
    public function __construct(
        private GenerateTransactionFeedbackAction $action,
    ) {}

    public function handle(TransactionCreated $event): void
    {
        try {
            $this->action->handle($event->transaction);
        } catch (\Exception $e) {
            // Log the error but don't throw - feedback generation should never break transaction creation
            Log::warning('Failed to generate financial feedback', [
                'transaction_id' => $event->transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
