<?php

namespace App\Enums;

enum VehicleSort: string
{
    case Recent = 'recent';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
    case YearDesc = 'year_desc';
    case MileageAsc = 'mileage_asc';

    public function label(): string
    {
        return match ($this) {
            self::Recent => 'Plus recents',
            self::PriceAsc => 'Prix croissant',
            self::PriceDesc => 'Prix decroissant',
            self::YearDesc => 'Annee (recent)',
            self::MileageAsc => 'Kilometrage (croissant)',
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
