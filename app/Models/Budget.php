<?php

namespace App\Models;

use App\Enums\BudgetPeriodType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'finance_category_id',
        'name',
        'amount',
        'period_type',
        'warning_threshold_percent',
        'start_date',
        'end_date',
        'is_recurring',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',

            'warning_threshold_percent' =>
                'decimal:2',

            'period_type' =>
                BudgetPeriodType::class,

            'start_date' =>
                'immutable_date',

            'end_date' =>
                'immutable_date',

            'is_recurring' =>
                'boolean',

            'is_active' =>
                'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function financeCategory(): BelongsTo
    {
        return $this->belongsTo(
            FinanceCategory::class
        );
    }

    public function periods(): HasMany
    {
        return $this->hasMany(
            BudgetPeriod::class
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'is_active',
            true
        );
    }

    public function isActiveOn(
        CarbonInterface $date
    ): bool {
        if (! $this->is_active) {
            return false;
        }

        $target = CarbonImmutable::parse(
            $date->toDateString()
        );

        if (
            $target->lt(
                $this->start_date
            )
        ) {
            return false;
        }

        if (
            $this->end_date !== null
            && $target->gt(
                $this->end_date
            )
        ) {
            return false;
        }

        return true;
    }
}
