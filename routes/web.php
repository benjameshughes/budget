<?php

use App\Http\Controllers\AdvisorController;
use App\Livewire\Settings\ApiTokens;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\BillsFloat;
use App\Livewire\Settings\Categories;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\PayBudget;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('analytics', 'analytics')->name('analytics');
    Route::view('transactions', 'transactions')->name('transactions');
    Route::view('bnpl', 'bnpl')->name('bnpl');
    Route::view('credit-cards', 'credit-cards')->name('credit-cards');
    Route::view('bills', 'bills')->name('bills');
    Route::view('savings', 'savings')->name('savings');
    Route::view('penny-challenge', 'penny-challenge')->name('penny-challenge');
    Route::view('calendar', 'calendar')->name('calendar');

    Route::get('advisor/stream/{transaction}', [AdvisorController::class, 'stream'])
        ->name('advisor.stream');

    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/pay-budget', PayBudget::class)->name('pay-budget.edit');
    Route::get('settings/bills-float', BillsFloat::class)->name('bills-float.edit');
    Route::get('settings/categories', Categories::class)->name('categories.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');
    Route::get('settings/api', ApiTokens::class)->name('api.tokens');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
