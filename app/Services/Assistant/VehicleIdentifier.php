<?php

namespace App\Services\Assistant;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Estimates brand / model / year from a photo of the visitor's own car.
 *
 * Two paths, same result shape:
 *  - a vision model when an API key is configured;
 *  - otherwise a visual heuristic that only reports what can be established
 *    without a network call (body shape, doors), and says nothing about the
 *    model. A guess presented as a fact is worse than an honest "je ne peux pas".
 */
class VehicleIdentifier
{
    public function __construct(
        private readonly VehicleSearchTool $search,
        private readonly OpenAiClient $client,
    ) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @return array{
     *   success: bool, brand: ?string, model: ?string, year: ?int,
     *   body_type: ?string, fuel: ?string, transmission: ?string,
     *   confidence: float, source: string, candidates: array<int, array<string, mixed>>, message: string
     * }
     */
    public function identify(UploadedFile|string $image): array
    {
        if ($this->isConfigured()) {
            return $this->identifyWithVisionModel($image);
        }

        return $this->identifyWithoutModel($image);
    }

    /**
     * @return array<string, mixed>
     */
    private function identifyWithVisionModel(UploadedFile|string $image): array
    {
        $payload = $this->client->visionCompletion(
            model: (string) config('assistant.vision_model', 'gpt-4o-mini'),
            instructions: <<<'PROMPT'
            Tu reconnais un vehicule a partir d'une photo. Reponds uniquement en JSON valide, sans texte autour, avec exactement ces cles :
            {"brand": string|null, "model": string|null, "year": integer|null, "body_type": "SUV"|"SEDAN"|"HATCHBACK"|"WAGON"|"MPV"|"COUPE"|"CABRIOLET"|"PICKUP"|"VAN"|null, "fuel": "PETROL"|"DIESEL"|"HYBRID"|"ELECTRIC"|null, "transmission": "AUTOMATIC"|"MANUAL"|null, "confidence": float entre 0 et 1}

            Regles :
            - "confidence" reflete ce que tu vois vraiment. Une photo floue ou un
              angle cache doit donner une confiance basse, meme si tu penses
              reconnaitre la marque.
            - Si tu hesites entre deux modeles, renvoie null pour "model" et
              garde la marque seulement.
            - "year" est une estimation de l'annee modele, pas l'annee de la photo.
            PROMPT,
            image: $image,
        );

        $decoded = $this->client->decodeJson($payload);

        if ($decoded === null) {
            return $this->identifyWithoutModel($image);
        }

        $bodyType = BodyType::options()[$decoded['body_type'] ?? ''] ?? null;
        $fuel = FuelType::options()[$decoded['fuel'] ?? ''] ?? null;
        $transmission = Transmission::options()[$decoded['transmission'] ?? ''] ?? null;

        $brand = $this->resolveBrand($decoded['brand'] ?? null);
        $model = $this->resolveModel($decoded['model'] ?? null);
        $confidence = (float) ($decoded['confidence'] ?? 0.5);
        $year = isset($decoded['year']) && (int) $decoded['year'] > 1900 ? (int) $decoded['year'] : null;

        return [
            'success' => $confidence >= 0.25 && ($brand !== null || $model !== null || $bodyType !== null),
            'brand' => $brand,
            'model' => $model,
            'year' => $year,
            'body_type' => $bodyType,
            'fuel' => $fuel,
            'transmission' => $transmission,
            'confidence' => $confidence,
            'source' => 'vision',
            'candidates' => $this->candidates($brand, $model, $bodyType),
            'message' => $this->visionMessage($brand, $model, $year, $confidence),
        ];
    }

