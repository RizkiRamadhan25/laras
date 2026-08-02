<?php

namespace App\Models;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'starts_at',
        'ends_at',
        'due_at',
        'all_day',
        'estimated_minutes',
        'is_flexible',
        'location',
        'color',
        'sort_order',
        'completed_at',
        'cancelled_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'priority' => ActivityPriority::class,
            'status' => ActivityStatus::class,

            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',

            'all_day' => 'boolean',
            'estimated_minutes' => 'integer',
            'is_flexible' => 'boolean',
            'sort_order' => 'integer',

            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn(
            'status',
            [
                ActivityStatus::Planned->value,
                ActivityStatus::InProgress->value,
            ]
        );
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where(
            'status',
            ActivityStatus::Completed->value
        );
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function relevantAt(): mixed
    {
        return $this->due_at ?? $this->starts_at;
    }
}
