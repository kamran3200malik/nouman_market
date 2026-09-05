<?php

namespace App;

enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::SUSPENDED => 'gray',
        };
    }

    public function canTransitionTo(ApprovalStatus $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [self::APPROVED, self::REJECTED]),
            self::APPROVED => in_array($status, [self::SUSPENDED]),
            self::SUSPENDED => in_array($status, [self::APPROVED]),
            self::REJECTED => false,
        };
    }
}
