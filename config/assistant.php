<?php

use App\Enums\AlertFrequency;
use App\Enums\NotificationChannel;

return [

    /*
    |--------------------------------------------------------------------------
    | Activation
    |--------------------------------------------------------------------------
    |
    | The widget can be disabled globally without touching the code, for
    | instance while moderation is being set up.
    |
    */

    'enabled' => env('ASSISTANT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Engine
    |--------------------------------------------------------------------------
    |
    | "openai" when an API key is present, "rules" otherwise. The rules engine
    | never calls the network: it parses intent and searches the catalogue
    | directly, so the widget stays usable (and testable) with no key at all.
    |
    */

    'driver' => env('ASSISTANT_DRIVER'),

    'key' => env('OPENAI_API_KEY'),

    'model' => env('ASSISTANT_MODEL', 'gpt-4o-mini'),

    'vision_model' => env('ASSISTANT_VISION_MODEL', 'gpt-4o-mini'),

    'api_base' => env('OPENAI_API_BASE', 'https://api.openai.com/v1'),

    'timeout' => (int) env('ASSISTANT_TIMEOUT', 25),

    /*
    |--------------------------------------------------------------------------
    | Conversation shape
    |--------------------------------------------------------------------------
    */

    'max_history' => (int) env('ASSISTANT_MAX_HISTORY', 20),

    'max_results' => (int) env('ASSISTANT_MAX_RESULTS', 4),

    'max_photo_mb' => (int) env('ASSISTANT_MAX_PHOTO_MB', 6),

    'locale' => env('ASSISTANT_LOCALE', 'fr'),

    /*
    |--------------------------------------------------------------------------
    | Prompt
    |--------------------------------------------------------------------------
    |
    | The assistant is a salesperson, not a generic chatbot. It must ground
    | every claim in the catalogue and offer to save an alert rather than
    | inventing availability.
    |
    */

    'system' => <<<'PROMPT'
    Tu es l'assistant d'AutoAlert, une plateforme qui centralise les annonces de vehicules d'occasion.

    Ton role : comprendre ce que la personne cherche, poser au plus deux questions
    de clarification si un critere manque, puis trouver des vehicules REELLEMENT
    presents dans le catalogue avec l'outil search_vehicles.

    Regles strictes :
    - N'invente jamais un vehicule, un prix ou une disponibilite. Si l'outil ne
      renvoie rien, dis-le franchement et propose d'élargir la recherche.
    - N'utilise que les informations renvoyées par l'outil. Ne complete jamais
      une caracteristique manquante par ce que tu sais du marche.
    - Prices en FCFA. Reponds en francais, ton direct et chaleureux, phrases courtes.
    - Quand les criteres suffisent, propose de creer une alerte (outil propose_alert)
      plutot que de demander a l'utilisateur de surveiller le catalogue lui-meme.
    - Si la personne envoie une photo, utilise identify_vehicle puis confirme
      l'identification avec elle avant de l'utiliser comme critere.

    Plusieurs criteres a la fois : marque, modele, budget max, annee min, kilometrage
    max, carrosserie, carburant, boite, ville.
    PROMPT,

    /*
    |--------------------------------------------------------------------------
    | Handover to a saved alert
    |--------------------------------------------------------------------------
    */

    'alert' => [
        'default_frequency' => AlertFrequency::Immediate->value,
        'channels' => [
            NotificationChannel::InApp->value,
            NotificationChannel::Email->value,
        ],
    ],

];
