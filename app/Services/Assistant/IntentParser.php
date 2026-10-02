<?php

namespace App\Services\Assistant;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Turns a French sentence into catalogue criteria without calling any API.
 *
 * This is what keeps the widget useful with no key configured, and what makes
 * the assistant testable: the same parser runs in CI.
 */
class IntentParser
{
    /** Words that mean "the visitor did not ask for anything". */
    private const SMALL_TALK = [
        'bonjour', 'bonsoir', 'salut', 'coucou', 'hello', 'bjr', 'slt',
        'merci', 'ok', 'd accord', 'au revoir', 'bonne journee', 'c est quoi',
    ];

    /**
     * Body type aliases, including the spellings visitors actually type.
     *
     * @var array<string, string>
     */
    private const BODY_ALIASES = [
        'suv' => 'SUV', '4x4' => 'SUV', 'tout terrain' => 'SUV', 'todoterrain' => 'SUV',
        'berline' => 'SEDAN', 'sedan' => 'SEDAN', 'berline 3 volumes' => 'SEDAN',
        'citadine' => 'HATCHBACK', 'petite' => 'HATCHBACK', 'hatchback' => 'HATCHBACK',
        'compacte' => 'HATCHBACK', 'polaire' => 'HATCHBACK',
        'break' => 'WAGON', 'wagon' => 'WAGON', 'familiale' => 'WAGON',
        'utilitaire' => 'VAN', 'fourgon' => 'VAN', 'camionnette' => 'VAN',
        'pick up' => 'PICKUP', 'pickup' => 'PICKUP', 'benne' => 'PICKUP',
        'coupe' => 'COUPE', 'sportive' => 'COUPE', 'roadster' => 'CABRIOLET',
        'decapotable' => 'CABRIOLET', 'cabriolet' => 'CABRIOLET',
        'monospace' => 'MPV', 'monos pace' => 'MPV', 'people carrier' => 'MPV',
        'berline 5 portes' => 'HATCHBACK', '5 portes' => 'HATCHBACK',
    ];

    /** @var array<string, string> */
    private const FUEL_ALIASES = [
        'essence' => 'PETROL', 'petrol' => 'PETROL', 'sans plomb' => 'PETROL',
        'diesel' => 'DIESEL', 'gasoil' => 'DIESEL', 'gazole' => 'DIESEL',
        'hybride' => 'HYBRID', 'electrique' => 'ELECTRIC', 'elec' => 'ELECTRIC',
    ];

    /** @var array<string, string> */
    private const TRANSMISSION_ALIASES = [
        'automatique' => 'AUTOMATIC', 'auto' => 'AUTOMATIC', 'bva' => 'AUTOMATIC',
        'boite automatique' => 'AUTOMATIC',
        'manuelle' => 'MANUAL', 'manual' => 'MANUAL', 'bvm' => 'MANUAL',
        'boite manuelle' => 'MANUAL',
    ];

    /** @var array<int, string> */
    private const CITIES = ['dakar', 'thies', 'saint louis', 'kaolack', 'ziguinchor', 'touba', 'louga'];

    /**
     * French function words. Without this list the model extractor reads
     * "montrez-moi les Toyota" as a model called "Montrez".
     *
     * @var array<int, string>
     */
    private const STOP_WORDS = [
        'vehicule', 'vehicules', 'voiture', 'voitures', 'annonce', 'annonces', 'prix',
        'budget', 'maison', 'salut', 'bonjour', 'bonsoir', 'merci', 'coucou',
        'montrez', 'montre', 'moi', 'les', 'des', 'une', 'un', 'le', 'la', 'les',
        'pour', 'avec', 'sans', 'dans', 'sur', 'cherche', 'cherche', 'trouve',
        'trouver', 'voudrais', 'voudrais', 'je', 'tu', 'il', 'elle', 'nous', 'vous',
        'il', 'y', 'a', 'au', 'aux', 'est', 'sont', 'etre', 'et', 'ou', 'quel',
        'quelle', 'quels', 'quelles', 'combien', 'parle', 'parlez', 'dis', 'dites',
        'besoin', 'besoins', 'facon', 'moyen', 'moyenne', 'autre', 'autres',
        'disponible', 'disponibles', 'nouveau', 'nouvelle', 'occasion', 'mecanique',
        'climatise', 'climatisee', 'premiere', 'main', 'seule', 'aussi', 'tout',
        'tous', 'toute', 'toutes', 'vers', 'par', 'chez', 'faire', 'besoin',
    ];

