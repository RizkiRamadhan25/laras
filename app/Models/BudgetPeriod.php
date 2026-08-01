<?php

namespace App\Models;

use App\Enums\BudgetPeriodStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetPeriod extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'budget_id',
        'period_start',
        'period_end',
        'budget_amount',
        'used_amount',
        'remaining_amount',
        'usage_percent',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' =>
                'immutable_date',

            'period_end' =>
                'immutable_date',

            'budget_amount' =>
                'decimal:2',

            'used_amount' =>
                'decimal:2',

            'remaining_amount' =>
                'decimal:2',

            'usage_percent' =>
                'decimal:2',

            'status' =>
                BudgetPeriodStatus::class,
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(
            Budget::class
        );
    }
}
