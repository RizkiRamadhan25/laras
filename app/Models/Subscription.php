<?php

namespace App\Models;

use App\Enums\SubscriptionIntervalUnit;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'account_id',
        'finance_category_id',
        'name',
        'provider',
        'amount',
        'currency_code',
        'interval_unit',
        'interval_count',
        'started_on',
        'next_billing_on',
        'end_on',
        'billing_time',
        'auto_post',
        'reminder_days',
        'status',
        'last_billed_on',
        'paused_at',
        'cancelled_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',

            'interval_unit' =>
                SubscriptionIntervalUnit::class,

            'status' =>
                SubscriptionStatus::class,

            'interval_count' => 'integer',
            'started_on' => 'immutable_date',
            'next_billing_on' => 'immutable_date',
            'end_on' => 'immutable_date',
            'last_billed_on' => 'immutable_date',

            'auto_post' => 'boolean',
            'reminder_days' => 'array',
            'metadata' => 'array',

            'paused_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)
            ->withTrashed();
    }

    public function financeCategory(): BelongsTo
    {
        return $this
            ->belongsTo(FinanceCategory::class)
            ->withTrashed();
    }

    public function billings(): HasMany
    {
        return $this->hasMany(
            SubscriptionBilling::class
        )->orderByDesc('scheduled_for');
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            SubscriptionStatus::Active->value
        );
    }

    public function scopeDue(
        Builder $query,
        mixed $date
    ): Builder {
        return $query
            ->active()
            ->whereNotNull('next_billing_on')
            ->whereDate(
                'next_billing_on',
                '<=',
                $date
            );
    }

    public function recurringLabel(): string
    {
        return $this->interval_unit
            ->recurringLabel(
                $this->interval_count
            );
    }

    public function monthlyEquivalent(): string
    {
        $intervalCount = max(
            1,
            $this->interval_count
        );

        $monthlyAmount = match (
            $this->interval_unit
        ) {
            SubscriptionIntervalUnit::Day =>
                bcmul(
                    bcdiv(
                        $this->amount,
                        (string) $intervalCount,
                        6
                    ),
                    '30.4375',
                    2
                ),

            SubscriptionIntervalUnit::Week =>
                bcmul(
                    bcdiv(
                        $this->amount,
                        (string) $intervalCount,
                        6
                    ),
                    '4.345',
                    2
                ),

            SubscriptionIntervalUnit::Month =>
                bcdiv(
                    $this->amount,
                    (string) $intervalCount,
                    2
                ),

            SubscriptionIntervalUnit::Year =>
                bcdiv(
                    $this->amount,
                    (string) (
                        $intervalCount * 12
                    ),
                    2
                ),
        };

        return bcadd(
            $monthlyAmount,
            '0',
            2
        );
    }

    public function yearlyEquivalent(): string
    {
        return bcmul(
            $this->monthlyEquivalent(),
            '12',
            2
        );
    }
}
