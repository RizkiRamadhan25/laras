<?php

namespace App\Models;

use App\Enums\TransactionEntryRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionEntry extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionEntryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'transaction_id',
        'account_id',
        'finance_category_id',
        'amount',
        'role',
        'memo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'role' => TransactionEntryRole::class,
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)
            ->withTrashed();
    }

    public function financeCategory(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class)
            ->withTrashed();
    }
}
