<?php

namespace App\Services\Assistant;

use App\Enums\BodyType;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Offline engine: parses intent, searches the catalogue and writes the reply
 * itself. No API key, no network, no rate limit — and the reason the widget
 * is demonstrable on a fresh clone.
 */
class RulesEngine
{
    public function __construct(
        private readonly IntentParser $parser,
        private readonly VehicleSearchTool $search,
    ) {}

    public function reply(string $message, AssistantContext $context): AssistantReply
    {
        $parsed = $this->parser->parse($message);

        return match ($parsed['intent']) {
            'greeting' => $parsed['criteria'] !== []
                ? $this->searchReply(['intent' => 'search'] + $parsed, $context)
                : $this->greeting(),
            'capabilities' => $this->capabilities(),
            'search' => $this->searchReply($parsed, $context),
            'question_about_catalogue' => $this->questionReply($parsed, $context),
            default => $parsed['isSmallTalk']
                ? $this->smallTalk()
                : $this->fallback($parsed),
        };
    }

    private function greeting(): AssistantReply
    {
        return new AssistantReply(
            "Bonjour ! Je vous aide a trouver un vehicule dans notre catalogue.\n\nDites-moi ce que vous cherchez : une marque, un budget, une carrosserie, une ville... Vous pouvez aussi m'envoyer une photo de votre voiture actuelle.",
            engine: 'rules'
        );
    }

    private function capabilities(): AssistantReply
    {
        $range = $this->search->priceRange();
        $brands = collect($this->search->brands())->take(8)->implode(', ');

        return new AssistantReply(
            "Voici ce que je sais faire :\n\n"
            ."• Chercher dans le catalogue par marque, modele, budget, annee, kilometrage, carrosserie, carburant, boite ou ville.\n"
            ."• Reconnaître votre voiture a partir d'une photo.\n"
            ."• Creer une alerte qui vous previent des qu'un vehicule correspond.\n\n"
            .'Le catalogue compte '.count($this->search->brands())." marques ({$brands}) "
            .'pour des prix de '.number_format($range['min'], 0, ',', ' ').' a '.number_format($range['max'], 0, ',', ' ').' FCFA.',
            engine: 'rules'
        );
    }

