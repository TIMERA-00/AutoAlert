<?php

namespace App\Services;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use Symfony\Component\DomCrawler\Crawler;

/** Pure string normalisation helpers used by the import pipeline. */
class ListingNormalizer
{
    /** "19 500 000 FCFA", "19500000", "19.500.000 F" => 19500000 */
    public static function price(?string $raw): ?int
    {
        if (blank($raw)) {
            return null;
        }

        $cleaned = preg_replace('/[\u{00a0}\u{202f}]/u', ' ', $raw) ?? $raw;
        $cleaned = preg_replace('/(f\s?cfa|xof|francs?|eur|euros?|usd|\$)/iu', '', $cleaned) ?? $cleaned;

        if (! preg_match('/([0-9][0-9\s.,]*)/u', $cleaned, $m)) {
            return null;
        }

        $digits = preg_replace('/\s/u', '', $m[1]) ?? $m[1];
        $lastComma = mb_strrpos($digits, ',');
        $lastDot = mb_strrpos($digits, '.');

        if ($lastComma !== false && $lastComma > (int) $lastDot) {
            $decimals = mb_strlen($digits) - $lastComma - 1;
            $digits = $decimals > 0 && $decimals <= 2
                ? str_replace(['.', ','], ['', '.'], $digits)
                : preg_replace('/[.,]/', '', $digits) ?? $digits;
        } else {
            $digits = str_replace(',', '', $digits);
        }

        $value = preg_replace('/[^\d.]/', '', $digits);

        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    public static function year(?string $raw): ?int
    {
        if (blank($raw)) {
            return null;
        }

        if (! preg_match('/\b(19[5-9]\d|20[0-4]\d)\b/u', $raw, $m)) {
            return null;
        }

        $year = (int) $m[1];

        return ($year >= 1950 && $year <= (int) date('Y') + 2) ? $year : null;
    }

    public static function mileage(?string $raw): ?int
    {
        if (blank($raw)) {
            return null;
        }

        if (preg_match('/([\d][\d\s.,]{0,12})\s*(km|kilom)/iu', $raw, $m)
            || preg_match('/kilom[èe]trage\D{0,15}([\d][\d\s.,]{0,12})/iu', $raw, $m)) {
            $value = preg_replace('/[^\d]/', '', $m[1]);

            return is_numeric($value) ? (int) $value : null;
        }

        return null;
    }

    public static function fuel(?string $raw): ?FuelType
    {
        if (blank($raw)) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/diesel|gasoil|gas oil/iu', $raw) => FuelType::Diesel,
            (bool) preg_match('/hybride|hybrid/iu', $raw) => FuelType::Hybrid,
            (bool) preg_match('/[ée]lectri(que|c)|electric/iu', $raw) => FuelType::Electric,
            (bool) preg_match('/gpl|lpg/iu', $raw) => FuelType::Lpg,
            (bool) preg_match('/gnv|cng/iu', $raw) => FuelType::Cng,
            (bool) preg_match('/essence|petrol|gasoline/iu', $raw) => FuelType::Petrol,
            default => null,
        };
    }

    public static function transmission(?string $raw): ?Transmission
    {
        if (blank($raw)) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/automati(que|c)/iu', $raw) => Transmission::Automatic,
            (bool) preg_match('/s[ée]mi[ -]?automati/iu', $raw) => Transmission::SemiAutomatic,
            (bool) preg_match('/cvt/iu', $raw) => Transmission::Cvt,
            (bool) preg_match('/manuelle|manual/iu', $raw) => Transmission::Manual,
            default => null,
        };
    }

    public static function bodyType(?string $raw): ?BodyType
    {
        if (blank($raw)) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/suv|4x4|crossover/iu', $raw) => BodyType::Suv,
            (bool) preg_match('/pick[\s-]?up|utilitaire/iu', $raw) => BodyType::Pickup,
            (bool) preg_match('/break|wagon/iu', $raw) => BodyType::StationWagon,
            (bool) preg_match('/hatchback|petite berline/iu', $raw) => BodyType::Hatchback,
            (bool) preg_match('/cabriolet|convertible/iu', $raw) => BodyType::Cabriolet,
            (bool) preg_match('/coup[ée]/iu', $raw) => BodyType::Coupe,
            (bool) preg_match('/fourgon|van|minivan/iu', $raw) => BodyType::Van,
            (bool) preg_match('/berline|sedan/iu', $raw) => BodyType::Sedan,
            default => null,
        };
    }

    public const BRANDS = [
        'Toyota', 'Renault', 'Peugeot', 'Citroen', 'Citroën', 'Mercedes', 'BMW', 'Audi',
        'Volkswagen', 'Hyundai', 'Kia', 'Nissan', 'Ford', 'Dacia', 'Fiat', 'Opel', 'Porsche',
        'Range Rover', 'Land Rover', 'Mitsubishi', 'Mazda', 'Suzuki', 'Lexus', 'Volvo',
        'Seat', 'Skoda', 'Škoda', 'Chevrolet', 'Jeep', 'Daihatsu', 'SsangYong', 'Isuzu',
    ];

    public static function brand(?string $raw): ?string
    {
        if (blank($raw)) {
            return null;
        }

        foreach (self::BRANDS as $brand) {
            if (preg_match('/\b'.preg_quote($brand, '/').'\b/iu', $raw)) {
                return $brand;
            }
        }

        return null;
    }

    public static function model(string $title, ?string $brand): ?string
    {
        $title = trim(preg_replace('/\s*[|\-–—]\s*[^|\-–—]*$/u', '', $title) ?? $title);
        if ($brand) {
            $title = trim(preg_replace('/^'.preg_quote($brand, '/').'\s*/iu', '', $title) ?? $title);
        }

        $words = preg_split('/\s+/u', $title) ?: [];
        $words = array_slice($words, 0, 4);

        return trim(implode(' ', $words)) ?: null;
    }

    public static function location(?string $raw): ?string
    {
        if (blank($raw)) {
            return null;
        }

        $cities = [
            'Dakar', 'Thiès', 'Thies', 'Saint-Louis', 'Kaolack', 'Ziguinchor', 'Tambacounda',
            'Touba', 'Mbour', 'Diourbel', 'Louga', 'Kolda', 'Matam', 'Fatick', 'Kaffrine',
            'Sedhiou', 'Richard-Toll', 'Dakar Plateau',
        ];

        foreach ($cities as $city) {
            if (mb_stripos($raw, $city) !== false) {
                return $city;
            }
        }

        return null;
    }

    /** Canonical key used to detect the same listing behind different URLs. */
    public static function sourceKey(string $url): string
    {
        $parsed = parse_url(trim($url));
        if (! $parsed || ! isset($parsed['host'])) {
            return mb_strtolower(trim($url));
        }

        $path = rtrim($parsed['path'] ?? '', '/');

        return mb_strtolower(($parsed['host']).($parsed['port'] ?? '' ? ':'.$parsed['port'] : '').$path);
    }

    public static function host(string $url): ?string
    {
        return parse_url(trim($url), PHP_URL_HOST) ?: null;
    }

    /** Removes script/style noise and returns the readable text of a page. */
    public static function visibleText(Crawler $crawler): string
    {
        $clone = clone $crawler;
        $clone->filter('script, style, noscript, svg')->each(fn ($node) => $node->remove());

        return mb_substr(trim(preg_replace('/\s+/u', ' ', $clone->text('')) ?? ''), 0, 6000);
    }
}
