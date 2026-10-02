<?php

namespace App\Enums;

enum FuelType: string
{
    case Petrol = 'PETROL';
    case Diesel = 'DIESEL';
    case Hybrid = 'HYBRID';
    case Electric = 'ELECTRIC';
    case Lpg = 'LPG';
    case Cng = 'CNG';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Petrol => 'Essence',
            self::Diesel => 'Diesel',
            self::Hybrid => 'Hybride',
            self::Electric => 'Electrique',
            self::Lpg => 'GPL',
            self::Cng => 'GNV',
            self::Other => 'Autre',
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
