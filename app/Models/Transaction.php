<?php

namespace App\Models;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\TransactionEntryRole;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'source',
        'occurred_at',
        'description',
        'counterparty',
        'reference_number',
        'notes',
        'metadata',
        'posted_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'source' => TransactionSource::class,
            'occurred_at' => 'datetime',
            'metadata' => 'array',
            'posted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    public function displayAmount(): string
    {
        $entries = $this->relationLoaded('entries')
            ? $this->entries
            : $this->entries()->get();

        if ($this->type === TransactionType::Transfer) {
            return $entries
                ->where(
                    'role',
                    TransactionEntryRole::Principal
                )
                ->reduce(
                    static function (
                        string $total,
                        TransactionEntry $entry
                    ): string {
                        if (bccomp($entry->amount, '0.00', 2) <= 0) {
                            return $total;
                        }

                        return bcadd(
                            $total,
                            $entry->amount,
                            2
                        );
                    },
                    '0.00'
                );
        }

        $total = $entries
            ->where(
                'role',
                TransactionEntryRole::Principal
            )
            ->reduce(
                static fn (
                    string $total,
                    TransactionEntry $entry
                ): string => bcadd(
                    $total,
                    $entry->amount,
                    2
                ),
                '0.00'
            );

        return bccomp($total, '0.00', 2) < 0
            ? bcsub('0.00', $total, 2)
            : $total;
    }
}
