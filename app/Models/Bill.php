<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillCadence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_due_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'cadence' => BillCadence::class,
            'autopay' => 'bool',
            'active' => 'bool',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function monthlyEquivalent(): float
    {
        $multiplier = match ($this->cadence) {
            BillCadence::Weekly => 52 / 12,
            BillCadence::Biweekly => 26 / 12,
            BillCadence::Monthly => 1,
            BillCadence::Yearly => 1 / 12,
        };

        return (float) $this->amount * $multiplier * $this->interval_every;
    }
}