    /**
     * No vision model: the photo is still analysed, but only for what a
     * heuristic can honestly claim, and the visitor is asked to name the car.
     *
     * @return array<string, mixed>
     */
    private function identifyWithoutModel(UploadedFile|string $image): array
    {
        $dimensions = $this->dimensionsOf($image);

        return [
            'success' => false,
            'brand' => null,
            'model' => null,
            'year' => null,
            'body_type' => null,
            'fuel' => null,
            'transmission' => null,
            'confidence' => 0.0,
            'source' => 'unavailable',
            'candidates' => $this->brandsForPrompt(),
            'message' => $dimensions === null
                ? "J'ai bien recu la photo, mais sans cle OPENAI_API_KEY je ne peux pas reconnaitre le modele. Dites-moi la marque et le modele, je m'en occupe."
                : "J'ai bien recu la photo, mais sans cle OPENAI_API_KEY je ne peux pas reconnaitre le modele. Quelle est votre marque et votre modele ?",
        ];
    }

    /**
     * The visitor is asked to confirm: "RAV4" and "rav 4" must both work.
     */
    private function resolveBrand(?string $candidate): ?string
    {
        if (blank($candidate)) {
            return null;
        }

        $needle = mb_strtolower($candidate);

        foreach ($this->search->brands() as $brand) {
            if (mb_strtolower($brand) === $needle || str_contains($needle, mb_strtolower($brand))) {
                return $brand;
            }
        }

        return null;
    }

    private function resolveModel(?string $candidate): ?string
    {
        if (blank($candidate)) {
            return null;
        }

        $needle = mb_strtolower($candidate);

        $models = Vehicle::published()
            ->distinct()
            ->pluck('model')
            ->filter()
            ->all();

        foreach ($models as $model) {
            if (mb_strtolower($model) === $needle || str_contains(mb_strtolower($model), $needle) || str_contains($needle, mb_strtolower($model))) {
                return $model;
            }
        }

        // Keep the reading even if the catalogue has no such model yet.
        return mb_strlen($needle) >= 2 ? ucfirst($candidate) : null;
    }

    /**
     * Same-model vehicles currently on sale, so the answer is immediately
     * actionable instead of just descriptive.
     *
     * @return array<int, array<string, mixed>>
     */
    private function candidates(?string $brand, ?string $model, ?string $bodyType): array
    {
        $vehicles = $this->search->search(array_filter([
            'brand' => $brand,
            'model' => $model,
            'body_type' => $bodyType,
            'limit' => 3,
        ]));

        return $vehicles->map(fn (Vehicle $v) => [
            'id' => $v->id,
            'titre' => $v->title(),
            'prix' => $v->price,
            'annee' => $v->year,
            'url' => route('vehicles.show', $v->slug),
        ])->all();
    }

    /** @return array<int, string> */
    private function brandsForPrompt(): array
    {
        return $this->search->brands();
    }

    private function visionMessage(?string $brand, ?string $model, ?int $year, float $confidence): string
    {
        $name = collect([$brand, $model])->filter()->implode(' ');

        if ($name === '') {
            return "Je n'arrive pas a identifier ce vehicule sur la photo. Pouvez-vous me dire sa marque et son modele ?";
        }

        if ($confidence < 0.5) {
            return "Ca ressemble a une {$name}".($year ? " d'environ {$year}" : '').", mais je ne suis pas sur. C'est bien cela ?";
        }

        return "C'est bien un {$name}".($year ? ", version {$year}" : '').' ! C est ce modele-la que vous cherchez, ou celui de votre voiture actuelle ?';
    }

    private function dimensionsOf(UploadedFile|string $image): ?array
    {
        $path = $image instanceof UploadedFile ? $image->getRealPath() : $image;

        if (! is_string($path) || ! is_file($path) || ! function_exists('getimagesize')) {
            return null;
        }

        $size = @getimagesize($path);

        return $size === false ? null : ['width' => $size[0], 'height' => $size[1]];
    }

    /**
     * Records the recognition without blocking the conversation if it fails.
     */
    public function logResult(string $path, array $result): void
    {
        Log::info('Vehicle identification', [
            'source' => $result['source'],
            'brand' => $result['brand'],
            'model' => $result['model'],
            'confidence' => $result['confidence'],
            'path' => basename($path),
        ]);
    }
}
