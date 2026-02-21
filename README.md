# OnlyRoll

[![CI/CD](https://github.com/LaurentRoches/OnlyRoll/workflows/CI-CD%20Pipeline/badge.svg)](https://github.com/LaurentRoches/OnlyRoll/actions)
[![Coverage](https://img.shields.io/badge/coverage-80%25-green.svg)](https://github.com/LaurentRoches/OnlyRoll)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Virtual Tabletop platform for Dungeons & Dragons 5e**

> *One site to Roll them all*

## Projet de Certification

Projet de table de jeu virtuelle (VTT) spécialisée pour Donjons & Dragons 5e, développé dans le cadre de la certification **Concepteur Développeur d'Applications**.

## Fonctionnalités

- **Authentification JWT** avec cookies HttpOnly sécurisés
- **Gestion de parties** : Création, recherche, invitation de joueurs
- **Cartes interactives** : Grille, tokens déplaçables, brouillard de guerre
- **Chat temps réel** avec WebSocket (Mercure)
- **Lanceur de dés** intégré avec historique
- **Wiki D&D 5e** : Base SRD complète et recherche
- **Feuilles de personnage** automatisées depuis le SRD

## Architecture Technique

### Stack Backend
- **PHP 8.3** / **Symfony 7.3**
- **MySQL 8.0** + **Redis** (cache)
- **API Platform** (REST API)
- **Mercure** (WebSocket temps réel)

### Stack Frontend
- **Vue.js 3.5** + **TypeScript**
- **Pinia** (state management)
- **Vite** (build tool)
- **TailwindCSS** (styling)

### DevOps
- **Docker** + **Docker Compose** (containerisation)
- **GitHub Actions** (CI/CD)
- **PHPUnit** + **Vitest** + **Playwright** (tests)
- **Hostinger VPS** (hébergement production)

## Stratégie de Tests

| Niveau | Outil | Scope | Couverture |
|---|---|---|---|
| Unitaires backend | PHPUnit | Services, repositories, entités | **88%** |
| Unitaires frontend | Vitest | Composants Vue, stores Pinia, helpers | **83%** |
| E2E | Playwright | Flux complets (inscription, connexion, partie, chat) | Scénarios critiques |

### Environnements

- **Dev local** : stack Docker Compose complète (frontend Vite sur port 5173, API Symfony sur port 80/nginx)
- **CI/CD** : GitHub Actions – jobs parallèles, rapport de couverture et rapport Playwright uploadés comme artefacts

### Scénarios E2E couverts

| Fichier | Flux testé |
|---|---|
| `e2e/auth.e2e.spec.ts` | Inscription → page de succès → connexion → accès à l'application |
| `e2e/game.e2e.spec.ts` | Création de partie (MJ) → rejoindre (joueur) → poster un message dans le chat |

## Installation

### Prérequis
- Docker & Docker Compose
- Node.js 20+
- PHP 8.3+

### Démarrage rapide

\`\`\`bash
# Clone le projet
git clone https://github.com/LaurentRoches/OnlyRoll.git
cd OnlyRoll

# Copie et configure l'environnement
cp .env.example .env

# Démarre l'infrastructure Docker
docker compose up -d

# Accède à l'application
Frontend:    http://localhost:5173
Backend API: http://localhost/api
\`\`\`

## Tests

### Tests backend (PHPUnit)

```bash
docker compose exec php-fpm php bin/phpunit
```

### Tests unitaires frontend (Vitest)

```bash
cd frontend
npm run test:unit          # mode watch
npm run test:coverage      # avec rapport de couverture
```

### Tests E2E (Playwright)

Les tests E2E couvrent deux scénarios critiques avec le stack complet (frontend + backend) :

| Fichier | Scénario |
|---|---|
| `e2e/auth.e2e.spec.ts` | Inscription via formulaire → connexion → accès à l'application |
| `e2e/game.e2e.spec.ts` | Création d'une partie (MJ) → rejoindre (joueur) → poster un message |

**Prérequis** : Docker Compose démarré (`docker compose up -d`)

```bash
cd frontend

# Installer les navigateurs Playwright (première fois)
npx playwright install chromium --with-deps

# Lancer les tests E2E
PLAYWRIGHT_BASE_URL=http://localhost:5173 npx playwright test e2e/auth.e2e.spec.ts e2e/game.e2e.spec.ts --project=chromium

# Afficher le rapport HTML après l'exécution
npx playwright show-report
```

> **Note** : `PLAYWRIGHT_BASE_URL=http://localhost:5173` pointe directement vers le serveur Vite du conteneur Docker (port exposé). Les appels API passent via `http://localhost/api` (nginx → backend).

### Tests E2E en CI/CD

Les tests E2E s'exécutent automatiquement dans la pipeline GitHub Actions (job `e2e-tests`) sur chaque push vers `main` ou `dev`. Le rapport Playwright est uploadé comme artefact.

## Documentation

- [Architecture Backend](docs/documentation_architecture/backend_architecture_doc.md)
- [Architecture Frontend](docs/documentation_architecture/frontend_architecture_doc.md)
- [Docker Infrastructure](docs/documentation_architecture/docker_architecture_doc.md)
- [WebSocket Events](docs/websocket_events.md)
- **API Swagger UI** : `/api/doc` (disponible en local après démarrage)

## Auteur

**Laurent Roches**  
Candidat à la certification *Concepteur Développeur d'Applications*

## Licence

MIT License - voir [LICENSE](LICENSE)