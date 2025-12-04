<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsAccount extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'is_bills_float' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(SavingsTransfer::class);
    }

    public function currentBalance(): float
    {
        $deposits = (float) $this->transfers()
            ->where('direction', 'deposit')
            ->sum('amount');

        $withdrawals = (float) $this->transfers()
            ->where('direction', 'withdraw')
            ->sum('amount');

        return $deposits - $withdrawals;
    }

    public function progressPercentage(): float
    {
        if (! $this->target_amount || $this->target_amount <= 0) {
            return 0.0;
        }

        $progress = ($this->currentBalance() / (float) $this->target_amount) * 100;

        return min(100.0, max(0.0, $progress));
    }
}
