<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ExpenseParserInterface;
use App\Enums\BnplProvider;
use App\Models\Bill;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\SavingsAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

final readonly class ExpenseParserService implements ExpenseParserInterface
{
    public function parse(string $input, int $userId): \App\DataTransferObjects\Actions\ParsedExpenseDto
    {
        try {
            // Get user's categories for context
            $categories = Category::query()
                ->where('user_id', $userId)
                ->get(['id', 'name'])
                ->pluck('name', 'id')
                ->toArray();

            $categoryList = empty($categories)
                ? 'No categories exist yet.'
                : 'Available categories: '.implode(', ', $categories);

            // Get user's credit cards for context
            $creditCards = CreditCard::query()
                ->where('user_id', $userId)
                ->get(['id', 'name'])
                ->pluck('name', 'id')
                ->toArray();

            $creditCardList = empty($creditCards)
                ? 'No credit cards exist yet.'
                : 'Available credit cards: '.implode(', ', $creditCards);

            // Get user's bills for context
            $bills = Bill::query()
                ->where('user_id', $userId)
                ->where('active', true)
                ->get(['id', 'name'])
                ->pluck('name', 'id')
                ->toArray();

            $billList = empty($bills)
                ? 'No bills exist yet.'
                : 'Available bills: '.implode(', ', $bills);

            // Get user's BNPL purchases with unpaid installments
            $bnplPurchases = BnplPurchase::query()
                ->where('user_id', $userId)
                ->whereHas('installments', fn ($q) => $q->where('is_paid', false))
                ->get(['id', 'merchant', 'provider'])
                ->map(fn ($purchase) => [
                    'id' => $purchase->id,
                    'display' => "{$purchase->merchant} ({$purchase->provider->label()})",
                ])
                ->pluck('display', 'id')
                ->toArray();

            $bnplList = empty($bnplPurchases)
                ? 'No BNPL purchases with unpaid installments exist yet.'
                : 'Available BNPL purchases: '.implode(', ', $bnplPurchases);

            // Get user's savings accounts for context
            $savingsAccounts = SavingsAccount::query()
                ->where('user_id', $userId)
                ->get(['id', 'name', 'is_bills_float'])
                ->mapWithKeys(fn ($acc) => [$acc->id => $acc->name.($acc->is_bills_float ? ' (Bills Pot)' : '')])
                ->toArray();

            $savingsList = empty($savingsAccounts)
                ? 'No savings accounts exist yet.'
                : 'Available savings accounts: '.implode(', ', $savingsAccounts);

            // Get BNPL providers
            $bnplProviders = collect(BnplProvider::cases())
                ->map(fn ($p) => $p->label())
                ->implode(', ');

            $todaysDate = Carbon::today()->toDateString();

            $systemPrompt = $this->buildSystemPrompt($categoryList, $creditCardList, $billList, $bnplList, $savingsList, $bnplProviders, $todaysDate);

            $response = Prism::text()
                ->using('anthropic', 'claude-opus-4-5-20251101')
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($input)
                ->withMaxTokens(1024)
                ->generate();

            $content = $response->text;

            if (! $content) {
                throw new \Exception('No response from Prism API');
            }

            // Extract JSON from response (in case Claude adds explanation text)
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');

            if ($jsonStart === false || $jsonEnd === false) {
                throw new \Exception('Invalid JSON response from Claude');
            }

            $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to decode JSON: '.json_last_error_msg());
            }

            // Validate and normalize the response
            return $this->normalizeResponse($parsed, $input, $userId, $categories, $creditCards, $bills, $bnplPurchases, $savingsAccounts);
        } catch (\Exception $e) {
            Log::error('ExpenseParserService failed', [
                'input' => $input,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Unable to parse transaction. Please try again or enter it manually.'
            );
        }
    }

    protected function buildSystemPrompt(string $categoryList, string $creditCardList, string $billList, string $bnplList, string $savingsList, string $bnplProviders, string $todaysDate): string
    {
        return <<<PROMPT
Today's date is: {$todaysDate}

You are a financial assistant parser. Your job is to extract transaction and action information from natural language input and return it as JSON.

Extract:
1. **amount**: The monetary value (positive number, no currency symbols)
2. **name**: Short description/merchant name (e.g., "Starbucks", "Weekly groceries")
3. **type**: Either "income" or "expense"
4. **category**: MUST be the exact category name from the list below, or null if no good match exists
5. **date**: ISO format (YYYY-MM-DD), parse relative dates like "yesterday", "last Friday", default to today
6. **credit_card**: MUST be the exact credit card name from the list below, or null if not paid with a credit card
7. **is_credit_card_payment**: Boolean - true if this is a payment TO a credit card (e.g., "paid off Amex", "paid to Barclaycard"), false otherwise
8. **payment_type**: One of "regular", "credit_card_payment", "bill_payment", "bnpl_payment", "savings_transfer", "bnpl_purchase"
9. **bill**: MUST be the exact bill name from the list below, or null if not a bill payment
10. **bnpl_purchase**: MUST be the exact BNPL purchase name from the list below (for payments), or null
11. **savings_account**: MUST be the exact savings account name from the list below, or null
12. **transfer_direction**: "deposit" or "withdraw" for savings transfers, null otherwise
13. **bnpl_provider**: Provider name for creating new BNPL purchases (Zilch, ClearPay), null otherwise
14. **bnpl_merchant**: Merchant name for creating new BNPL purchases, null otherwise
15. **bnpl_fee**: Fee amount for creating new BNPL purchases (default 0), null otherwise
16. **confidence**: Float 0-1 indicating parsing confidence

Currency symbols to handle: £, $, €, and words like "quid", "pounds", "dollars", "euros"

Date parsing examples:
- "yesterday" = yesterday's date
- "last Friday" = most recent Friday
- "on the 15th" = 15th of current month
- no date mentioned = today

Type detection:
- "spent", "bought", "paid" = expense (unless it's a payment type below)
- "earned", "got paid", "received" = income
- "saved", "transfer to savings" = expense with savings context

Payment type detection:

1. **savings_transfer**:
   - Phrases: "transfer £X to [account]", "put £X in [account]", "move £X to [account]", "deposit £X to [account]"
   - Also: "transfer to bills pot", "add to bills pot", "put in savings"
   - Withdrawal: "withdraw £X from [account]", "take £X from [account]"
   - Set payment_type = "savings_transfer", savings_account = matched account name
   - Set transfer_direction = "deposit" or "withdraw" based on context
   - "bills pot" or "bills float" should match the account marked as "(Bills Pot)"

2. **bnpl_purchase** (creating NEW BNPL):
   - Phrases: "spent £X on Zilch at [merchant]", "bought £X with Zilch from [merchant]", "Zilch purchase £X at [merchant]"
   - Also: "used clearpay for £X at [merchant]", "clearpay purchase"
   - Keywords indicating NEW purchase: "spent on zilch", "bought with zilch", "zilch purchase", "used zilch at"
   - Set payment_type = "bnpl_purchase", bnpl_provider = provider name, bnpl_merchant = merchant
   - If fee mentioned ("fee was £X"): set bnpl_fee = fee amount
   - amount should be the TOTAL purchase amount (not including fee)

3. **credit_card_payment**:
   - Phrases: "paid off [card]", "paid to [card]", "payment to [card]", "paid £X on [card]"
   - Set payment_type = "credit_card_payment", is_credit_card_payment = true, credit_card = matched card name

4. **bill_payment**:
   - Phrases: "paid [bill]", "paid [bill] bill", "paid X for [bill]", "paid X on [bill]"
   - Set payment_type = "bill_payment", bill = matched bill name
   - Use fuzzy matching - "electric" matches "Electricity", "gas" matches "Gas Bill"

5. **bnpl_payment** (paying existing BNPL installment):
   - Phrases: "paid [bnpl] installment", "klarna payment", "clearpay payment", "zilch payment"
   - Set payment_type = "bnpl_payment", bnpl_purchase = matched purchase name
   - Keywords: "installment", "instalment", "payment for [existing purchase]"

6. **regular**: Default for normal transactions (spending on card, debit purchases, income)

Credit card spending (NOT payment):
- "on [card name]", "with [card name]", "using [card name]" = spending on that card (set credit_card, payment_type = "regular")

{$categoryList}

{$creditCardList}

{$billList}

{$bnplList}

{$savingsList}

Available BNPL providers: {$bnplProviders}

IMPORTANT MATCHING RULES:
- Category: Return EXACT name from list or null
- Credit card: Return EXACT name from list or null
- Bill: Return EXACT name from list (fuzzy matching OK) or null
- BNPL purchase: Return EXACT name from list or null
- Savings account: Return EXACT name from list or null (match "bills pot"/"bills float" to account with "(Bills Pot)")
- BNPL provider: Return EXACT provider name or null

Return ONLY valid JSON in this exact format:
{
    "amount": 4.50,
    "name": "Starbucks coffee",
    "type": "expense",
    "category": "Food & Drink",
    "date": "2024-01-15",
    "credit_card": "Amex",
    "is_credit_card_payment": false,
    "payment_type": "regular",
    "bill": null,
    "bnpl_purchase": null,
    "savings_account": null,
    "transfer_direction": null,
    "bnpl_provider": null,
    "bnpl_merchant": null,
    "bnpl_fee": null,
    "confidence": 0.95
}

Example for savings transfer:
{
    "amount": 216.71,
    "name": "Transfer to Bills Pot",
    "type": "expense",
    "category": null,
    "date": "2024-01-15",
    "credit_card": null,
    "is_credit_card_payment": false,
    "payment_type": "savings_transfer",
    "bill": null,
    "bnpl_purchase": null,
    "savings_account": "Bills Pot (Bills Pot)",
    "transfer_direction": "deposit",
    "bnpl_provider": null,
    "bnpl_merchant": null,
    "bnpl_fee": null,
    "confidence": 0.95
}

Example for new BNPL purchase:
{
    "amount": 200.00,
    "name": "Zilch purchase at Amazon",
    "type": "expense",
    "category": "Shopping",
    "date": "2024-01-15",
    "credit_card": null,
    "is_credit_card_payment": false,
    "payment_type": "bnpl_purchase",
    "bill": null,
    "bnpl_purchase": null,
    "savings_account": null,
    "transfer_direction": null,
    "bnpl_provider": "Zilch",
    "bnpl_merchant": "Amazon",
    "bnpl_fee": 2.50,
    "confidence": 0.92
}

Example for bill payment:
{
    "amount": 75.00,
    "name": "Electricity bill payment",
    "type": "expense",
    "category": "Bills",
    "date": "2024-01-15",
    "credit_card": null,
    "is_credit_card_payment": false,
    "payment_type": "bill_payment",
    "bill": "Electricity",
    "bnpl_purchase": null,
    "savings_account": null,
    "transfer_direction": null,
    "bnpl_provider": null,
    "bnpl_merchant": null,
    "bnpl_fee": null,
    "confidence": 0.92
}

Rules:
- payment_type must be one of: "regular", "credit_card_payment", "bill_payment", "bnpl_payment", "savings_transfer", "bnpl_purchase"
- is_credit_card_payment should be true ONLY when payment_type is "credit_card_payment"
- transfer_direction should be set ONLY when payment_type is "savings_transfer"
- bnpl_provider, bnpl_merchant, bnpl_fee should be set ONLY when payment_type is "bnpl_purchase"
- Always include ALL fields in the response
- confidence should reflect how certain you are about the parsing
- Return clean, parseable JSON only
PROMPT;
    }

    protected function normalizeResponse(array $parsed, string $rawInput, int $userId, array $categories, array $creditCards, array $bills, array $bnplPurchases, array $savingsAccounts): \App\DataTransferObjects\Actions\ParsedExpenseDto
    {
        // Find matching category ID if category name was suggested
        $categoryId = null;
        if (! empty($parsed['category'])) {
            $categoryId = array_search($parsed['category'], $categories, true);
            if ($categoryId === false) {
                $categoryId = null;
            }
        }

        // Find matching credit card ID if card name was suggested
        $creditCardId = null;
        if (! empty($parsed['credit_card'])) {
            $creditCardId = array_search($parsed['credit_card'], $creditCards, true);
            if ($creditCardId === false) {
                $creditCardId = null;
            }
        }

        // Determine payment type and match entities
        $paymentType = $parsed['payment_type'] ?? 'regular';
        $validPaymentTypes = ['regular', 'credit_card_payment', 'bill_payment', 'bnpl_payment', 'savings_transfer', 'bnpl_purchase'];
        if (! in_array($paymentType, $validPaymentTypes, true)) {
            $paymentType = 'regular';
        }

        // Handle bill payment matching with fuzzy matching
        $billId = null;
        $billName = null;
        if ($paymentType === 'bill_payment' && ! empty($parsed['bill'])) {
            $suggestedBill = $parsed['bill'];
            $billId = $this->fuzzyMatchEntity($suggestedBill, $bills);
            if ($billId !== null) {
                $billName = $bills[$billId];
            }
        }

        // Handle BNPL payment matching with fuzzy matching
        $bnplInstallmentId = null;
        $bnplPurchaseName = null;
        if ($paymentType === 'bnpl_payment' && ! empty($parsed['bnpl_purchase'])) {
            $suggestedBnpl = $parsed['bnpl_purchase'];
            $bnplPurchaseId = $this->fuzzyMatchEntity($suggestedBnpl, $bnplPurchases);

            if ($bnplPurchaseId !== null) {
                // Find the next unpaid installment for this purchase
                $installment = BnplInstallment::query()
                    ->where('user_id', $userId)
                    ->where('bnpl_purchase_id', $bnplPurchaseId)
                    ->where('is_paid', false)
                    ->orderBy('due_date')
                    ->first();

                if ($installment) {
                    $bnplInstallmentId = $installment->id;
                    $bnplPurchaseName = $bnplPurchases[$bnplPurchaseId];
                }
            }
        }

        // Handle savings account matching
        $savingsAccountId = null;
        $savingsAccountName = null;
        $transferDirection = null;
        if ($paymentType === 'savings_transfer' && ! empty($parsed['savings_account'])) {
            $suggestedAccount = $parsed['savings_account'];
            $savingsAccountId = $this->fuzzyMatchEntity($suggestedAccount, $savingsAccounts);
            if ($savingsAccountId !== null) {
                $savingsAccountName = $savingsAccounts[$savingsAccountId];
            }
            $transferDirection = $parsed['transfer_direction'] ?? 'deposit';
            if (! in_array($transferDirection, ['deposit', 'withdraw'], true)) {
                $transferDirection = 'deposit';
            }
        }

        // Handle BNPL provider matching for new purchases
        $bnplProvider = null;
        $bnplMerchant = null;
        $bnplFee = null;
        if ($paymentType === 'bnpl_purchase') {
            $suggestedProvider = $parsed['bnpl_provider'] ?? null;
            if ($suggestedProvider) {
                // Match provider to enum
                foreach (BnplProvider::cases() as $provider) {
                    if (strcasecmp($provider->label(), $suggestedProvider) === 0 || strcasecmp($provider->value, $suggestedProvider) === 0) {
                        $bnplProvider = $provider->value;
                        break;
                    }
                }
            }
            $bnplMerchant = $parsed['bnpl_merchant'] ?? null;
            $bnplFee = isset($parsed['bnpl_fee']) ? (float) $parsed['bnpl_fee'] : null;
        }

        return new \App\DataTransferObjects\Actions\ParsedExpenseDto(
            amount: (float) ($parsed['amount'] ?? 0),
            name: $parsed['name'] ?? 'Unknown',
            type: in_array($parsed['type'] ?? '', ['income', 'expense'], true)
                ? $parsed['type']
                : 'expense',
            categoryId: $categoryId,
            categoryName: $parsed['category'] ?? null,
            creditCardId: $creditCardId,
            creditCardName: $parsed['credit_card'] ?? null,
            isCreditCardPayment: (bool) ($parsed['is_credit_card_payment'] ?? false),
            date: $this->parseDate($parsed['date'] ?? null),
            confidence: (float) ($parsed['confidence'] ?? 0),
            rawInput: $rawInput,
            paymentType: $paymentType,
            billId: $billId,
            billName: $billName,
            bnplInstallmentId: $bnplInstallmentId,
            bnplPurchaseName: $bnplPurchaseName,
            savingsAccountId: $savingsAccountId,
            savingsAccountName: $savingsAccountName,
            transferDirection: $transferDirection,
            bnplProvider: $bnplProvider,
            bnplMerchant: $bnplMerchant,
            bnplFee: $bnplFee,
        );
    }

    protected function parseDate(?string $date): string
    {
        if (empty($date)) {
            return Carbon::today()->toDateString();
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return Carbon::today()->toDateString();
        }
    }

    /**
     * Fuzzy match an entity name against a list of available entities
     * Returns the ID of the best match, or null if no good match found
     */
    protected function fuzzyMatchEntity(string $searchTerm, array $entities): ?int
    {
        $searchTerm = strtolower(trim($searchTerm));

        // First, try exact match (case-insensitive)
        foreach ($entities as $id => $name) {
            if (strtolower($name) === $searchTerm) {
                return $id;
            }
        }

        // Then, try substring match (entity name contains search term or vice versa)
        foreach ($entities as $id => $name) {
            $nameLower = strtolower($name);
            if (str_contains($nameLower, $searchTerm) || str_contains($searchTerm, $nameLower)) {
                return $id;
            }
        }

        // Finally, try similarity matching with a threshold
        $bestMatch = null;
        $bestSimilarity = 0;
        $threshold = 70; // 70% similarity required

        foreach ($entities as $id => $name) {
            similar_text(strtolower($name), $searchTerm, $similarity);
            if ($similarity > $bestSimilarity && $similarity >= $threshold) {
                $bestSimilarity = $similarity;
                $bestMatch = $id;
            }
        }

        return $bestMatch;
    }
}
