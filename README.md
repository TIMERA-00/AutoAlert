# AutoAlert

Centralisation des annonces de vehicules d'occasion et alertes personnalisees :
l'utilisateur definit ses criteres une fois, la plateforme regroupe les annonces
publiees sur plusieurs sources et le prevenit des qu'un vehicule correspond.

## Fonctionnalites

- **Catalogue public** avec recherche, filtres (marque, modele, prix, annee,
  kilometrage, carburant, boite, carrosserie, ville), tri et pagination.
- **Fiches vehicule** avec galerie, redirection trackee vers la source d'origine,
  favoris, partage et vehicules similaires.
- **Alertes** : criteres enregistres, frequence (immediat / quotidien /
  hebdomadaire), apercu du nombre de correspondances.
- **Notifications** multicanal (email, WhatsApp, in-app) avec badge et historique.
- **Espace admin** : CRUD vehicules, import d'annonces depuis une URL, gestion
  des sources, des utilisateurs, surveillance des alertes, journal des
  notifications et statistiques.
- **API mobile** `/api/v1` authentifiee par token Sanctum.

## Stack

| Composant | Choix |
|---|---|
| Framework | Laravel 13 |
| UI | Blade + Livewire 4 |
| Style | Tailwind CSS 4 (Vite 8) |
| Auth web | Breeze (sessions) |
| Auth mobile | Sanctum (tokens) |
| Base de donnees | SQLite (dev) / PostgreSQL (prod) |
| File d'attente | `database` (dev) / Redis (prod) |
| Tests | PHPUnit |

## Installation locale

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed
```

Puis, dans deux terminaux :

```bash
php artisan serve          # http://127.0.0.1:8000
npm run dev                # Vite (ou `npm run build` pour un build statique)
```

Ou en une seule commande : `composer dev`.

### Comptes de demonstration

| Role | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@autoalert.dev` | `Admin@2024` |
| Utilisateur | `mamadou@example.com` | `Password@2024` |

## Configuration

Les variables qui pilotent les integrations optionnelles sont toutes vides par
defaut : l'application fonctionne sans elles, avec des degrades explicites.

| Variable | Role |
|---|---|
| `CLOUDINARY_*` | Stockage et redimensionnement des images ; disque `public` sinon |
| `WHATSAPP_PHONE_NUMBER_ID` / `WHATSAPP_ACCESS_TOKEN` | Envoi WhatsApp via l'API Cloud |
| `IMPORT_ENABLED` / `IMPORT_ALLOWED_HOSTS` | Import automatique depuis une URL tierce |

L'import automatique est volontairement restreint : liste blanche d'hotes,
limite de taille de charge utile, respect de `robots.txt` et delai d'attente.

## Taches planifiees

`routes/console.php` definit le resume quotidien (08:00), le resume hebdomadaire
(lundi 08:15) et le nettoyage des tables de tracking (03:30). Sans planificateur,
ces taches ne s'executent jamais :

```bash
php artisan schedule:work          # developpement
* * * * * cd /app && php artisan schedule:run >> /dev/null 2>&1   # production
```

## API mobile

Base : `/api/v1`. Authentification par token Bearer.

```bash
# recuperer un token
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@autoalert.dev","password":"Admin@2024"}'

# lister les vehicules filtres
curl 'http://localhost:8000/api/v1/vehicles?marque=Toyota&prix_max=20000000&tri=price_asc' \
  -H 'Accept: application/json'
```

Les filtres acceptent trois conventions interchangeables, pour ne pas contrer
les clients mobiles : `maxPrice`, `max_price` et `prix_max` sont equivalents.

| Methode | Route | Auth |
|---|---|---|
| POST | `/auth/register`, `/auth/login`, `/auth/forgot-password` | non |
| GET | `/vehicles`, `/vehicles/facets`, `/vehicles/{slug}` | non |
| GET/POST | `/me`, `/logout` | oui |
| GET/POST/DELETE | `/favorites`, `/favorites/{slug}` | oui |
| GET/POST/PATCH/DELETE | `/alerts`, `/alerts/{id}/toggle` | oui |
| GET/PATCH | `/notifications`, `/notifications/{id}/read`, `/notifications/read-all` | oui |

## Tests

```bash
./vendor/bin/pint          # mise en forme
./vendor/bin/phpunit       # 74 tests : catalogue, alertes, API, acces admin, auth
```

La suite utilise SQLite en memoire : aucune base de donnees n'est requise.

## Docker

```bash
docker compose up --build
```

Le compose lance l'application (PostgreSQL + Redis), un worker de file
d'attente et le planificateur. Variables utiles : `APP_PORT`, `DB_PASSWORD`,
`AUTO_SEED=true` pour peupler la base au premier demarrage.

## Structure

```
app/
  Enums/           statuts et options (workflow vehicule, roles, canaux, tri)
  Jobs/            matching a la publication, envoi, resume periodical
  Livewire/        catalogue, fiche vehicule, compte et back-office
  Models/          Vehicle, Alert, Favorite, Notification, Source, ...
  Notifications/   classes de notification par canal
  Services/        MatchingService, NotificationDispatcher, import, images, stats
resources/views/
  components/      design system Blade (cartes, badges, champs, layouts)
  livewire/        vues des composants Livewire
routes/
  web.php          catalogue public, espace utilisateur, back-office
  api.php          API mobile v1
  console.php      resumes et nettoyage
tests/Feature/     CatalogTest, AlertMatchingTest, ApiV1Test, AdminAccessTest, Auth
```