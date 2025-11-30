<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionFeedback extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFeedbackFactory> */
    use HasFactory;

    protected $table = 'transaction_feedback';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
