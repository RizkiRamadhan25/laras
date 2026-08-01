<?php

namespace App\Enums;

enum TransactionSource: string
{
    case Manual = 'manual';
    case ReceiptScan = 'receipt_scan';
    case PaymentScan = 'payment_scan';
    case Import = 'import';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Input manual',
            self::ReceiptScan => 'Scan struk',
            self::PaymentScan => 'Scan pembayaran',
            self::Import => 'Impor data',
            self::System => 'Sistem',
        };
    }
}
