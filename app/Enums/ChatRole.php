<?php

namespace App\Enums;

enum ChatRole: string
{
    case User = 'USER';
    case Assistant = 'ASSISTANT';
    case System = 'SYSTEM';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => mb_strtolower($case->value)])
            ->all();
    }
}
