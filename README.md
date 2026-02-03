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
- **PHP 8.3** / **Symfony 7.1+**
- **MySQL 8.0** + **Redis** (cache)
- **API Platform** (REST API)
- **Mercure** (WebSocket temps réel)

### Stack Frontend
- **Vue.js 3.4** + **TypeScript**
- **Pinia** (state management)
- **Vite** (build tool)
- **TailwindCSS** (styling)

### DevOps
- **Docker** + **Docker Compose** (containerisation)
- **GitHub Actions** (CI/CD)
- **PHPUnit** + **Vitest** + **Playwright** (tests)
- **Hostinger VPS** (hébergement production)

## Couverture de Tests

- Backend (PHPUnit): **88%**
- Frontend (Vitest): **83%**
- E2E (Playwright): Scénarios critiques couverts

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
Frontend: http://localhost:5173
Backend API: http://localhost:8000/api
\`\`\`

## Tests

\`\`\`bash
# Tests backend
docker compose exec backend php bin/phpunit

# Tests frontend
docker compose exec frontend npm run test:unit
docker compose exec frontend npm run test:e2e
\`\`\`

## Documentation

- [Architecture Backend](docs/backend_architecture.md)
- [Architecture Frontend](docs/frontend_architecture.md)
- [Docker Infrastructure](docs/docker_architecture.md)
- [WebSocket Events](docs/websocket_events.md)

## Auteur

**Laurent Roches**  
Candidat à la certification *Concepteur Développeur d'Applications*

## Licence

MIT License - voir [LICENSE](LICENSE)