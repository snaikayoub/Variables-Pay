# Flutter (Android) - Variables-Pay

## Prerequis

- Flutter SDK installe (stable)
- Android Studio + Android SDK

## Creation du projet

Depuis la racine du repo:

```bash
flutter create mobile/variables_pay_android
```

Puis, copie le code de `mobile/variables_pay/` vers `mobile/variables_pay_android/` (en gardant le dossier `android/` genere).

## Base URL (Android emulator)

- Si Symfony tourne sur ta machine en local (ex: `symfony serve` / `php -S`), l'emulateur Android doit utiliser:
  - `https://10.0.2.2:8000`

Lancer avec une baseUrl custom:

```bash
flutter run --dart-define=API_BASE_URL=https://10.0.2.2:8000
```

## Endpoints V1 (validations)

Voir `docs/api_v1_validations.http` pour des exemples.
