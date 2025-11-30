<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Food & Dining', 'description' => 'General food and dining expenses.'],
            ['name' => 'Groceries', 'description' => 'Supermarket and grocery store purchases.'],
            ['name' => 'Restaurants', 'description' => 'Eating out at restaurants and cafes.'],
            ['name' => 'Coffee', 'description' => 'Coffee shops and beverages.'],
            ['name' => 'Alcohol & Bars', 'description' => 'Bars, pubs, and alcohol purchases.'],

            ['name' => 'Family', 'description' => 'General family-related expenses.'],
            ['name' => 'Kids', 'description' => 'Children-related expenses.'],
            ['name' => 'Childcare', 'description' => 'Daycare, babysitting, and childcare.'],
            ['name' => 'Education', 'description' => 'Tuition, school supplies, and courses.'],

            ['name' => 'Housing', 'description' => 'General housing expenses.'],
            ['name' => 'Rent/Mortgage', 'description' => 'Monthly rent or mortgage payments.'],
            ['name' => 'Utilities', 'description' => 'Electricity, gas, water utilities.'],
            ['name' => 'Internet', 'description' => 'Home internet service.'],
            ['name' => 'Mobile Phone', 'description' => 'Mobile/cell phone plans and top-ups.'],

            ['name' => 'Transportation', 'description' => 'General transport expenses.'],
            ['name' => 'Fuel', 'description' => 'Petrol, diesel, or EV charging.'],
            ['name' => 'Public Transport', 'description' => 'Bus, train, tram, and metro fares.'],
            ['name' => 'Car Payment', 'description' => 'Car finance or lease payments.'],
            ['name' => 'Parking & Tolls', 'description' => 'Parking lots, meters, and toll roads.'],

            ['name' => 'Health & Fitness', 'description' => 'General health and fitness.'],
            ['name' => 'Medical', 'description' => 'Doctor visits and medical services.'],
            ['name' => 'Pharmacy', 'description' => 'Medicines and pharmacy purchases.'],
            ['name' => 'Insurance', 'description' => 'Health, auto, home, and other insurance.'],

            ['name' => 'Entertainment', 'description' => 'Leisure and entertainment spending.'],
            ['name' => 'Streaming Subscriptions', 'description' => 'Netflix, Spotify, and other streaming services.'],
            ['name' => 'Shopping', 'description' => 'General shopping and retail.'],
            ['name' => 'Personal Care', 'description' => 'Haircuts, cosmetics, and personal items.'],
            ['name' => 'Travel', 'description' => 'Flights, hotels, and travel expenses.'],
            ['name' => 'Gifts & Donations', 'description' => 'Gifts and charitable donations.'],

            ['name' => 'Savings', 'description' => 'Transfers to savings accounts.'],
            ['name' => 'Investments', 'description' => 'Investment contributions and fees.'],
            ['name' => 'Taxes', 'description' => 'Income or property taxes.'],
            ['name' => 'Fees & Charges', 'description' => 'Bank fees and miscellaneous charges.'],

            ['name' => 'Income', 'description' => 'General income category.'],
            ['name' => 'Salary', 'description' => 'Salary and wage income.'],
            ['name' => 'Bonus', 'description' => 'Bonus payments.'],
            ['name' => 'Interest Income', 'description' => 'Interest from bank accounts.'],
            ['name' => 'Dividends', 'description' => 'Dividend income.'],
            ['name' => 'Refunds', 'description' => 'Refunds and returns.'],
        ];

        foreach ($categories as $category) {
            $existing = DB::table('categories')->where('name', $category['name'])->first();
            if ($existing) {
                DB::table('categories')
                    ->where('id', $existing->id)
                    ->update([
                        'description' => $category['description'],
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('categories')->insert([
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
