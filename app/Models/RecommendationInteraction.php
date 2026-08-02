<?php

namespace App\Models;

use App\Enums\RecommendationInteractionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationInteraction extends Model
{
    protected $fillable = [
        'user_id',
        'recommendation_key',
        'recommendation_kind',
        'interaction_type',
        'title',
        'snapshot',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'interaction_type' => RecommendationInteractionType::class,

            'snapshot' => 'array',

            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }
}
