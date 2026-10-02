<?php

namespace App\Enums;

enum Transmission: string
{
    case Manual = 'MANUAL';
    case Automatic = 'AUTOMATIC';
    case SemiAutomatic = 'SEMI_AUTOMATIC';
    case Cvt = 'CVT';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manuelle',
            self::Automatic => 'Automatique',
            self::SemiAutomatic => 'Semi-automatique',
            self::Cvt => 'CVT',
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
