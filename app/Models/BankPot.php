<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PotPurpose;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankPot extends Model
{
    /** @use HasFactory<\Database\Factories\BankPotFactory> */
    use BelongsToUser, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'balance_pence' => 'integer',
            'goal_amount_pence' => 'integer',
            'is_locked' => 'boolean',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'purpose' => PotPurpose::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connectedAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class);
    }

    public function balanceInPounds(): float
    {
        return $this->balance_pence / 100;
    }

    public function goalInPounds(): ?float
    {
        return $this->goal_amount_pence !== null ? $this->goal_amount_pence / 100 : null;
    }

    public function isPot(): bool
    {
        return $this->type === 'pot';
    }

    public function isSpace(): bool
    {
        return $this->type === 'space';
    }
}
