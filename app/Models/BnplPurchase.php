<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BnplProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BnplPurchase extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'purchase_date' => 'date',
            'provider' => BnplProvider::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(BnplInstallment::class);
    }

    public function remainingBalance(): float
    {
        return (float) $this->installments()
            ->where('is_paid', false)
            ->sum('amount');
    }

    public function isFullyPaid(): bool
    {
        return $this->installments()
            ->where('is_paid', false)
            ->count() === 0;
    }

    public function nextUnpaidInstallment(): ?BnplInstallment
    {
        return $this->installments()
            ->where('is_paid', false)
            ->orderBy('due_date')
            ->first();
    }

    public function paidInstallmentsCount(): int
    {
        return $this->installments()
            ->where('is_paid', true)
            ->count();
    }
}
