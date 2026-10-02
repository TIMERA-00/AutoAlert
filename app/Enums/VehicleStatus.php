<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Draft = 'DRAFT';
    case Published = 'PUBLISHED';
    case Reserved = 'RESERVED';
    case Sold = 'SOLD';
    case Archived = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Disponible',
            self::Reserved => 'Reserve',
            self::Sold => 'Vendu',
            self::Archived => 'Archive',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Published => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            self::Reserved => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::Sold => 'bg-red-100 text-red-800 ring-red-200',
            self::Archived => 'bg-zinc-200 text-zinc-700 ring-zinc-300',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    /** Allowed transitions of the DRAFT -> PUBLISHED -> RESERVED -> SOLD -> ARCHIVED workflow. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published, self::Archived],
            self::Published => [self::Reserved, self::Sold, self::Archived, self::Draft],
            self::Reserved => [self::Published, self::Sold, self::Archived],
            self::Sold => [self::Archived],
            self::Archived => [self::Draft],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
