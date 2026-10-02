<?php

namespace App\Services;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use App\Models\Source;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Import pipeline:
 *   URL -> fetch (SSRF guarded) -> extraction -> normalisation -> preview -> admin correction -> publication
 *
 * The whole app works without this service: manual entry is always available.
 * Automatic extraction respects robots-friendly behaviour, host allow-listing,
 * payload size limits and the terms of the targeted site.
 */
class VehicleImportService
{
    private const USER_AGENT = 'AutoAlertBot/1.0 (+https://autoalert.dev/bot)';

    private bool $enabled;

    private int $timeoutMs;

    private int $maxBytes;

    private array $allowedHosts;

    public function __construct(
        ?bool $enabled = null,
        ?int $timeoutMs = null,
        ?int $maxBytes = null,
        ?array $allowedHosts = null,
    ) {
        $this->enabled = $enabled ?? (bool) config('import.enabled', true);
        $this->timeoutMs = $timeoutMs ?? (int) config('import.timeout_ms', 12000);
        $this->maxBytes = $maxBytes ?? (int) config('import.max_bytes', 3_000_000);
        $this->allowedHosts = $allowedHosts ?? array_filter(explode(',', (string) config('import.allowed_hosts', '')));
    }

    /**
     * @return array{
     *   source_url: string, host: ?string, extraction: string, fields: array<string, mixed>,
     *   warnings: array<int, string>, duplicates: Collection, robots_allowed: bool
     * }
     */
    public function preview(string $url): array
    {
        if (! $this->enabled) {
            throw new \RuntimeException(
                "L'import automatique est desactive sur cette installation. La saisie manuelle reste disponible."
            );
        }

        $this->assertSafeUrl($url);

        $robotsAllowed = $this->robotsAllows($url);
        if (! $robotsAllowed) {
            throw new \RuntimeException(
                "L'import de $url est bloque : le fichier robots.txt de ce site l'interdit ou l'extraction automatique est desactive."
            );
        }

        $html = $this->fetchHtml($url);
        $fields = $this->extract($html, $url);
        $warnings = $this->warningsFor($fields);

        $found = count(array_filter($fields, fn ($value, $key) => $key !== 'images' && ! empty($value), ARRAY_FILTER_USE_BOTH));

        return [
            'source_url' => $url,
            'host' => ListingNormalizer::host($url),
            'extraction' => $found >= 5 ? 'AUTO' : ($found >= 2 ? 'PARTIAL' : 'NONE'),
            'fields' => $fields,
            'warnings' => $warnings,
            'duplicates' => $this->findDuplicates($url, $fields),
            'robots_allowed' => true,
        ];
    }

