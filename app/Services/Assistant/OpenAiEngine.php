<?php

namespace App\Services\Assistant;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\Alert;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * LLM engine. The model is never allowed to answer from memory: it has to call
 * search_vehicles or identify_vehicle to say anything about a vehicle, and
 * propose_alert to turn the conversation into a saved search.
 */
class OpenAiEngine
{
    private const TOOLS = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'search_vehicles',
                'description' => 'Cherche des vehicules REELLEMENT disponibles dans le catalogue AutoAlert. A appeler avant d\'annoncer un prix ou une disponibilite.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'brand' => ['type' => 'string', 'description' => 'Marque exacte du catalogue'],
                        'model' => ['type' => 'string'],
                        'max_price' => ['type' => 'integer', 'description' => 'Budget maximum en FCFA'],
                        'min_price' => ['type' => 'integer'],
                        'min_year' => ['type' => 'integer'],
                        'max_mileage' => ['type' => 'integer'],
                        'body_type' => ['type' => 'string', 'enum' => ['SUV', 'SEDAN', 'HATCHBACK', 'WAGON', 'MPV', 'COUPE', 'CABRIOLET', 'PICKUP', 'VAN']],
                        'fuel' => ['type' => 'string', 'enum' => ['PETROL', 'DIESEL', 'HYBRID', 'ELECTRIC']],
                        'transmission' => ['type' => 'string', 'enum' => ['AUTOMATIC', 'MANUAL']],
                        'location' => ['type' => 'string'],
                        'sort' => ['type' => 'string', 'enum' => ['recent', 'price_asc', 'price_desc', 'year_desc', 'mileage_asc']],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'propose_alert',
                'description' => 'Propose de transformer la conversation en alerte enregistree. Utilise quand les criteres sont suffisants pour une recherche recurrente.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'brand' => ['type' => 'string'],
                        'model' => ['type' => 'string'],
                        'max_price' => ['type' => 'integer'],
                        'min_year' => ['type' => 'integer'],
                        'max_mileage' => ['type' => 'integer'],
                        'body_type' => ['type' => 'string'],
                        'fuel' => ['type' => 'string'],
                        'transmission' => ['type' => 'string'],
                        'location' => ['type' => 'string'],
                    ],
                    'required' => ['name'],
                ],
            ],
        ],
    ];

    public function __construct(
        private readonly OpenAiClient $client,
        private readonly VehicleSearchTool $search,
        private readonly RulesEngine $rules,
    ) {}

    public function reply(string $message, AssistantContext $context): AssistantReply
    {
        $started = microtime(true);

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($context)],
            ...$context->transcript(),
            ['role' => 'user', 'content' => $message],
        ];

        $criteria = $context->knownCriteria;
        $alertDraft = null;
        $vehicles = collect();

        // Two passes are enough for "recherche X, puis propose une alerte",
        // and they bound the cost of a long conversation.
        for ($turn = 0; $turn < 4; $turn++) {
            $completion = $this->client->chatCompletion($messages, self::TOOLS);

            if ($completion === null) {
                // The API failed: answer from the offline engine rather than
                // leaving the visitor with a dead end.
                return $this->fallbackToRules($message, $context, $started);
            }

            if (! isset($completion['tool_calls']) || $completion['tool_calls'] === []) {
                return new AssistantReply(
                    (string) ($completion['content'] ?? ''),
                    vehicles: $vehicles->all(),
                    alertDraft: $alertDraft,
                    engine: 'openai',
                    latencyMs: (int) ((microtime(true) - $started) * 1000),
                    criteria: $criteria,
                );
            }

            $messages[] = $completion;

            foreach ($completion['tool_calls'] as $call) {
                $name = $call['function']['name'] ?? '';
                $arguments = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];

                $result = match ($name) {
                    'search_vehicles' => $this->runSearch($arguments, $criteria, $vehicles),
                    'propose_alert' => $this->runAlert($arguments, $context, $criteria),
                    default => ['error' => 'outil inconnu'],
                };

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return new AssistantReply(
            "J'ai trouve des resultats, mais je n'ai pas reussi a conclure. Pouvez-vous preciser votre budget ou la marque recherchee ?",
            vehicles: $vehicles->all(),
            engine: 'openai',
            latencyMs: (int) ((microtime(true) - $started) * 1000),
        );
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $criteria
     * @param  Collection<int, Vehicle>  $vehicles
     * @return array<string, mixed>
     */
    private function runSearch(array $arguments, array &$criteria, Collection &$vehicles): array
    {
        $criteria = array_filter($arguments, static fn ($v) => $v !== null && $v !== '');
        $found = $this->search->search($criteria);

        $vehicles = $found;

        return [
            'nombre' => $found->count(),
            'vehicules' => $this->search->describe($found),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function runAlert(array $arguments, AssistantContext $context, array &$criteria): array
    {
        $name = (string) ($arguments['name'] ?? 'Mon alerte');
        unset($arguments['name']);

        $criteria = array_filter($arguments, static fn ($v) => $v !== null && $v !== '');

        return [
            'alert_draft' => $this->rules->buildDraft($criteria, $context) + ['name' => mb_substr($name, 0, 80)],
            'connecte' => $context->isAuthenticated(),
        ];
    }

    private function systemPrompt(AssistantContext $context): string
    {
        $vocabulary = $this->search->vocabulary();

        $prompt = (string) config('assistant.system');

        $prompt .= "\n\nVocabulaire du catalogue (seules valeurs valides pour les filtres) :\n";
        $prompt .= json_encode($vocabulary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt .= "\n\nCorrespondances issues des photos envoyees par le visiteur :\n";
        $prompt .= $context->identifiedVehicles === []
            ? 'Aucune.'
            : json_encode($context->identifiedVehicles, JSON_UNESCAPED_UNICODE);

        $prompt .= $context->isAuthenticated()
            ? "\n\nLe visiteur est connecte : tu peux creer l'alerte directement."
            : "\n\nLe visiteur n'est PAS connecte : propose l'alerte, elle sera enregistree apres inscription.";

        return $prompt;
    }

    private function fallbackToRules(string $message, AssistantContext $context, float $started): AssistantReply
    {
        $reply = $this->rules->reply($message, $context);

        return new AssistantReply(
            $reply->content,
            vehicles: $reply->vehicles,
            alertDraft: $reply->alertDraft,
            engine: 'rules-fallback',
            latencyMs: (int) ((microtime(true) - $started) * 1000),
            criteria: $reply->criteria,
        );
    }

    /** Persists a draft offered by either engine. */
    public function createAlert(User $user, array $draft): ?Alert
    {
        $criteria = $draft['criteria'] ?? [];

        return $user->alerts()->create([
            'name' => $draft['name'] ?? 'Alerte',
            'brand' => $criteria['brand'] ?? null,
            'model' => $criteria['model'] ?? null,
            'min_year' => $criteria['min_year'] ?? null,
            'max_price' => $criteria['max_price'] ?? null,
            'max_mileage' => $criteria['max_mileage'] ?? null,
            'body_type' => isset($criteria['body_type']) && array_key_exists($criteria['body_type'], BodyType::options())
                ? $criteria['body_type'] : null,
            'fuel' => isset($criteria['fuel']) && array_key_exists($criteria['fuel'], FuelType::options())
                ? $criteria['fuel'] : null,
            'transmission' => isset($criteria['transmission']) && array_key_exists($criteria['transmission'], Transmission::options())
                ? $criteria['transmission'] : null,
            'location' => $criteria['location'] ?? null,
            'frequency' => $draft['frequency'] ?? config('assistant.alert.default_frequency'),
            'is_active' => true,
        ]);
    }
}
