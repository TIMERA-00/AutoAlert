<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Import automatique d'annonces
    |---------------------------------------------------------------------------
    | L'import est une fonctionnalite optionnelle : l'application doit fonctionner
    | entierement en saisie manuelle. Lorsqu'il est actif, le service :
    |   - respecte une liste blanche de sources (allowed_hosts) ;
    |   - verifie robots.txt avant toute extraction ;
    |   - bloque les requetes vers des adresses internes (anti-SSRF) ;
    |   - limite la taille et la duree de la reponse.
    | N'activez l'extraction que pour les sources dont vous detenez les droits.
    */

    'enabled' => env('IMPORT_ENABLED', true),

    'timeout_ms' => env('IMPORT_TIMEOUT_MS', 12000),

    'max_bytes' => env('IMPORT_MAX_BYTES', 3_000_000),

    'allowed_hosts' => env('IMPORT_ALLOWED_HOSTS', ''),

];
