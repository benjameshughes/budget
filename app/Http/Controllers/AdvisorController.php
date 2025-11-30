<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Services\FinancialAdvisorService;
use Prism\Prism\Facades\Prism;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdvisorController extends Controller
{
    public function __construct(
        private readonly FinancialAdvisorService $advisorService,
    ) {}

    public function stream(Transaction $transaction): StreamedResponse|\Illuminate\Http\Response
    {
        // Authorization: must be the transaction owner
        abort_unless($transaction->user_id === auth()->id(), 403);

        // Skip if no category - can't provide meaningful context
        if (! $transaction->category_id) {
            return response('No category assigned to this transaction.', 204);
        }

        // Only generate feedback for expenses (not income)
        if ($transaction->type !== TransactionType::Expense) {
            return response('Feedback only available for expenses.', 204);
        }

        // Build context using the service
        $context = $this->advisorService->buildSpendingContext(auth()->user(), $transaction);

        // Build prompts using the service
        $systemPrompt = $this->advisorService->buildSystemPrompt();
        $userPrompt = $this->advisorService->buildUserPrompt($transaction, $context);

        // Stream the response
        return Prism::text()
            ->using('anthropic', 'claude-opus-4-5-20251101')
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($userPrompt)
            ->withMaxTokens(150)
            ->asEventStreamResponse();
    }
}
