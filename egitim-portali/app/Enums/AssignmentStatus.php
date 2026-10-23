<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Atandı',
            self::InProgress => 'Devam Ediyor',
            self::Completed => 'Tamamlandı',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Assigned => 'secondary',
            self::InProgress => 'warning',
            self::Completed => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Assigned => 'hourglass',
            self::InProgress => 'play-circle',
            self::Completed => 'check-circle',
        };
    }
}
