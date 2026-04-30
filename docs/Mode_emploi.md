# Grimoire du Développeur OnlyRoll
### *Guide d'aventure pour ceux qui rejoignent la guilde*

---

> *"Bienvenue, voyageur. Tu t'apprêtes à rejoindre l'une des guildes les plus ambitieuses du royaume numérique. Avant de brandir ton clavier comme une épée, écoute bien ce que j'ai à te dire. Ce grimoire contient tout ce dont tu as besoin pour survivre, et prospérer, dans le monde d'OnlyRoll."*
>
> Le Maître du Jeu

---

## Prologue : L'Histoire d'OnlyRoll

OnlyRoll est une **table de jeu virtuelle (VTT)** spécialisée pour Donjons & Dragons 5e. Elle permet à des aventuriers de se retrouver en ligne pour lancer des dés, gérer des parties, discuter en temps réel et consulter l'encyclopédie complète du SRD (System Reference Document).

Ce n'est pas un simple projet. C'est une **certification professionnelle** (Concepteur Développeur d'Applications), forgée avec :

- Un backend **Symfony 7.4 / PHP 8.3** qui gère l'API REST, la sécurité JWT et les événements temps réel
- Un frontend **Vue.js 3.5 / TypeScript** qui rend l'aventure vivante dans le navigateur
- Un système de cache **Redis** qui soulage la base de données pour les requêtes répétitives (wiki, présence des joueurs)
- Un bus temps réel **Mercure** pour les messages de chat et les jets de dés en direct
- Une armée de tests : **PHPUnit**, **Vitest** et **Playwright** E2E

---

## Chapitre I : L'Équipement de l'Aventurier

*Avant de partir en quête, vérifie que tu possèdes les bons outils. Un guerrier mal équipé est un guerrier mort.*

### Prérequis obligatoires

| Outil | Version minimale | Usage |
|-------|-----------------|-------|
| **Docker Desktop** | 4.x | Fait tourner toute l'infrastructure |
| **Git** | 2.40+ | Gestion du code source |
| **Node.js** | 26.x | Développement frontend local (hors Docker) |
| **PHP** | 8.3+ | Développement backend local (optionnel) |
| **Composer** | 2.x | Gestionnaire de dépendances PHP |

> **Note du MJ :** En réalité, Docker suffit pour tout faire tourner. Node.js et PHP en local sont utiles pour l'autocomplétion dans ton IDE, mais non obligatoires.

### IDE recommandé

- **VS Code** avec les extensions : PHP Intelephense, Volar (Vue), ESLint, Prettier, Docker
- **PHPStorm** (alternative premium, supporte nativement Symfony et Vue)

### Vérifier ton équipement

```bash
docker --version          # Docker Desktop 4.x+
git --version             # git 2.40+
node --version            # v26.x.x
php --version             # PHP 8.3.x
composer --version        # Composer 2.x
```

---

## Chapitre II : La Carte du Royaume

*Avant de te lancer, comprends la géographie des terres que tu vas parcourir.*

```
OnlyRoll/
├── backend/               ← Symfony 7.4 (API REST, JWT, domaine métier)
│   ├── src/
│   │   ├── Controller/    ← Points d'entrée HTTP
│   │   ├── Entity/        ← Entités Doctrine (User, Game, Message…)
│   │   ├── Repository/    ← Requêtes base de données
│   │   ├── Service/       ← Logique métier
│   │   └── EventSubscriber/ ← Listeners Symfony (JWT refresh, etc.)
│   ├── config/            ← Configuration Symfony
│   └── tests/             ← Tests PHPUnit
│
├── frontend/              ← Vue.js 3.5 + TypeScript
│   ├── src/
│   │   ├── components/    ← Composants Vue réutilisables
│   │   ├── views/         ← Pages de l'application
│   │   ├── stores/        ← État global Pinia
│   │   ├── services/api/  ← Clients HTTP (Axios)
│   │   └── composables/   ← Logique réutilisable (ex: useInfiniteScroll)
│   └── e2e/               ← Tests Playwright
│
├── docker/                ← Configuration Docker (nginx, redis, php)
├── docker-compose.yml     ← Environnement de développement
├── docker-compose.prod.yml ← Environnement de production
└── .github/workflows/     ← Pipeline CI/CD GitHub Actions
```

### Services Docker

| Service | Rôle | Port exposé |
|---------|------|-------------|
| `onlyroll-nginx` | Reverse proxy → PHP-FPM | 80 |
| `onlyroll-php` | Symfony / PHP-FPM | — |
| `onlyroll-frontend` | Vite dev server | 5173 |
| `onlyroll-mysql` | Base de données | 3306 |
| `onlyroll-redis` | Cache + présence temps réel | 6379 |
| `onlyroll-mercure` | WebSocket (Server-Sent Events) | 3000 |
| `onlyroll-phpmyadmin` | Interface base de données | 8080 |
| `onlyroll-mailhog` | Capture emails de test | 8025 |

---

## Chapitre III : Préparer son Campement

*Voici les rituels d'invocation pour faire apparaître le projet sur ta machine.*

### 1. Cloner le dépôt

```bash
git clone https://github.com/LaurentRoches/OnlyRoll.git
cd OnlyRoll
```

### 2. Configurer les variables d'environnement

```bash
# Backend
cp backend/.env.example backend/.env.local

# Frontend (optionnel, les valeurs par défaut conviennent en local)
cp frontend/.env.example frontend/.env.local
```

Édite `backend/.env.local` avec tes valeurs :

```dotenv
# Base de données
DATABASE_URL="mysql://onlyroll:onlyroll@mysql:3306/onlyroll"

# Redis
REDIS_URL="redis://redis:6379"

# JWT (générées par la commande ci-dessous)
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=ton_passphrase_secret

# Mercure
MERCURE_URL=http://mercure:80/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET=ton_jwt_secret_mercure
```

### 3. Démarrer l'infrastructure Docker

```bash
# Premier démarrage (build complet)
docker compose up -d --build

# Démarrages suivants
docker compose up -d
```

### 4. Initialiser la base de données

```bash
# Créer le schéma
docker exec onlyroll-php php bin/console doctrine:migrations:migrate --no-interaction

# (Optionnel) Charger les fixtures de développement
docker exec onlyroll-php php bin/console doctrine:fixtures:load --no-interaction
```

### 5. Générer les clés JWT

```bash
docker exec onlyroll-php php bin/console lexik:jwt:generate-keypair
```

### 6. Vérifier que tout fonctionne

| URL | Description |
|-----|-------------|
| http://localhost:5173 | Application frontend |
| http://localhost/api | API REST Symfony |
| http://localhost/api/doc | Documentation Swagger |
| http://localhost/health | Health check backend |
| http://localhost:8080 | phpMyAdmin |
| http://localhost:8025 | Mailhog (emails de test) |

---

## Chapitre IV : Les Lois du Royaume

*Tout royaume a ses lois. Le non-respect de ces lois mène à l'exil du dépôt. Mémorise-les bien.*

### Conventional Commits

Chaque message de commit doit respecter le format :

```
<type>(<scope>): <description courte>

[corps optionnel]

[footer optionnel]
```

#### Types autorisés

| Type | Usage |
|------|-------|
| `feat` | Nouvelle fonctionnalité |
| `fix` | Correction de bug |
| `hotfix` | Correction critique en production |
| `docs` | Modification de documentation uniquement |
| `refactor` | Refactorisation sans ajout de fonctionnalité ni correction de bug |
| `test` | Ajout ou modification de tests |
| `chore` | Tâches de maintenance (dépendances, config, CI…) |
| `style` | Mise en forme du code (espaces, virgules…) sans impact fonctionnel |
| `perf` | Amélioration de performance |

#### Scope (optionnel mais recommandé)

Le scope précise quelle partie du projet est concernée :

```
feat(auth): ajouter la vérification email après inscription
fix(wiki): corriger la pagination des sorts avec filtres multiples
test(game): ajouter les tests E2E pour la création de partie
chore(deps): mettre à jour Symfony vers 7.4.8
```

#### Exemples concrets

```bash
git commit -m "feat(game): permettre l'invitation de joueurs par email"
git commit -m "fix(chat): corriger l'ancrage de scroll lors du chargement des anciens messages"
git commit -m "docs: mettre à jour le guide d'installation dans Mode_emploi.md"
git commit -m "refactor(wiki): extraire la logique de cache dans un service dédié"
git commit -m "test(auth): ajouter les tests unitaires pour JwtCookieRefreshSubscriber"
```

> **Astuce du MJ :** La description doit répondre à la question *"Que fait ce commit ?"*, pas *"Comment ?"*. Évite les messages vagues comme `fix bugs` ou `update`.

---

## Chapitre V : Le Chemin des Héros

*La stratégie de branches est ta carte de route. S'en écarter, c'est se perdre dans les forêts sombres du conflit de merge.*

### Gitflow simplifié

```
main ──────────────────────────────────────── Production stable
  │
  └─── dev ──────────────────────────────── Branche d'intégration
         │
         ├─── feature/nom-de-feature ──── Développement de fonctionnalité
         ├─── feature/autre-feature
         │
         └─── hotfix/nom-du-fix ──────── Correction critique → merge dans main ET dev
```

### Règles

1. **Ne jamais commiter directement sur `main`** : c'est la branche de production
2. **Ne jamais commiter directement sur `dev`** : c'est la branche d'intégration
3. **Toujours partir de `dev`** pour créer une feature branch
4. **Une Pull Request par feature** : pas de feat/mega-feature-tout-en-un

### Workflow type

```bash
# 1. Se placer sur dev et récupérer les dernières modifications
git checkout dev
git pull origin dev

# 2. Créer ta branche de fonctionnalité
git checkout -b feature/ma-super-fonctionnalite

# 3. Développer, commiter régulièrement
git add backend/src/Controller/MaFonctionnalite.php
git commit -m "feat(ma-fonc): implémenter l'endpoint de création"

# 4. Pousser ta branche
git push origin feature/ma-super-fonctionnalite

# 5. Ouvrir une Pull Request vers dev sur GitHub
# → La CI/CD se déclenche automatiquement
# → Attendre la review et le passage de tous les checks
```

### Nommage des branches

```
feature/nom-explicite        # Nouvelle fonctionnalité
fix/description-du-bug       # Correction de bug
hotfix/critique-prod         # Correction urgente en production
docs/mise-a-jour-guide       # Documentation uniquement
refactor/wiki-cache-service  # Refactorisation
```

### Hotfix (correction urgente en production)

```bash
# Partir de main
git checkout main
git pull origin main
git checkout -b hotfix/description-courte

# Corriger, commiter
git commit -m "hotfix(scope): description de la correction critique"

# PR vers main (déploiement rapide)
# Puis merger également dans dev pour rester synchronisé
```

---

## Chapitre VI : Les Épreuves

*Tout héros doit prouver sa valeur. Dans ce royaume, les épreuves s'appellent des tests. Ils ne mentent jamais.*

### Tests backend (PHPUnit)

```bash
# Lancer tous les tests
docker exec onlyroll-php php bin/phpunit

# Avec rapport de couverture
docker exec onlyroll-php php bin/phpunit --coverage-html coverage/

# Un test spécifique
docker exec onlyroll-php php bin/phpunit tests/Unit/Service/DiceServiceTest.php
```

### Tests unitaires frontend (Vitest)

```bash
# Mode watch (développement)
cd frontend && npm run test:unit

# Exécution unique avec couverture
cd frontend && npm run test:coverage
```

### Tests E2E (Playwright)

Les tests E2E nécessitent la stack Docker complète en cours d'exécution.

```bash
# Depuis la racine du projet, s'assurer que Docker tourne
docker compose up -d

# Lancer les tests E2E
cd frontend
PLAYWRIGHT_BASE_URL=http://localhost:5173 npx playwright test e2e/ --project=chromium

# Voir le rapport interactif
npx playwright show-report
```

> **Note du MJ :** Les tests E2E vérifient la vérification email via Mailhog. Assure-toi que le service Mailhog est démarré et accessible sur http://localhost:8025.

### Pipeline CI/CD

La pipeline s'exécute automatiquement sur chaque push vers `main` ou `dev`, et sur chaque Pull Request. Elle comprend :

1. **Validation Docker** : build des images
2. **Tests PHP** : PHPUnit + PHPStan + PHP-CS-Fixer
3. **Tests Frontend** : Vitest + couverture
4. **Tests E2E** : Playwright sur stack complète
5. **Déploiement** : sur le VPS Hostinger (uniquement sur `main` avec tag)

---

## Chapitre VII : Les Sorts Utiles

*Le grimoire d'un mage contient des sorts pour toutes les occasions. Mémorise ceux-ci.*

### Docker

```bash
# Démarrer tous les services
docker compose up -d

# Voir les logs d'un service
docker compose logs -f php
docker compose logs -f frontend

# Redémarrer un service
docker compose restart php

# Arrêter tout
docker compose down

# Arrêter et supprimer les volumes (reset BdD)
docker compose down -v
```

### Symfony Console

```bash
# Dans le conteneur PHP
docker exec onlyroll-php php bin/console <commande>

# Commandes utiles
docker exec onlyroll-php php bin/console cache:clear
docker exec onlyroll-php php bin/console doctrine:migrations:migrate
docker exec onlyroll-php php bin/console doctrine:migrations:diff
docker exec onlyroll-php php bin/console make:entity
docker exec onlyroll-php php bin/console make:controller
docker exec onlyroll-php php bin/console debug:router
docker exec onlyroll-php php bin/console debug:container
```

### Composer

```bash
# Installer les dépendances
docker exec onlyroll-php composer install

# Ajouter un package
docker exec onlyroll-php composer require nom/du-package

# Mettre à jour les dépendances
docker exec onlyroll-php composer update
```

### Redis

```bash
# Vérifier les clés en cache
docker exec onlyroll-redis redis-cli keys "wiki_*"

# Vider tout le cache applicatif
docker exec onlyroll-php php bin/console cache:pool:clear cache.app

# Se connecter au CLI Redis
docker exec -it onlyroll-redis redis-cli
```

### Frontend

```bash
# Installer les dépendances (hors Docker)
cd frontend && npm install

# Build de production
cd frontend && npm run build

# Linter
cd frontend && npm run lint

# Formatter
cd frontend && npm run format
```

---

## Chapitre VIII : L'Oracle des Bugs

*Même les plus grands héros tombent. Ce qui les distingue, c'est leur capacité à se relever et à comprendre pourquoi.*

### Voir les logs

```bash
# Logs Symfony (erreurs PHP)
docker compose logs php --tail=50

# Logs nginx (requêtes HTTP)
docker compose logs nginx --tail=50

# Logs frontend (Vite)
docker compose logs frontend --tail=50

# Logs en temps réel
docker compose logs -f php nginx
```

### Xdebug (débogage pas à pas)

Xdebug est installé dans l'image de développement PHP. Configure ton IDE :

- **VS Code** : extension PHP Debug, listener sur le port 9003
- **PHPStorm** : Preferences → PHP → Debug → port 9003

Activer le débogage dans `.env.local` :

```dotenv
XDEBUG_MODE=debug
XDEBUG_SESSION=1
```

### Mailhog (emails de test)

Tous les emails envoyés par l'application en développement sont capturés par Mailhog.

- Interface web : http://localhost:8025
- API : `GET http://localhost:8025/api/v2/messages`

### Erreurs courantes

| Symptôme | Cause probable | Solution |
|----------|---------------|----------|
| `502 Bad Gateway` | PHP-FPM pas démarré | `docker compose restart php` |
| `JWT token not found` | Cookie absent ou expiré | Se reconnecter |
| `Connection refused` sur Redis | Service Redis arrêté | `docker compose start redis` |
| `Doctrine migration failed` | Schéma désynchronisé | `docker exec onlyroll-php php bin/console doctrine:migrations:migrate` |
| Build Vite qui plante | Mauvaise version Node | Vérifier Node.js 26+ |
| Tests E2E en échec | Stack Docker pas prête | Attendre le health check du backend |

---

## Épilogue

*Tu es maintenant prêt, aventurier. Le code t'attend, les bugs aussi. Mais tu as ce qu'il faut pour les affronter.*

*Souviens-toi : chaque Pull Request est une aventure. Chaque review est un conseil d'ami. Et chaque pipeline verte est une victoire.*

*Bonne chance. Et que les dés soient avec toi.*

---

**Contribuer à ce guide :** Si tu découvres une information manquante ou une commande qui ne fonctionne plus, ouvre une PR avec le type `docs` pour mettre ce grimoire à jour. Les aventuriers suivants te seront reconnaissants.
