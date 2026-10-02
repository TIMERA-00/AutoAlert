<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case Pending = 'PENDING';
    case Queued = 'QUEUED';
    case Sent = 'SENT';
    case Failed = 'FAILED';
    case Read = 'READ';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Queued => 'En file',
            self::Sent => 'Envoyee',
            self::Failed => 'Echec',
            self::Read => 'Lue',
        };
    }
}
