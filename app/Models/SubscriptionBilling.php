<?php

namespace App\Models;

use App\Enums\SubscriptionBillingStatus;
use Database\Factories\SubscriptionBillingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionBilling extends Model
{
    /** @use HasFactory<SubscriptionBillingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'user_id',
        'transaction_id',
        'scheduled_for',
        'amount',
        'currency_code',
        'status',
        'attempted_at',
        'processed_at',
        'failure_reason',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_for' => 'immutable_date',
            'amount' => 'decimal:2',
            'status' => SubscriptionBillingStatus::class,
            'attempted_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            Subscription::class
        )->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            Transaction::class
        )->withTrashed();
    }
}