    /** Phrases that mark an upper bound. @var array<int, string> */
    private const MAX_MARKERS = [
        'sous', 'moins de', 'pas plus de', 'pas plus', 'maximum', 'maximale',
        'maxi', 'max', 'inferieur a', 'au plus', 'jusqu', 'budget', 'prix',
    ];

    /** Phrases that mark a lower bound. @var array<int, string> */
    private const MIN_MARKERS = [
        'a partir de', 'a partir', 'au moins', 'minimum', 'plus de',
        'superieur a', 'au dela de', 'a l\'au dela',
    ];

    /**
     * @return array{
     *   intent: string, criteria: array<string, mixed>, brands: array<int, string>,
     *   confidence: float, wantsAlert: bool, isSmallTalk: bool
     * }
     */
    public function parse(string $message, ?Collection $knownBrands = null): array
    {
        $text = mb_strtolower(trim($message));

        $brand = $this->brand($text, $knownBrands);
        $prices = $this->prices($this->withoutMileage($text));

        $criteria = [
            'brand' => $brand,
            'model' => $this->model($text, $brand),
            'max_price' => $prices['max_price'],
            'min_price' => $prices['min_price'],
            'min_year' => $this->year($text),
            'max_mileage' => $this->mileage($text),
            'body_type' => $this->enumValue($text, self::BODY_ALIASES, BodyType::options()),
            'fuel' => $this->enumValue($text, self::FUEL_ALIASES, FuelType::options()),
            'transmission' => $this->enumValue($text, self::TRANSMISSION_ALIASES, Transmission::options()),
            'location' => $this->city($text),
        ];

        $criteria = array_filter($criteria, static fn ($value) => $value !== null);

        $isQuestion = (bool) preg_match('/(\?|combien|est ce que|est-ce que|quel|quelle|dis moi)/u', $text);
        $wantsAlert = (bool) preg_match('/(alerte|alerter|previent|prevoyez|surveill|des que|quand il y a)/u', $text);

        return [
            'intent' => $this->intent($text, $criteria, $isQuestion, $wantsAlert),
            'criteria' => $criteria,
            'brands' => isset($criteria['brand']) ? [$criteria['brand']] : [],
            'confidence' => $this->confidence($criteria),
            'wantsAlert' => $wantsAlert,
            'isSmallTalk' => $this->isSmallTalk($text, $criteria),
        ];
    }

    private function intent(string $text, array $criteria, bool $isQuestion, bool $wantsAlert): string
    {
        if (preg_match('/(bonjour|salut|coucou|hello)/u', $text) && count($criteria) === 0) {
            return 'greeting';
        }

        if (preg_match('/(aide|comment ca marche|tu fais quoi|que sais tu)/u', $text)) {
            return 'capabilities';
        }

        if ($criteria !== []) {
            // "Cree-moi une alerte pour un Toyota sous 10 millions" is a search
            // first: the criteria have to exist before an alert can hold them.
            return ($isQuestion && ! $wantsAlert) ? 'question_about_catalogue' : 'search';
        }

        return 'unknown';
    }

    private function isSmallTalk(string $text, array $criteria): bool
    {
        if ($criteria !== []) {
            return false;
        }

        foreach (self::SMALL_TALK as $word) {
            if (Str::contains($text, $word)) {
                return true;
            }
        }

        return mb_strlen($text) < 3;
    }

    private function confidence(array $criteria): float
    {
        if ($criteria === []) {
            return 0.0;
        }

        // A brand plus a budget is a search the assistant can act on.
        return min(1.0, 0.4 + 0.2 * count($criteria));
    }

