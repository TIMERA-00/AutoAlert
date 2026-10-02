<?php

namespace App\Enums;

enum BodyType: string
{
    case Sedan = 'SEDAN';
    case Suv = 'SUV';
    case Hatchback = 'HATCHBACK';
    case StationWagon = 'STATION_WAGON';
    case Pickup = 'PICKUP';
    case Van = 'VAN';
    case Coupe = 'COUPE';
    case Cabriolet = 'CABRIOLET';
    case Bus = 'BUS';
    case Motorcycle = 'MOTORCYCLE';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Sedan => 'Berline',
            self::Suv => 'SUV',
            self::Hatchback => 'Berline compacte',
            self::StationWagon => 'Break',
            self::Pickup => 'Pick-up',
            self::Van => 'Fourgon',
            self::Coupe => 'Coupe',
            self::Cabriolet => 'Cabriolet',
            self::Bus => 'Bus',
            self::Motorcycle => 'Moto',
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
