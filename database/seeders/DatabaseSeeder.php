<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardPayment;
use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create or retrieve the test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Seed categories for the user
        CategorySeeder::seedForUser($user->id);

        // Get some categories for realistic assignments
        $categories = Category::where('user_id', $user->id)->get();
        $groceriesCategory = $categories->firstWhere('name', 'Groceries');
        $utilitiesCategory = $categories->firstWhere('name', 'Utilities');
        $entertainmentCategory = $categories->firstWhere('name', 'Streaming Subscriptions');
        $transportCategory = $categories->firstWhere('name', 'Transportation');
        $housingCategory = $categories->firstWhere('name', 'Rent/Mortgage');

        // Create bills with realistic cadences
        $bills = [
            Bill::factory()->monthly()->forUser($user)->create([
                'name' => 'Rent',
                'amount' => 1200.00,
                'category_id' => $housingCategory?->id,
                'day_of_month' => 1,
                'autopay' => true,
                'next_due_date' => now()->startOfMonth()->addMonth(),
            ]),
            Bill::factory()->monthly()->forUser($user)->create([
                'name' => 'Electricity',
                'amount' => 85.50,
                'category_id' => $utilitiesCategory?->id,
                'day_of_month' => 15,
                'autopay' => true,
                'next_due_date' => now()->day(15)->addMonth(),
            ]),
            Bill::factory()->monthly()->forUser($user)->create([
                'name' => 'Internet',
                'amount' => 45.00,
                'category_id' => $utilitiesCategory?->id,
                'day_of_month' => 10,
                'autopay' => true,
                'next_due_date' => now()->day(10)->addMonth(),
            ]),
            Bill::factory()->monthly()->forUser($user)->create([
                'name' => 'Netflix',
                'amount' => 15.99,
                'category_id' => $entertainmentCategory?->id,
                'day_of_month' => 5,
                'autopay' => true,
                'next_due_date' => now()->day(5)->addMonth(),
            ]),
            Bill::factory()->monthly()->forUser($user)->create([
                'name' => 'Spotify',
                'amount' => 10.99,
                'category_id' => $entertainmentCategory?->id,
                'day_of_month' => 12,
                'autopay' => true,
                'next_due_date' => now()->day(12)->addMonth(),
            ]),
            Bill::factory()->weekly()->forUser($user)->create([
                'name' => 'Weekly Food Shop',
                'amount' => 80.00,
                'category_id' => $groceriesCategory?->id,
                'weekday' => 6, // Saturday
                'autopay' => false,
                'next_due_date' => now()->next('Saturday'),
            ]),
            Bill::factory()->yearly()->forUser($user)->create([
                'name' => 'Car Insurance',
                'amount' => 650.00,
                'category_id' => $categories->firstWhere('name', 'Insurance')?->id,
                'day_of_month' => 1,
                'autopay' => false,
                'next_due_date' => now()->addMonths(3)->startOfMonth(),
            ]),
            Bill::factory()->yearly()->forUser($user)->create([
                'name' => 'Amazon Prime',
                'amount' => 95.00,
                'category_id' => $entertainmentCategory?->id,
                'day_of_month' => 20,
                'autopay' => true,
                'next_due_date' => now()->addMonths(8)->day(20),
            ]),
        ];

        // Create credit cards
        $creditCards = [
            CreditCard::factory()->forUser($user)->create([
                'name' => 'Visa Rewards',
                'starting_balance' => 450.00,
                'credit_limit' => 5000.00,
            ]),
            CreditCard::factory()->forUser($user)->create([
                'name' => 'Mastercard',
                'starting_balance' => 125.00,
                'credit_limit' => 3000.00,
            ]),
            CreditCard::factory()->forUser($user)->create([
                'name' => 'American Express',
                'starting_balance' => 890.00,
                'credit_limit' => 10000.00,
            ]),
        ];

        // Create savings accounts
        $savingsAccounts = [
            SavingsAccount::factory()->forUser($user)->create([
                'name' => 'Emergency Fund',
                'target_amount' => 10000.00,
            ]),
            SavingsAccount::factory()->forUser($user)->create([
                'name' => 'Holiday Fund',
                'target_amount' => 3000.00,
            ]),
            SavingsAccount::factory()->asBillsFloat()->forUser($user)->create([
                'target_amount' => 2000.00,
            ]),
        ];

        // Create transactions over the past 3 months
        // Mix of income, expenses, and various categories
        $transactions = [];

        // Monthly salary transactions
        for ($i = 0; $i < 3; $i++) {
            $transactions[] = Transaction::factory()->income()->forUser($user)->create([
                'name' => 'Monthly Salary',
                'amount' => 3500.00,
                'category_id' => $categories->firstWhere('name', 'Salary')?->id,
                'payment_date' => now()->subMonths(2 - $i)->startOfMonth()->addDays(24),
            ]);
        }

        // Random expenses throughout the past 3 months
        $expenseCategories = $categories->whereIn('name', [
            'Groceries', 'Restaurants', 'Coffee', 'Fuel', 'Public Transport',
            'Shopping', 'Personal Care', 'Entertainment',
        ]);

        for ($i = 0; $i < 80; $i++) {
            $category = $expenseCategories->random();
            $amount = match ($category->name) {
                'Groceries' => fake()->randomFloat(2, 20, 120),
                'Coffee' => fake()->randomFloat(2, 3, 8),
                'Restaurants' => fake()->randomFloat(2, 15, 85),
                'Fuel' => fake()->randomFloat(2, 40, 75),
                'Public Transport' => fake()->randomFloat(2, 2.5, 15),
                'Shopping' => fake()->randomFloat(2, 20, 200),
                'Personal Care' => fake()->randomFloat(2, 10, 60),
                default => fake()->randomFloat(2, 5, 100),
            };

            $transactions[] = Transaction::factory()->expense()->forUser($user)->create([
                'name' => $this->getExpenseName($category->name),
                'amount' => $amount,
                'category_id' => $category->id,
                'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);
        }

        // Add some credit card payments
        foreach ($creditCards as $card) {
            for ($i = 0; $i < 2; $i++) {
                CreditCardPayment::factory()->forCard($card)->create([
                    'amount' => fake()->randomFloat(2, 100, 500),
                    'payment_date' => now()->subMonths($i + 1)->day(15),
                ]);
            }
        }

        // Create BNPL purchases with installments
        $bnplPurchases = [
            BnplPurchase::factory()->create([
                'user_id' => $user->id,
                'merchant' => 'Apple',
                'total_amount' => 800.00,
                'provider' => \App\Enums\BnplProvider::Zilch,
                'fee' => 2.50,
                'purchase_date' => now()->subMonths(2),
            ]),
            BnplPurchase::factory()->create([
                'user_id' => $user->id,
                'merchant' => 'ASOS',
                'total_amount' => 240.00,
                'provider' => \App\Enums\BnplProvider::ClearPay,
                'fee' => 0,
                'purchase_date' => now()->subMonth(),
            ]),
        ];

        // Create installments for BNPL purchases
        foreach ($bnplPurchases as $purchase) {
            $installmentAmount = $purchase->total_amount / 4;
            for ($i = 0; $i < 4; $i++) {
                $isPaid = $i < 2; // First 2 installments are paid
                BnplInstallment::factory()->create([
                    'user_id' => $user->id,
                    'bnpl_purchase_id' => $purchase->id,
                    'installment_number' => $i + 1,
                    'amount' => $installmentAmount,
                    'due_date' => $purchase->purchase_date->copy()->addWeeks($i * 2),
                    'is_paid' => $isPaid,
                    'paid_date' => $isPaid ? $purchase->purchase_date->copy()->addWeeks($i * 2) : null,
                ]);
            }
        }

        // Create savings transfers
        foreach ($savingsAccounts as $account) {
            // Some deposits
            for ($i = 0; $i < 3; $i++) {
                SavingsTransfer::factory()->deposit()->forAccount($account)->create([
                    'amount' => fake()->randomFloat(2, 50, 300),
                    'transfer_date' => now()->subMonths($i + 1),
                ]);
            }

            // One withdrawal
            if ($account->name !== 'Bills Float') {
                SavingsTransfer::factory()->withdraw()->forAccount($account)->create([
                    'amount' => fake()->randomFloat(2, 25, 100),
                    'transfer_date' => now()->subWeeks(2),
                ]);
            }
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Test user credentials:');
        $this->command->info('Email: test@example.com');
        $this->command->info('Password: password');
    }

    /**
     * Get a realistic expense name based on category.
     */
    private function getExpenseName(string $categoryName): string
    {
        return match ($categoryName) {
            'Groceries' => fake()->randomElement(['Tesco', 'Sainsbury\'s', 'Asda', 'Morrisons', 'Waitrose', 'Aldi', 'Lidl']),
            'Coffee' => fake()->randomElement(['Starbucks', 'Costa', 'Caffè Nero', 'Pret', 'Local Coffee Shop']),
            'Restaurants' => fake()->randomElement(['Pizza Express', 'Nando\'s', 'Wagamama', 'Local Restaurant', 'Thai Takeaway', 'Chinese Food']),
            'Fuel' => fake()->randomElement(['Shell', 'BP', 'Tesco Petrol', 'Sainsbury\'s Fuel', 'Esso']),
            'Public Transport' => fake()->randomElement(['TfL', 'Train Ticket', 'Bus Fare', 'Taxi', 'Uber']),
            'Shopping' => fake()->randomElement(['Amazon', 'ASOS', 'Next', 'H&M', 'Zara', 'Sports Direct']),
            'Personal Care' => fake()->randomElement(['Haircut', 'Boots', 'Superdrug', 'Salon', 'Barber']),
            'Entertainment' => fake()->randomElement(['Cinema', 'Theatre', 'Concert', 'Games', 'Books']),
            default => fake()->words(2, true),
        };
    }
}