    private function smallTalk(): AssistantReply
    {
        return new AssistantReply(
            'Je peux vous aider a trouver un vehicule. Dites-moi une marque, un budget, ou envoyez-moi une photo de votre voiture.',
            engine: 'rules'
        );
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function searchReply(array $parsed, AssistantContext $context): AssistantReply
    {
        $criteria = $this->mergeWithKnown($parsed['criteria'], $context->knownCriteria);
        $vehicles = $this->search->search($criteria);

        if ($vehicles->isEmpty()) {
            return $this->noResult($criteria, $parsed);
        }

        return new AssistantReply(
            $this->describeResults($vehicles, $criteria),
            vehicles: $vehicles->all(),
            alertDraft: $this->offerAlert($vehicles, $criteria, $context),
            engine: 'rules',
            criteria: $criteria,
        );
    }

    /** @param array<string, mixed> $parsed */
    private function questionReply(array $parsed, AssistantContext $context): AssistantReply
    {
        return $this->searchReply($parsed, $context);
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<string, mixed>  $known
     * @return array<string, mixed>
     */
    private function mergeWithKnown(array $criteria, array $known): array
    {
        // The visitor's latest sentence refines, never silently clears, what
        // they already established two messages ago.
        foreach ($criteria as $key => $value) {
            $known[$key] = $value;
        }

        return array_filter($known, static fn ($v) => $v !== null && $v !== '');
    }

    /** @param array<string, mixed> $criteria */
    private function noResult(array $criteria, array $parsed): AssistantReply
    {
        $label = $this->criteriaLabel($criteria);

        $hints = [];

        if (isset($criteria['max_price'])) {
            $range = $this->search->priceRange();
            if ($criteria['max_price'] < $range['min']) {
                $hints[] = 'notre catalogue commence a '.number_format($range['min'], 0, ',', ' ').' FCFA';
            } elseif ($criteria['max_price'] < $range['max']) {
                $hints[] = 'vous pouvez monter jusqu\'a '.number_format(min($range['max'], (int) ($criteria['max_price'] * 1.5)), 0, ',', ' ').' FCFA';
            }
        }

        if (isset($criteria['brand'])) {
            $hints[] = 'aucun '.$criteria['brand'].' ne correspond encore';
        }

        $suffix = $hints === []
            ? ''
            : "\n\nPour information : ".implode(' ; ', $hints).'.';

        return new AssistantReply(
            "Je n'ai rien pour {$label} dans le catalogue pour le moment.{$suffix}\n\nVoulez-vous que je creer une alerte, pour etre prevenu des qu'une annonce correspond ?",
            engine: 'rules',
            criteria: $criteria,
        );
    }

    /** @param array<string, mixed> $criteria */
    private function criteriaLabel(array $criteria): string
    {
        $parts = [];

        if (! empty($criteria['brand'])) {
            $parts[] = $criteria['brand'];
        }

        if (! empty($criteria['model'])) {
            $parts[] = $criteria['model'];
        }

        if (! empty($criteria['body_type'])) {
            $parts[] = mb_strtolower(BodyType::options()[$criteria['body_type']] ?? '');
        }

        if (! empty($criteria['max_price'])) {
            $parts[] = 'sous '.number_format((int) $criteria['max_price'], 0, ',', ' ').' FCFA';
        }

        if (! empty($criteria['min_year'])) {
            $parts[] = 'a partir de '.$criteria['min_year'];
        }

        if (! empty($criteria['location'])) {
            $parts[] = 'a '.$criteria['location'];
        }

        return $parts === [] ? 'ces criteres' : implode(', ', array_filter($parts));
    }

    /** @param array<string, mixed> $criteria */
    private function describeResults(Collection $vehicles, array $criteria): string
    {
        $count = $vehicles->count();
        $lines = [$count > 1
            ? "J'ai trouve {$count} vehicules qui correspondent."
            : "J'ai trouve un vehicule qui correspond."];

        foreach ($vehicles as $vehicle) {
            $lines[] = sprintf(
                '• %s — %s FCFA, %d km, %s, %s (%s)',
                $vehicle->title(),
                number_format($vehicle->price, 0, ',', ' '),
                $vehicle->mileage,
                $vehicle->fuel?->label(),
                $vehicle->transmission?->label(),
                $vehicle->location,
            );
        }

        if ($count >= (int) config('assistant.max_results', 4)) {
            $lines[] = "\nJ'affiche les {$count} premiers resultats.";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>|null
     */
    private function offerAlert(Collection $vehicles, array $criteria, AssistantContext $context): ?array
    {
        if ($criteria === []) {
            return null;
        }

        // One match today is not worth an alert: the visitor can just take it.
        if ($vehicles->count() <= 1) {
            return null;
        }

        return $this->buildDraft($criteria, $context);
    }

    /** @param array<string, mixed> $criteria */
    public function buildDraft(array $criteria, AssistantContext $context): array
    {
        $name = collect([
            $criteria['brand'] ?? null,
            $criteria['model'] ?? null,
            $criteria['max_price'] ?? null ? 'max '.number_format((int) $criteria['max_price'], 0, ',', ' ').' FCFA' : null,
        ])->filter()->implode(' ');

        return [
            'name' => mb_substr($name !== '' ? $name : 'Mon alerte', 0, 80),
            'criteria' => array_filter($criteria, static fn ($v) => $v !== null && $v !== ''),
            'frequency' => $context->defaultFrequency()->value,
            'channels' => array_map(fn ($c) => $c->value, $context->defaultChannels()),
            'match_count' => $this->search->count($criteria),
        ];
    }

    /** @param array<string, mixed> $parsed */
    private function fallback(array $parsed): AssistantReply
    {
        return new AssistantReply(
            "Je n'ai pas bien saisi ce que vous cherchez. Pouvez-vous me donner au moins une marque, un modele ou un budget ? Par exemple : \"un SUV automatique sous 15 millions a Dakar\".",
            engine: 'rules',
        );
    }

    /** Builds a reply from a photo, independent of the engine. */
    public function identificationReply(array $identification): AssistantReply
    {
        $message = $identification['message'];
        $candidates = $identification['candidates'] ?? [];
        $vehicles = collect($candidates)
            ->map(fn (array $c) => Vehicle::published()->with('images')->find($c['id']))
            ->filter()
            ->all();

        return new AssistantReply(
            $message,
            vehicles: $vehicles,
            identification: $identification,
            engine: 'rules',
        );
    }
}