    /**
     * Extracts the listing fields from the HTML page.
     *
     * @return array<string, mixed>
     */
    public function extract(string $html, string $url): array
    {
        $crawler = new Crawler($html, $url);

        $jsonLd = $this->readJsonLd($crawler);
        $microData = $this->readMicroData($crawler);

        $title = $this->meta($crawler, 'property', 'og:title')
            ?? trim($crawler->filter('title')->first()->text(''))
            ?: $url;

        $description = $this->meta($crawler, 'name', 'description')
            ?? $this->meta($crawler, 'property', 'og:description');

        $text = ListingNormalizer::visibleText($crawler);
        $haystack = implode(' ', array_filter([$title, $description, $text]));

        $brand = $jsonLd['brand'] ?? $microData['brand'] ?? ListingNormalizer::brand($title);
        $fields = [
            'brand' => $brand,
            'model' => $jsonLd['model'] ?? $microData['model'] ?? ListingNormalizer::model($title, $brand),
            'year' => $jsonLd['year'] ?? $microData['year'] ?? ListingNormalizer::year($haystack),
            'price' => $jsonLd['price'] ?? $microData['price'] ?? ListingNormalizer::price($haystack),
            'mileage' => $jsonLd['mileage'] ?? $microData['mileage'] ?? ListingNormalizer::mileage($haystack),
            'fuel' => $jsonLd['fuel'] ?? $microData['fuel'] ?? ListingNormalizer::fuel($haystack),
            'transmission' => $jsonLd['transmission'] ?? $microData['transmission'] ?? ListingNormalizer::transmission($haystack),
            'body_type' => $jsonLd['body_type'] ?? $microData['body_type'] ?? ListingNormalizer::bodyType($title.' '.$haystack),
            'color' => ListingNormalizer::brand($haystack) === null ? null : $this->guessColor($haystack),
            'location' => $jsonLd['location'] ?? $microData['location'] ?? ListingNormalizer::location($haystack),
            'description' => $description ? mb_substr($description, 0, 6000) : null,
            'images' => $this->readImages($crawler, $jsonLd['images'] ?? []),
        ];

        return array_filter($fields, fn ($value, $key) => $key === 'images' || ($value !== null && $value !== ''), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Listings that look like the same vehicle: same canonical URL, or same
     * brand + year (+ model or price).
     *
     * @param  array<string, mixed>  $fields
     * @return Collection<int, Vehicle>
     */
    public function findDuplicates(string $url, array $fields = []): Collection
    {
        $key = ListingNormalizer::sourceKey($url);

        $byUrl = Vehicle::query()
            ->whereNotNull('source_url')
            ->get()
            ->filter(fn (Vehicle $v) => ListingNormalizer::sourceKey((string) $v->source_url) === $key)
            ->values();

        $conditions = [];
        if (! empty($fields['brand']) && ! empty($fields['year'])) {
            $conditions[] = ['brand' => $fields['brand'], 'year' => $fields['year']];
        }
        if (! empty($fields['brand']) && ! empty($fields['price'])) {
            $conditions[] = ['brand' => $fields['brand'], 'price' => $fields['price']];
        }

        $byCharacteristics = collect();
        foreach ($conditions as $condition) {
            $byCharacteristics = $byCharacteristics->merge(
                Vehicle::query()
                    ->where('brand', 'like', $condition['brand'])
                    ->where('year', $condition['year'] ?? 0)
                    ->when(! isset($condition['year']), fn ($q) => $q->where('price', $condition['price']))
                    ->limit(10)
                    ->get()
            );
        }

        return $byUrl->merge($byCharacteristics)->unique('id')->values();
    }

    /** Creates the vehicle from a preview, applying admin corrections. */
    public function createFromPreview(array $input): array
    {
        $url = $input['url'];
        $preview = $this->preview($url);
        $fields = array_merge($preview['fields'], array_filter($input['overrides'] ?? [], fn ($v) => $v !== null && $v !== ''));

        if (blank($fields['brand'] ?? null) || blank($fields['model'] ?? null)) {
            throw new \RuntimeException(
                "Marque et modele sont obligatoires : l'extraction automatique n'a pas suffi, completer le formulaire manuellement."
            );
        }

        $source = $input['sourceId'] ?? null;
        if (! $source) {
            $source = $this->resolveSource($preview['host']);
        }

        $status = ($input['publish'] ?? false) ? VehicleStatus::Published : VehicleStatus::Draft;

        $vehicle = Vehicle::create([
            'source_id' => $source,
            'source_url' => $url,
            'brand' => $fields['brand'],
            'model' => $fields['model'],
            'year' => $fields['year'] ?? (int) date('Y'),
            'price' => $fields['price'] ?? 0,
            'mileage' => $fields['mileage'] ?? 0,
            'fuel' => $fields['fuel'] ?? FuelType::Petrol,
            'transmission' => $fields['transmission'] ?? Transmission::Manual,
            'body_type' => $fields['body_type'] ?? BodyType::Sedan,
            'color' => $fields['color'] ?? null,
            'location' => $fields['location'] ?? null,
            'description' => $fields['description'] ?? null,
            'status' => $status,
            'published_at' => $status === VehicleStatus::Published ? now() : null,
            'reference' => $this->nextReference(),
        ]);

        foreach (array_slice($fields['images'] ?? [], 0, 8) as $imageUrl) {
            $vehicle->images()->create([
                'url' => $imageUrl,
                'provider' => 'external',
                'sort_order' => $vehicle->images()->count(),
                'alt' => $vehicle->title(),
            ]);
        }

        $warnings = $preview['warnings'];
        if ($preview['extraction'] === 'NONE') {
            $warnings[] = 'Aucune donnee exploitable : la fiche a ete creee en brouillon.';
        }

        return [
            'vehicle' => $vehicle->fresh(['images', 'source']),
            'duplicates' => $preview['duplicates'],
            'warnings' => $warnings,
            'extraction' => $preview['extraction'],
        ];
    }

    public function resolveSource(?string $host): ?int
    {
        if (blank($host)) {
            return null;
        }

        return Source::query()
            ->active()
            ->where('base_url', 'like', "%{$host}%")
            ->value('id');
    }

    public function nextReference(): string
    {
        $last = Vehicle::query()->orderByDesc('id')->value('reference');

        return str_pad((string) (((int) $last) + 1), 5, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------ guards

    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url(trim($url));
        if (! $parts || ! isset($parts['host'], $parts['scheme'])) {
            throw new \InvalidArgumentException('URL invalide.');
        }
        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Seuls les liens http et https sont acceptes.');
        }

        $host = mb_strtolower($parts['host']);
        if ($this->allowedHosts && ! collect($this->allowedHosts)->contains(fn ($h) => str_ends_with($host, mb_strtolower(trim($h))))) {
            throw new \InvalidArgumentException("Host non autorise : {$host}");
        }
        if ($this->isPrivateHost($host)) {
            throw new \InvalidArgumentException('Les adresses internes ne sont pas accessibles.');
        }
    }

    private function isPrivateHost(string $host): bool
    {
        $isPublicIp = fn (?string $ip): bool => $ip !== false && $ip !== null
            && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return ! $isPublicIp($host);
        }

        $resolved = gethostbyname($host);
        if ($resolved === $host) {
            return false; // Unresolvable host: rejected later by the HTTP client.
        }

        return ! $isPublicIp($resolved);
    }

    private function robotsAllows(string $url): bool
    {
        $parts = parse_url($url);
        $robots = sprintf('%s://%s/robots.txt', $parts['scheme'], $parts['host']);

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(min(5, intdiv($this->timeoutMs, 1000)))
                ->get($robots);

            if (! $response->successful()) {
                return true; // No robots.txt served: nothing to forbid.
            }

            $rules = $response->body();
            $path = $parts['path'] ?? '/';
            $disallow = [];

            foreach (preg_split('/\r?\n/', $rules) ?: [] as $line) {
                if (preg_match('/^\s*(user-agent|disallow|allow)\s*:\s*(.*)$/i', $line, $m)) {
                    if (strtolower($m[1]) === 'disallow' && trim($m[2]) !== '') {
                        $disallow[] = trim($m[2]);
                    }
                }
            }

            foreach ($disallow as $rule) {
                if ($rule === '/' || str_starts_with($path, $rule)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable) {
            return true; // Network hiccup on robots.txt should not block the feature.
        }
    }

    private function fetchHtml(string $url): string
    {
        $response = Http::withHeaders([
            'User-Agent' => self::USER_AGENT,
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'fr,en;q=0.8',
        ])
            ->timeout(max(3, intdiv($this->timeoutMs, 1000)))
            ->withOptions(['allow_redirects' => ['max' => 5]])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("La source a repondu avec le statut {$response->status()}.");
        }

        $contentType = $response->header('Content-Type');
        if ($contentType && ! str_contains($contentType, 'text/html')) {
            throw new \RuntimeException("La source ne renvoie pas une page HTML exploitable ({$contentType}).");
        }

        return mb_substr($response->body(), 0, $this->maxBytes);
    }

    // ----------------------------------------------------------------- parsing

    private function meta(Crawler $crawler, string $attribute, string $value): ?string
    {
        $node = $crawler->filter("meta[{$attribute}='{$value}']")->first();
        $content = $node->count() ? $node->attr('content') : null;

        return $content ? trim($content) : null;
    }

    /** @return array<string, mixed> */
    private function readJsonLd(Crawler $crawler): array
    {
        $found = [];

        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $script) use (&$found) {
            if (! empty($found)) {
                return;
            }

            $data = json_decode($script->text(''), true);
            $candidates = is_array($data)
                ? array_merge([$data], $data['@graph'] ?? [])
                : [];

            foreach ($candidates as $candidate) {
                $type = is_array($candidate) ? ($candidate['@type'] ?? '') : '';
                $typeString = is_array($type) ? implode(' ', $type) : (string) $type;

                if (! preg_match('/vehicle|car|product|motorizedvehicle/i', $typeString)) {
                    continue;
                }

                $offers = $candidate['offers'] ?? [];
                $offers = is_array($offers) && array_is_list($offers) ? ($offers[0] ?? []) : $offers;
                $brand = $candidate['brand'] ?? $candidate['manufacturer'] ?? null;
                $brand = is_array($brand) ? ($brand['name'] ?? null) : $brand;

                $found = array_filter([
                    'brand' => is_string($brand) ? $brand : null,
                    'model' => isset($candidate['model']) && is_string($candidate['model']) ? $candidate['model'] : null,
                    'year' => ListingNormalizer::year((string) ($candidate['vehicleIdentificationDate'] ?? $candidate['productionDate'] ?? $candidate['releaseDate'] ?? '')),
                    'price' => ListingNormalizer::price((string) ($offers['price'] ?? $candidate['price'] ?? '')),
                    'mileage' => ListingNormalizer::mileage((string) ($candidate['mileageFromOdometer'] ?? $candidate['vehicleMileage'] ?? '')),
                    'fuel' => ListingNormalizer::fuel((string) ($candidate['fuelType'] ?? '')),
                    'transmission' => ListingNormalizer::transmission((string) ($candidate['vehicleTransmission'] ?? '')),
                    'location' => is_string($candidate['vehicleLocation'] ?? null) ? $candidate['vehicleLocation'] : null,
                    'images' => $this->flattenImages($candidate['image'] ?? []),
                ], fn ($v) => $v !== null && $v !== '' && $v !== []);
            }
        });

