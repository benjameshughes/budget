<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class BillsPotSeeder extends Seeder
{
    /**
     * Create a bills float savings account for the first user.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->warn('No user found. Skipping bills pot seeder.');

            return;
        }

        // Check if user already has a bills float account
        if ($user->billsFloatAccount) {
            $this->command->info('User already has a bills float account.');

            return;
        }

        SavingsAccount::create([
            'user_id' => $user->id,
            'name' => 'Bills Pot',
            'notes' => 'Buffer account for upcoming bills',
            'is_bills_float' => true,
        ]);

        $this->command->info('Bills pot created for user: '.$user->name);
    }
}
