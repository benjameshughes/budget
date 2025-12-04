<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PayCadence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pay_cadence' => PayCadence::class,
            'pay_day' => 'integer',
            'weekly_budget' => 'decimal:2',
            'weekly_savings_goal' => 'decimal:2',
            'bills_float_target' => 'decimal:2',
            'bills_float_multiplier' => 'decimal:1',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the last pay date based on user's pay cadence and pay day
     */
    public function lastPayDate(): \Carbon\Carbon
    {
        $today = now();

        if ($this->pay_cadence === PayCadence::Weekly) {
            // pay_day is 0-6 (Sunday-Saturday)
            $dayOfWeek = $this->pay_day ?? 5; // default Friday

            // If today IS pay day, use today
            if ($today->dayOfWeek === $dayOfWeek) {
                return $today->startOfDay();
            }

            $lastPayDate = $today->copy()->previous($dayOfWeek);

            return $lastPayDate->startOfDay();
        }

        // Monthly: pay_day is 1-31
        $dayOfMonth = $this->pay_day ?? 1;
        $lastPayDate = $today->copy()->day($dayOfMonth);

        if ($lastPayDate->gt($today)) {
            $lastPayDate->subMonth();
        }

        return $lastPayDate->startOfDay();
    }

    /**
     * Get the next pay date based on user's pay cadence and pay day
     */
    public function nextPayDate(): \Carbon\Carbon
    {
        $today = now();

        if ($this->pay_cadence === PayCadence::Weekly) {
            $dayOfWeek = $this->pay_day ?? 5;

            // If today is pay day, next is in 7 days
            if ($today->dayOfWeek === $dayOfWeek) {
                return $today->copy()->addWeek()->startOfDay();
            }

            return $today->copy()->next($dayOfWeek)->startOfDay();
        }

        // Monthly
        $dayOfMonth = $this->pay_day ?? 1;
        $nextPayDate = $today->copy()->day(min($dayOfMonth, $today->daysInMonth));

        if ($nextPayDate->lte($today)) {
            $nextPayDate->addMonth();
            // Handle months with fewer days
            $nextPayDate->day(min($dayOfMonth, $nextPayDate->daysInMonth));
        }

        return $nextPayDate->startOfDay();
    }

    /**
     * Check if user has configured a bills float target.
     */
    public function hasBillsFloatTarget(): bool
    {
        return $this->bills_float_target !== null && $this->bills_float_target > 0;
    }

    /**
     * Get the user's bills float account.
     */
    public function billsFloatAccount(): HasOne
    {
        return $this->hasOne(SavingsAccount::class)->where('is_bills_float', true);
    }

    /**
     * Get the user's bills.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    // Salary model removed in favor of unified transactions
}
