<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BankProvider;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectedAccount extends Model
{
    /** @use HasFactory<\Database\Factories\ConnectedAccountFactory> */
    use BelongsToUser, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'provider' => BankProvider::class,
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'balance_pence' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function bankPots(): HasMany
    {
        return $this->hasMany(BankPot::class);
    }

    public function balanceInPounds(): float
    {
        return $this->balance_pence / 100;
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at !== null && $this->token_expires_at->isPast();
    }
}
