<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Trackable;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCard extends Model implements Trackable
{
    use BelongsToUser;
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'starting_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'minimum_payment' => 'decimal:2',
            'interest_rate' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CreditCardPayment::class);
    }

    public function spending(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function currentBalance(): float
    {
        $spending = (float) $this->spending->sum('amount');
        $payments = (float) $this->payments->sum('amount');

        return $this->starting_balance + $spending - $payments;
    }

    public function availableCredit(): ?float
    {
        if (! $this->credit_limit || $this->credit_limit <= 0) {
            return null;
        }

        return $this->credit_limit - $this->currentBalance();
    }

    public function minimumPayment(): float
    {
        return (float) $this->minimum_payment;
    }

    public function monthlyInterest(): float
    {
        if (! $this->interest_rate) {
            return 0.0;
        }

        return $this->currentBalance() * ((float) $this->interest_rate / 100 / 12);
    }

    public function isCleared(): bool
    {
        return $this->currentBalance() <= 0;
    }

    public function utilizationPercent(): ?float
    {
        if (! $this->credit_limit || $this->credit_limit <= 0) {
            return null;
        }

        return min(100, ($this->currentBalance() / $this->credit_limit) * 100);
    }
}
