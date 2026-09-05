<?php

namespace App;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case REFUNDED = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PARTIAL => 'Partial',
            self::PAID => 'Paid',
            self::REFUNDED => 'Refunded',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PARTIAL => 'blue',
            self::PAID => 'green',
            self::REFUNDED => 'red',
        };
    }
}
