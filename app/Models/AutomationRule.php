<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TriggerEvent;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    /** @use HasFactory<\Database\Factories\AutomationRuleFactory> */
    use BelongsToUser, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'trigger_event' => TriggerEvent::class,
            'trigger_conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'run_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationRuleLog::class);
    }

    public function incrementRunCount(): void
    {
        $this->update([
            'run_count' => $this->run_count + 1,
            'last_run_at' => now(),
        ]);
    }
}
