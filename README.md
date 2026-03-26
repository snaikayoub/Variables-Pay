# Variables-Pay

Application Symfony (PHP 8.2+) pour la gestion RH (primes, validations, deplacements, etc.).

## Prerequis

- PHP 8.2+
- Composer
- Node.js + npm (assets via Webpack Encore)
- MySQL 8
- (Optionnel) Docker + Docker Compose

## Configuration locale

1) Variables d'environnement Symfony

- Copier les valeurs sensibles dans `/.env.local` (non commite).
- Le fichier `/.env` contient uniquement des valeurs par defaut (sans secrets).

Exemple (a adapter):

```
APP_SECRET=...
DATABASE_URL="mysql://user:pass@127.0.0.1:3306/db?serverVersion=8.0&charset=utf8mb4"
```

2) JWT (API mobile)

- Genere les cles JWT (non commitees):

```
php bin/console lexik:jwt:generate-keypair
```

Les chemins et la passphrase sont dans `config/packages/lexik_jwt_authentication.yaml` et `.env(.local)`.

3) Base de donnees (option Docker)

- Copier `/.env.docker.example` vers `/.env.docker` puis ajuster les valeurs.
- Lancer:

```
docker compose up -d
```

## Installation

```
composer install
npm install
npm run dev
```

Puis:

```
php bin/console doctrine:migrations:migrate
php -S 127.0.0.1:8000 -t public
```

## Reset dev (seed)

Commande "one-shot" pour recreer la base et recharger des donnees locales:

```
php bin/console app:dev:reset --force --migrate
```

Optionnel (si tu veux aussi drop/create la base):

```
php bin/console app:dev:reset --force --recreate-db --migrate
```

## Mobile

- Voir `mobile/README.md`.
- L'emulateur Android utilise en general `https://10.0.2.2:8000` pour acceder au backend local.

## API (validations)

- Scenarios de test HTTP: `docs/api_v1_validations.http`

## Tests

```
composer test
```

Note: certains tests (integration/API) utilisent une base de donnees de test (config `APP_ENV=test`).
- Par defaut, `.env.test` utilise MySQL (base `variables_pay`). Symfony/Doctrine ajoute automatiquement le suffixe `_test` en environnement test.
- Assure-toi que MySQL tourne (ex: `docker compose up -d`). Le schema de test est recree automatiquement.
- Mets tes identifiants locaux dans `.env.test.local` (non commite).

## Notes securite

- Ne commit pas de secrets dans `/.env`, `compose.yaml` ou ailleurs.
- Les actions sensibles (validation/retour) sont protegees par CSRF et par des verifications de perimetre (service/division).
