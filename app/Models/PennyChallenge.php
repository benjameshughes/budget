<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PennyChallenge extends Model
{
    use BelongsToUser;
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(PennyChallengeDay::class);
    }

    public function pendingDays(): HasMany
    {
        return $this->days()->whereNull('deposited_at');
    }

    public function depositedDays(): HasMany
    {
        return $this->days()->whereNotNull('deposited_at');
    }

    public function totalDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Calculate total possible amount for the challenge (1+2+3+...+n pence).
     * Uses arithmetic series formula: n(n+1)/2
     */
    public function totalPossible(): float
    {
        $n = $this->totalDays();

        return ($n * ($n + 1) / 2) / 100;
    }

    /**
     * Calculate total deposited amount based on day numbers.
     */
    public function totalDeposited(): float
    {
        return $this->depositedDays()->sum('day_number') / 100;
    }

    /**
     * Calculate remaining amount to complete the challenge.
     */
    public function totalRemaining(): float
    {
        return $this->totalPossible() - $this->totalDeposited();
    }

    /**
     * Get count of deposited days.
     */
    public function depositedCount(): int
    {
        return $this->depositedDays()->count();
    }

    /**
     * Calculate progress percentage.
     */
    public function progressPercentage(): float
    {
        $total = $this->totalPossible();
        if ($total <= 0) {
            return 0;
        }

        return min(100, ($this->totalDeposited() / $total) * 100);
    }

    /**
     * Check if challenge is complete (all days deposited).
     */
    public function isComplete(): bool
    {
        return $this->pendingDays()->count() === 0;
    }
}
