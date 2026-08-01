<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Trackable;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model implements Trackable
{
    use BelongsToUser;
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'starting_balance' => 'decimal:2',
            'minimum_payment' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'cleared_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function currentBalance(): float
    {
        return (float) $this->starting_balance - (float) $this->payments->sum('amount');
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
        return $this->currentBalance() <= 0 || $this->cleared_at !== null;
    }
}
