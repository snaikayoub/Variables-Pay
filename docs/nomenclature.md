# Nomenclature et conventions (MyMRH)

Ce document standardise les termes et les noms utilises dans l'interface (Twig) et dans le code.

## Termes fonctionnels

- `Prime de performance` : prime variable liee a la performance (ex: note hierarchique, jours perf, scores periode)
- `Prime de fonction` : prime liee a la fonction (taux monetaire, nombre de jours, note hierarchique 0..1)
- `Deplacement` : mission / voyage professionnel (validation par service puis division)
- `Periode de paie` : periode mensuelle ou quinzaine (statut: Ouverte/Fermee/Archivee)

## Rôles

- `Gestionnaire (service)` : saisie et soumission (prime de performance, prime de fonction, deplacements)
- `Responsable service` : validation service
- `Responsable division` : validation division
- `RH` : synthese, etats et exports
- `Admin` : administration (EasyAdmin)

## Conventions UI (Twig)

- Toujours preciser le type de prime dans les titres, boutons et sections
  - OK: `Primes de performance en attente`
  - NOK: `Primes en attente`
- Pour les CTA, preferer des libelles courts mais explicites
  - `Prime de performance`, `Prime de fonction`, `Deplacements`
- Utiliser `Mensuelle` / `Quinzaine` pour les types de paie

## Conventions de variables (templates)

- Eviter `primes` quand le contexte peut contenir plusieurs types
  - preferer `primePerformances`, `primeFonctions`
- Eviter `validated` sans contexte
  - preferer `serviceValidated...` / `divisionValidated...`

## Conventions de routes

- `gestionnaire_*` : routes de saisie/suivi cote gestionnaire
- `responsable_*` : routes cote responsable service
- `responsable_division_*` : routes cote responsable division
- `rh_*` : routes cote RH