        return $found;
    }

    /** @return array<string, mixed> */
    private function readMicroData(Crawler $crawler): array
    {
        $scope = $crawler->filter('[itemtype*="Vehicle"], [itemtype*="Car"], [itemtype*="Product"]')->first();
        if (! $scope->count()) {
            return [];
        }

        $read = function (string $property) use ($scope): ?string {
            $node = $scope->filter("[itemprop='{$property}']")->first();
            if (! $node->count()) {
                return null;
            }

            $content = $node->attr('content') ?: trim($node->text(''));

            return $content !== '' ? $content : null;
        };

        return array_filter([
            'brand' => $read('brand'),
            'model' => $read('model'),
            'year' => ListingNormalizer::year((string) ($read('productionDate') ?? $read('vehicleModelDate') ?? $read('year') ?? '')),
            'price' => ListingNormalizer::price((string) $read('price')),
            'mileage' => ListingNormalizer::mileage((string) $read('mileageFromOdometer')),
            'fuel' => ListingNormalizer::fuel((string) $read('fuelType')),
            'transmission' => ListingNormalizer::transmission((string) $read('vehicleTransmission')),
            'body_type' => ListingNormalizer::bodyType((string) ($read('vehicleBodyType') ?? $read('bodyType'))),
            'location' => $read('vehicleLocation'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** @return array<int, string> */
    private function flattenImages(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }
        if (is_array($value)) {
            $out = [];
            array_walk_recursive($value, function ($item) use (&$out) {
                if (is_string($item)) {
                    $out[] = $item;
                }
            });

            return $out;
        }

        return [];
    }

    /** @return array<int, string> */
    private function readImages(Crawler $crawler, array $jsonLdImages): array
    {
        $base = $crawler->getUri() ?: '';
        $images = array_values(array_filter($jsonLdImages));

        $ogImage = $this->meta($crawler, 'property', 'og:image');
        if ($ogImage) {
            $images[] = $ogImage;
        }

        $crawler->filter('img')->each(function (Crawler $img) use (&$images, $base) {
            if (count($images) >= 20) {
                return;
            }
            $src = $img->attr('data-src') ?: $img->attr('src');
            if (! $src || str_starts_with($src, 'data:')) {
                return;
            }
            $images[] = $base && str_starts_with($src, '/') ? rtrim(parse_url($base, PHP_URL_SCHEME).'://'.parse_url($base, PHP_URL_HOST), '/').$src : $src;
        });

        return array_slice(array_values(array_unique(array_filter(
            $images,
            fn ($src) => is_string($src) && preg_match('#^https?://#i', $src) === 1
        ))), 0, 12);
    }

    private function guessColor(string $haystack): ?string
    {
        $colors = ['blanc', 'noir', 'argent', 'gris', 'rouge', 'bleu', 'vert', 'jaune', 'orange', 'marron', 'beige', 'bronze', 'dore', 'violet'];
        foreach ($colors as $color) {
            if (preg_match('/\b'.$color.'\b/iu', $haystack)) {
                return ucfirst($color);
            }
        }

        return null;
    }

    /** @param array<string, mixed> $fields */
    private function warningsFor(array $fields): array
    {
        $warnings = [];
        foreach (['brand', 'model', 'year', 'price'] as $required) {
            if (empty($fields[$required])) {
                $warnings[] = "Champ manquant : {$required} (a completer manuellement)";
            }
        }
        if (empty($fields['mileage'])) {
            $warnings[] = 'Kilometrage non trouve';
        }
        if (($fields['images'] ?? []) === []) {
            $warnings[] = 'Aucune image trouvee : ajoutez-les manuellement';
        }

        return $warnings;
    }
}
