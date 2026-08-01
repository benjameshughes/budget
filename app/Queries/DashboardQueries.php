<?php

declare(strict_types=1);

namespace App\Queries;

use App\DataTransferObjects\BillDto;
use App\DataTransferObjects\BnplPurchaseDto;
use App\DataTransferObjects\ConnectedAccountDto;
use App\DataTransferObjects\CreditCardDto;
use App\DataTransferObjects\DebtDto;
use App\DataTransferObjects\PennyChallengeDto;
use App\DataTransferObjects\SavingsAccountDto;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;

final readonly class DashboardQueries
{
    public function __construct(
        private BillQueries $billQueries,
        private CreditCardQueries $creditCardQueries,
        private DebtQueries $debtQueries,
        private SavingsQueries $savingsQueries,
        private ConnectedAccountQueries $connectedAccountQueries,
        private BnplQueries $bnplQueries,
        private PennyChallengeQueries $pennyChallengeQueries,
    ) {}

    /**
     * @return array{summary: array, pay: array, connected_accounts: array, savings_accounts: array, credit_cards: array, debts: array, bnpl_purchases: array, active_bills: array, penny_challenge: PennyChallengeDto|null}
     */
    public function forUser(User $user): array
    {
        $connectedAccounts = $this->connectedAccountQueries->allForUser($user);
        $totalBankBalance = $connectedAccounts->sum('balance_pence') / 100;

        $savingsAccounts = $this->savingsQueries->allForUser($user);
        $totalSavings = $savingsAccounts->sum(fn ($account) => $account->currentBalance());

        $creditCards = $this->creditCardQueries->allForUser($user);
        $totalCreditCardDebt = $creditCards->sum(fn ($card) => $card->currentBalance());

        $debts = $this->debtQueries->allForUser($user);
        $totalDebtBalance = $debts->sum(fn ($debt) => $debt->currentBalance());

        $bnplPurchases = $this->bnplQueries->allForUser($user);
        $totalBnplRemaining = $bnplPurchases->sum(fn ($purchase) => $purchase->remainingBalance());

        $bills = $this->billQueries->activeForUser($user);
        $monthlyBills = $bills->sum(fn ($bill) => $bill->monthlyEquivalent());

        $pennyChallenge = $this->pennyChallengeQueries->latestForUser($user);

        $monthTransactions = Transaction::forUser($user)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->get();

        $monthIncome = (float) $monthTransactions->where('type', TransactionType::Income)->sum('amount');
        $monthExpenses = (float) $monthTransactions->where('type', TransactionType::Expense)->sum('amount');

        return [
            'summary' => [
                'bank_balance' => $totalBankBalance,
                'total_savings' => $totalSavings,
                'total_credit_card_debt' => $totalCreditCardDebt,
                'total_debt' => $totalDebtBalance,
                'total_bnpl_remaining' => $totalBnplRemaining,
                'total_liabilities' => $totalCreditCardDebt + $totalDebtBalance + $totalBnplRemaining,
                'monthly_bills' => $monthlyBills,
                'month_income' => $monthIncome,
                'month_expenses' => $monthExpenses,
                'month_net' => $monthIncome - $monthExpenses,
            ],
            'pay' => [
                'cadence' => $user->pay_cadence?->value,
                'last_pay_date' => $user->lastPayDate()?->toDateString(),
                'next_pay_date' => $user->nextPayDate()?->toDateString(),
            ],
            'connected_accounts' => ConnectedAccountDto::collect($connectedAccounts),
            'savings_accounts' => SavingsAccountDto::collect($savingsAccounts),
            'credit_cards' => CreditCardDto::collect($creditCards),
            'debts' => DebtDto::collect($debts),
            'bnpl_purchases' => BnplPurchaseDto::collect($bnplPurchases),
            'active_bills' => BillDto::collect($bills),
            'penny_challenge' => $pennyChallenge ? PennyChallengeDto::fromModel($pennyChallenge) : null,
        ];
    }
}