    /** Matches against the brands actually stocked, never a hardcoded list. */
    private function brand(string $text, ?Collection $knownBrands): ?string
    {
        $brands = $knownBrands ?? Vehicle::published()->distinct()->pluck('brand');

        foreach ($brands as $brand) {
            if ($brand && Str::contains($text, mb_strtolower($brand))) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * Reads the model out of a sentence, preferring what the catalogue actually
     * stocks so a French function word can never be mistaken for a model.
     */
    private function model(string $text, ?string $brand): ?string
    {
        $text = $this->withoutNumbers($text);
        $words = $this->words($text);

        foreach ($this->knownModels() as $model) {
            if (Str::contains($text, mb_strtolower($model))) {
                return $model;
            }
        }

        foreach ($words as $word) {
            if (mb_strlen(preg_replace('/\d/', '', $word)) < 3) {
                continue;
            }

            if (in_array($word, self::STOP_WORDS, true)) {
                continue;
            }

            if ($brand !== null && $word === mb_strtolower($brand)) {
                continue;
            }

            // "RAV4", "308", "series 3": letters and digits glued together.
            if (preg_match('/^[a-z]{2,}[0-9]/u', $word)) {
                return Str::title($word);
            }
        }

        return null;
    }

    /**
     * Understands "10 millions", "10M", "10 000 000", "entre 5 et 10 millions"
     * and keeps the direction of the bound: "sous 15" and "a partir de 15"
     * must not produce the same search.
     *
     * @return array{min_price: ?int, max_price: ?int}
     */
    private function prices(string $text): array
    {
        $bounds = ['min_price' => null, 'max_price' => null];

        // "entre 5 et 10 millions"
        if (preg_match('/entre\s+(\d[\d\s.,]*)\s*(milliards?|millions?|m\b|k\b)?\s*(?:et|a)\s+(\d[\d\s.,]*)\s*(milliards?|millions?|m\b|k\b)?/u', $text, $m)) {
            // "entre 5 et 10 millions" only qualifies one figure; the other
            // inherits the unit rather than reading as five francs.
            $unit = $this->unit($m[2] ?? '', $m[4] ?? '');

            $bounds['min_price'] = $this->toAmount($m[1], $unit);
            $bounds['max_price'] = $this->toAmount($m[3], $unit);

            return $bounds;
        }

        // Grouped digits come first: "10 000 000" must win over the "10" it starts with.
        $pattern = '/(\d{1,3}(?:[ \x{a0}]\d{3}){1,3}|\d+(?:[.,]\d+)?)\s*(milliards?|millions?|m\b|k\b|fcfa|frs|cfa|xof)?/u';

        if (! preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return $bounds;
        }

        $candidates = [];

        foreach ($matches as $match) {
            $unit = $match[2][0] ?? '';
            $amount = $this->toAmount($match[1][0], $unit);

            if ($amount === null) {
                continue;
            }

            // A bare four-digit 19xx/20xx is a year, not a price.
            if ($unit === '' && $amount >= 1900 && $amount <= 2100) {
                continue;
            }

            // "RAV4" contributes a 4; without a unit that is never a budget.
            if ($unit === '' && $amount < 1_000) {
                continue;
            }

            $before = mb_substr(substr($text, 0, $match[0][1]), -24);

            $candidates[] = [
                'amount' => $amount,
                'min' => $this->direction($before) === 'min',
                // An explicit unit outranks a bare figure: "4x4 essence 1,5 million"
                // is a budget, not a four-franc car.
                'weight' => $unit !== '' ? 1 : 0,
            ];
        }

        usort($candidates, fn (array $a, array $b) => $b['weight'] <=> $a['weight']);

        foreach ($candidates as $candidate) {
            $key = $candidate['min'] ? 'min_price' : 'max_price';

            if ($bounds[$key] === null) {
                $bounds[$key] = $candidate['amount'];
            }
        }

        return $bounds;
    }

    /**
     * Decides which end of the range a figure closes. "Pas plus de 9" is a
     * ceiling even though "plus de" is also a lower-bound phrase.
     */
    private function direction(string $before): ?string
    {
        $window = mb_substr($before, -24);

        foreach (self::MAX_MARKERS as $marker) {
            if (Str::contains($window, $marker)) {
                return 'max';
            }
        }

        // "pas plus de" already returned above; drop the negation so the
        // remaining "plus de" cannot flip the meaning.
        $window = str_replace('pas ', '', $window);

        foreach (self::MIN_MARKERS as $marker) {
            if (Str::contains($window, $marker)) {
                return 'min';
            }
        }

        return null;
    }

    /** Whichever of the two units the sentence actually spelled out. */
    private function unit(string $first, string $second): string
    {
        $first = trim($first);
        $second = trim($second);

        return $first !== '' ? $first : $second;
    }

    /** Converts "1,5 millions" or "10 000 000" to a plain integer. */
    private function toAmount(string $figures, string $unit): ?int
    {
        $unit = mb_strtolower(trim($unit));
        $figures = trim(str_replace(["\u{00a0}", ' ', ','], ['', '', '.'], $figures));

        if ($figures === '' || ! is_numeric($figures)) {
            return null;
        }

        $value = (float) $figures;

        return match (true) {
            str_starts_with($unit, 'milliard') => (int) round($value * 1_000_000_000),
            str_starts_with($unit, 'million'), $unit === 'm' => (int) round($value * 1_000_000),
            $unit === 'k' => (int) round($value * 1_000),
            default => (int) round($value),
        };
    }

    /**
     * Blanks out every figure so the model extractor never reads "sous 15"
     * as a car called "Sous 15".
     */
    private function withoutNumbers(string $text): string
    {
        return (string) preg_replace('/\d[\d\s.,]*/u', ' ', $text);
    }

    /** @return array<int, string> */
    private function words(string $text): array
    {
        preg_match_all('/[\p{L}]+[\p{N}-]*|[\p{N}]+[\p{L}-]*/u', $text, $matches);

        return array_values(array_filter(array_map(
            fn (string $word) => mb_strtolower($word),
            $matches[0]
        )));
    }

    /** @return array<int, string> */
    private function knownModels(): array
    {
        return Vehicle::published()
            ->distinct()
            ->orderBy('model')
            ->pluck('model')
            ->filter()
            ->all();
    }

    private function year(string $text): ?int
    {
        if (preg_match('/(?:a partir de|depuis|apres|minimum)\D{0,10}((?:19|20)\d{2})/u', $text, $m)) {
            return (int) $m[1];
        }

        // Bare four-digit years in the 1995..current+2 window are model years.
        preg_match_all('/\b(19[9]\d|20[0-4]\d)\b/u', $text, $matches);

        $current = (int) date('Y') + 2;

        foreach ($matches[1] as $year) {
            if ((int) $year >= 1995 && (int) $year <= $current) {
                return (int) $year;
            }
        }

        return null;
    }

    private function mileage(string $text): ?int
    {
        if (preg_match('/(\d[\d\s]{3,})\s*(km|kilometre|kilometres)\b/u', $text, $m)) {
            return (int) preg_replace('/\D/', '', $m[1]);
        }

        if (preg_match('/(?:moins de|sous|max|maximum|maxi)\D{0,10}(\d{2,3})\s*mille?\s*km/u', $text, $m)) {
            return (int) $m[1] * 1_000;
        }

        return null;
    }

    /**
     * Removes "60 000 km" before prices are read, otherwise a kilometre reading
     * would be mistaken for a budget.
     */
    private function withoutMileage(string $text): string
    {
        return (string) preg_replace('/\d[\d\s]*\s*(km|kilometre|kilometres)\b/u', ' ', $text);
    }

    private function city(string $text): ?string
    {
        foreach (self::CITIES as $city) {
            if (Str::contains($text, $city)) {
                return Str::title($city);
            }
        }

        return null;
    }

    /**
     * Only accepts an alias whose canonical value really exists in the enum,
     * so a typo cannot silently produce an unfilterable search.
     *
     * @param  array<string, string>  $aliases
     * @param  array<string, string>  $valid
     */
    private function enumValue(string $text, array $aliases, array $valid): ?string
    {
        foreach ($aliases as $needle => $canonical) {
            if (Str::contains($text, $needle) && array_key_exists($canonical, $valid)) {
                return $canonical;
            }
        }

        return null;
    }
}
