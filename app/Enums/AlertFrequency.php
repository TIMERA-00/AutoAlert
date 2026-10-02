<?php

namespace App\Enums;

enum AlertFrequency: string
{
    case Immediate = 'IMMEDIATE';
    case Daily = 'DAILY';
    case Weekly = 'WEEKLY';

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Immediatement',
            self::Daily => 'Une fois par jour',
            self::Weekly => 'Une fois par semaine',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Immediate => 'Vous etes notifie des la publication',
            self::Daily => 'Un resume chaque matin',
            self::Weekly => 'Un resume chaque lundi',
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
