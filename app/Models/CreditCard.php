<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCard extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'starting_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

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
        $spending = (float) $this->spending()->sum('amount');
        $payments = (float) $this->payments()->sum('amount');

        return $this->starting_balance + $spending - $payments;
    }

    public function availableCredit(): ?float
    {
        if (! $this->credit_limit || $this->credit_limit <= 0) {
            return null;
        }

        return $this->credit_limit - $this->currentBalance();
    }

    public function utilizationPercent(): ?float
    {
        if (! $this->credit_limit || $this->credit_limit <= 0) {
            return null;
        }

        return min(100, ($this->currentBalance() / $this->credit_limit) * 100);
    }
}
