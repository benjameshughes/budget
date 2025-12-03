<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FinancialAdvisorInterface;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\TransactionFeedback;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

final readonly class FinancialAdvisorService implements FinancialAdvisorInterface
{
    public function generateFeedback(User $user, Transaction $transaction): ?TransactionFeedback
    {
        try {
            // Skip if no category - can't provide meaningful context
            if (! $transaction->category_id) {
                return null;
            }

            // Only generate feedback for expenses (not income)
            if ($transaction->type !== TransactionType::Expense) {
                return null;
            }

            // Get context about recent spending in this category
            $context = $this->buildSpendingContext($user, $transaction);

            // Build the AI prompt
            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt($transaction, $context);

            // Call Prism AI
            $response = Prism::text()
                ->using('anthropic', 'claude-3-5-haiku-latest')
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($userPrompt)
                ->withMaxTokens(100)
                ->generate();

            $feedback = trim($response->text);

            if (! $feedback) {
                return null;
            }

            // Create and return the feedback model
            return new TransactionFeedback([
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'feedback' => $feedback,
                'tone' => $this->detectTone($feedback),
            ]);
        } catch (\Exception $e) {
            Log::warning('FinancialAdvisorService failed to generate feedback', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Return null on failure - feedback is nice-to-have, not critical
            return null;
        }
    }

    public function buildSpendingContext(User $user, Transaction $transaction): array
    {
        // Get last 10 transactions in same category
        $recentTransactions = Transaction::query()
            ->where('user_id', $user->id)
            ->where('category_id', $transaction->category_id)
            ->where('type', TransactionType::Expense)
            ->where('id', '!=', $transaction->id)
            ->orderByDesc('payment_date')
            ->limit(10)
            ->get();

        // Calculate 30-day average for this category
        $thirtyDayAverage = Transaction::query()
            ->where('user_id', $user->id)
            ->where('category_id', $transaction->category_id)
            ->where('type', TransactionType::Expense)
            ->where('payment_date', '>=', Carbon::now()->subDays(30))
            ->avg('amount') ?? 0;

        return [
            'recent_transactions' => $recentTransactions,
            'thirty_day_average' => round((float) $thirtyDayAverage, 2),
            'recent_count' => $recentTransactions->count(),
            'category_name' => $transaction->category?->name ?? 'Unknown',
        ];
    }

    public function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a snarky but supportive personal finance advisor. Your job is to provide brief, witty feedback on spending transactions.

Guidelines:
- Keep it SHORT - 1-2 sentences maximum
- Be playful and snarky, but never mean-spirited
- Notice patterns: frequent purchases, high amounts, trending up/down
- Celebrate good behavior (less spending, cooking at home)
- Gently tease bad habits (too much takeout, impulse buys)
- Use humor to make financial awareness fun

Examples:
- "Third coffee today? Your barista must love you. ☕"
- "Look at you, cooking! Your wallet is breathing easier."
- "Another impulse buy? At least it wasn't two... this time."
- "Finally getting that grocery bill down! Progress! 🎉"
- "Treating yourself? You've earned it this week."

Tone: Friendly sarcasm with genuine support underneath.
PROMPT;
    }

    public function buildUserPrompt(Transaction $transaction, array $context): string
    {
        $categoryName = $context['category_name'];
        $amount = number_format((float) $transaction->amount, 2);
        $name = $transaction->name;
        $thirtyDayAvg = number_format((float) $context['thirty_day_average'], 2);
        $recentCount = $context['recent_count'];

        $recentList = '';
        if ($context['recent_transactions']->isNotEmpty()) {
            $recent = $context['recent_transactions']
                ->map(fn ($t) => "£{$t->amount} at {$t->name}")
                ->take(6)
                ->join(', ');
            $recentList = "\nRecent purchases: {$recent}";
        }

        return <<<PROMPT
New expense: £{$amount} at {$name}
Category: {$categoryName}
30-day average in this category: £{$thirtyDayAvg}
Recent transaction count in category: {$recentCount}{$recentList}

Generate brief, snarky but supportive feedback about this purchase.
PROMPT;
    }

    protected function detectTone(string $feedback): ?string
    {
        $feedback = strtolower($feedback);

        // Simple keyword detection for tone
        if (str_contains($feedback, 'nice') || str_contains($feedback, 'great') || str_contains($feedback, 'well done')) {
            return 'encouraging';
        }

        if (str_contains($feedback, 'again') || str_contains($feedback, 'another') || str_contains($feedback, 'third')) {
            return 'playful';
        }

        if (str_contains($feedback, 'careful') || str_contains($feedback, 'watch')) {
            return 'warning';
        }

        return null;
    }
}
