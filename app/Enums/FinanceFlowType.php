<?php

namespace App\Enums;

enum FinanceFlowType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
            self::Both => 'Pemasukan dan pengeluaran',
        };
    }
}
