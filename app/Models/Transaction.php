<?php

namespace App\Models;

use App\Enums\TransactionEntryRole;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTransferKind;
use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use ValueError;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
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

    public function transferKind(): ?TransactionTransferKind
    {
        if ($this->type !== TransactionType::Transfer) {
            return null;
        }

        $value = $this->metadata['transfer_kind']
            ?? TransactionTransferKind::Internal->value;

        try {
            return TransactionTransferKind::from(
                (string) $value
            );
        } catch (ValueError) {
            return TransactionTransferKind::Internal;
        }
    }

    public function isInternalTransfer(): bool
    {
        return $this->transferKind()
            === TransactionTransferKind::Internal;
    }

    public function isExternalTransfer(): bool
    {
        return $this->transferKind()
            === TransactionTransferKind::External;
    }

    /**
     * @return array<string, string|null>|null
     */
    public function externalDestination(): ?array
    {
        if (! $this->isExternalTransfer()) {
            return null;
        }

        $destination = $this->metadata['external_destination']
            ?? null;

        if (! is_array($destination)) {
            return null;
        }

        return [
            'name' => isset($destination['name'])
                ? (string) $destination['name']
                : null,
            'institution' => isset($destination['institution'])
                ? (string) $destination['institution']
                : null,
            'account_number' => isset($destination['account_number'])
                ? (string) $destination['account_number']
                : null,
        ];
    }

    public function displayAmount(): string
    {
        $entries = $this->relationLoaded('entries')
            ? $this->entries
            : $this->entries()->get();

        if ($this->type === TransactionType::Transfer) {
            $principalEntries = $entries->where(
                'role',
                TransactionEntryRole::Principal
            );

            $incomingPrincipal = $principalEntries->reduce(
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

            if (bccomp($incomingPrincipal, '0.00', 2) > 0) {
                return $incomingPrincipal;
            }

            $outgoingPrincipal = $principalEntries->reduce(
                static function (
                    string $total,
                    TransactionEntry $entry
                ): string {
                    if (bccomp($entry->amount, '0.00', 2) >= 0) {
                        return $total;
                    }

                    return bcadd(
                        $total,
                        bcsub('0.00', $entry->amount, 2),
                        2
                    );
                },
                '0.00'
            );

            return $outgoingPrincipal;
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

    public function subscriptionBilling(): HasOne
    {
        return $this->hasOne(
            SubscriptionBilling::class
        );
    }
}
