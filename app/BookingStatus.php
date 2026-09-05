<?php

namespace App;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case RESCHEDULED = 'rescheduled';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
            self::RESCHEDULED => 'Rescheduled',
            self::COMPLETED => 'Completed',
            self::NO_SHOW => 'No Show',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::CONFIRMED => 'green',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
            self::RESCHEDULED => 'blue',
            self::COMPLETED => 'green',
            self::NO_SHOW => 'red',
        };
    }

    public function canTransitionTo(BookingStatus $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [self::CONFIRMED, self::REJECTED, self::CANCELLED]),
            self::CONFIRMED => in_array($status, [self::CANCELLED, self::RESCHEDULED, self::COMPLETED, self::NO_SHOW]),
            self::RESCHEDULED => in_array($status, [self::CONFIRMED, self::CANCELLED, self::COMPLETED, self::NO_SHOW]),
            self::CANCELLED => false,
            self::REJECTED => false,
            self::COMPLETED => false,
            self::NO_SHOW => false,
        };
    }
}
