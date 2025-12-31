<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PennyChallengeDay extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'deposited_at' => 'datetime',
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(PennyChallenge::class, 'penny_challenge_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the amount for this day in pounds.
     * Day 1 = £0.01, Day 50 = £0.50, Day 365 = £3.65
     */
    public function amount(): float
    {
        return $this->day_number / 100;
    }

    /**
     * Check if this day has been deposited.
     */
    public function isDeposited(): bool
    {
        return $this->deposited_at !== null;
    }

    /**
     * Check if this day is pending (not yet deposited).
     */
    public function isPending(): bool
    {
        return $this->deposited_at === null;
    }

    /**
     * Get the actual calendar date for this day.
     */
    public function date(): \Carbon\Carbon
    {
        return $this->challenge->start_date->copy()->addDays($this->day_number - 1);
    }
}
