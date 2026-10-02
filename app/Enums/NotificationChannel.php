<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'EMAIL';
    case Whatsapp = 'WHATSAPP';
    case InApp = 'IN_APP';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Whatsapp => 'WhatsApp',
            self::InApp => 'Sur le site',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
