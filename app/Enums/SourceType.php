<?php

namespace App\Enums;

enum SourceType: string
{
    case Manual = 'MANUAL';
    case Scraper = 'SCRAPER';
    case Api = 'API';
    case Partner = 'PARTNER';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Saisie manuelle',
            self::Scraper => 'Import automatise',
            self::Api => 'API officielle',
            self::Partner => 'Partenaire',
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
