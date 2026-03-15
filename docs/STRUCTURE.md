# Structure du Projet OnlyRoll

**Stack technique** : Symfony 7.3 (PHP 8.3) + Vue.js 3.5 (TypeScript) + Docker

```
OnlyRoll/
│
├── docker-compose.yml              # Orchestration Docker (dev)
├── docker-compose.prod.yml         # Orchestration Docker (prod)
├── .env                            # Variables d'environnement
├── README.md                       # Documentation principale
│
├── backend/                        # API REST (Symfony + API Platform)
├── frontend/                       # SPA (Vue.js + Vite)
├── docker/                         # Configurations Docker
├── docs/                           # Documentation technique
├── tests/                          # Tests transverses (charge)
└── .github/                        # CI/CD GitHub Actions
```

---

## Docker (`docker/`)

```
docker/
├── mysql/
│   ├── init.sql                    # Script d'initialisation BDD
│   └── my.cnf                      # Configuration MySQL
├── nginx/
│   ├── nginx.conf                  # Reverse proxy (dev)
│   └── nginx.prod.conf             # Reverse proxy (prod)
├── redis/
│   └── redis.conf                  # Configuration cache
└── scripts/
    └── init-ssl.sh                 # Initialisation certificats SSL
```

### Services Docker

| Service     | Image                    | Port  | Description                    |
|-------------|--------------------------|-------|--------------------------------|
| `php-fpm`   | php:8.3-fpm-alpine       | -     | Backend Symfony                |
| `nginx`     | nginx:1.25-alpine        | 80    | Reverse proxy API              |
| `mysql`     | mysql:8.0                | 3306  | Base de données                |
| `redis`     | redis:7.2-alpine         | 6379  | Cache & sessions               |
| `frontend`  | node:20-alpine           | 5173  | Vite dev server                |
| `mercure`   | dunglas/mercure:v0.15    | 3000  | WebSocket (temps réel)         |

---

## Backend (`backend/`)

```
backend/
├── Dockerfile                                  # Multi-stage build (5 stages)
├── composer.json                               # Dépendances PHP
│
├── config/
│   ├── packages/
│   │   ├── api_platform.yaml                   # Configuration API Platform
│   │   ├── doctrine.yaml                       # ORM Doctrine
│   │   ├── security.yaml                       # Authentification & firewalls
│   │   ├── lexik_jwt_authentication.yaml       # JWT
│   │   ├── mercure.yaml                        # WebSocket
│   │   ├── nelmio_cors.yaml                    # CORS
│   │   └── rate_limiter.yaml                   # Rate limiting (OWASP A06)
│   ├── jwt/                                    # Clés JWT (private.pem, public.pem)
│   └── routes/                                 # Définitions des routes
│
├── src/
│   ├── Controller/                             # Endpoints API
│   │   ├── AuthController.php                  # Authentification
│   │   ├── GameController.php                  # Gestion des parties
│   │   ├── ChatController.php                  # Chat temps réel
│   │   ├── MapController.php                   # Cartes de jeu
│   │   ├── TokenController.php                 # Tokens (personnages)
│   │   ├── PresenceController.php              # Présence utilisateurs
│   │   ├── SecurityController.php              # Génération mots de passe (OWASP)
│   │   ├── ProfileController.php               # Gestion profil utilisateur
│   │   ├── WikiController.php                  # Wiki D&D 5e (public + favoris)
│   │   └── Admin/                              # Administration (ROLE_ADMIN)
│   │       ├── AdminDashboardController.php    # Dashboard admin
│   │       ├── AdminUserController.php         # CRUD utilisateurs
│   │       └── AdminAuditLogController.php     # Logs d'audit
│   │
│   ├── Entity/                                 # Entités Doctrine
│   │   ├── User.php                            # Utilisateur (+ sécurité: lockout, soft delete)
│   │   ├── Game.php                            # Partie
│   │   ├── GamePlayer.php                      # Joueur dans une partie
│   │   ├── GameMap.php                         # Carte
│   │   ├── GameToken.php                       # Token sur la carte
│   │   ├── GameMessage.php                     # Message chat
│   │   ├── AuditLog.php                        # Logs de sécurité (OWASP A09)
│   │   ├── Reference/                          # Données de référence D&D
│   │   │   ├── ContentSource.php               # Sources (PHB, DMG, MM, etc.)
│   │   │   ├── DamageType.php                  # Types de dégâts
│   │   │   ├── WeaponProperty.php              # Propriétés d'armes
│   │   │   ├── ItemCategory.php                # Catégories d'objets
│   │   │   ├── ItemRarity.php                  # Raretés d'objets
│   │   │   ├── CreatureSize.php                # Tailles de créature
│   │   │   ├── CreatureType.php                # Types de créature
│   │   │   ├── Alignment.php                   # Alignements
│   │   │   ├── SpellSchool.php                 # Écoles de magie
│   │   │   ├── ConditionType.php               # États (aveuglé, charmé, etc.)
│   │   │   ├── Skill.php                       # Compétences
│   │   │   └── Language.php                    # Langues
│   │   ├── Srd/                                # Contenu D&D (System Reference Document)
│   │   │   ├── Spell.php + SpellComponent/Class/DamageType  # Sorts
│   │   │   ├── SrdClass.php + Subclass/ClassFeature         # Classes
│   │   │   ├── Race.php + Subrace/RaceTrait/RaceSpeed/RaceLanguage  # Races
│   │   │   ├── Item.php + ItemWeapon/ItemArmor/ItemWeaponProperty/ItemWeaponDamage  # Équipement
│   │   │   ├── Monster.php + 10 entités liées               # Monstres
│   │   │   ├── Background.php + BackgroundSkill/Language/Equipment  # Historiques
│   │   │   └── Feat.php + FeatAbilityModifier/Prerequisite/Benefit  # Dons
│   │   └── Wiki/                               # Couche wiki i18n
│   │       ├── WikiCategory.php                # 7 catégories (sorts, races, classes, etc.)
│   │       ├── WikiCategoryTranslation.php     # Traductions catégories (EN/FR)
│   │       ├── WikiArticle.php                 # Lien SRD ↔ article wiki
│   │       ├── WikiArticleTranslation.php      # Titre + contenu Markdown localisé
│   │       └── WikiFavorite.php                # Favoris utilisateur (user_id + srd_table + srd_id)
│   │
│   ├── Service/                                # Logique métier
│   │   ├── GameService.php                     # Logique de jeu
│   │   ├── ChatService.php                     # Gestion du chat
│   │   ├── MapService.php                      # Manipulation des cartes
│   │   ├── TokenService.php                    # Gestion des tokens
│   │   ├── MercurePublisher.php                # Publication WebSocket
│   │   ├── FileUploader.php                    # Upload de fichiers
│   │   ├── PasswordGeneratorService.php        # Génération mots de passe sécurisés
│   │   ├── AuditLogService.php                 # Logging sécurisé (RGPD: hash IP)
│   │   ├── ProfileService.php                  # Gestion profil & changement MDP
│   │   ├── Import/                             # Import données 5etools
│   │   │   ├── EntryParser.php                 # Convertit entries 5etools → Markdown
│   │   │   ├── SpellImportMapper.php           # Mapping sorts
│   │   │   ├── RaceImportMapper.php            # Mapping races
│   │   │   ├── ClassImportMapper.php           # Mapping classes
│   │   │   ├── ItemImportMapper.php            # Mapping équipement
│   │   │   ├── BackgroundImportMapper.php      # Mapping historiques
│   │   │   ├── FeatImportMapper.php            # Mapping dons
│   │   │   └── MonsterImportMapper.php         # Mapping monstres
│   │   ├── Wiki/                               # Services wiki
│   │   │   └── WikiSrdSyncService.php          # Sync SRD → wiki_article + traduction EN
│   │   └── Admin/                              # Services administration
│   │       └── AdminUserService.php            # CRUD users, soft delete, lock/unlock
│   │
│   ├── DTO/                                    # Data Transfer Objects
│   │   ├── Auth/                               # DTOs authentification
│   │   ├── Game/                               # DTOs partie
│   │   ├── Chat/                               # DTOs chat
│   │   ├── Map/                                # DTOs carte
│   │   ├── Token/                              # DTOs token
│   │   ├── Security/                           # DTOs sécurité (génération MDP)
│   │   ├── Profile/                            # DTOs profil utilisateur
│   │   │   ├── PasswordChangeDTO.php           # Changement de mot de passe
│   │   │   └── ProfileUpdateDTO.php            # Mise à jour profil
│   │   └── Admin/                              # DTOs administration
│   │       ├── UserFilterDTO.php               # Filtres liste utilisateurs
│   │       ├── UserUpdateDTO.php               # Mise à jour utilisateur
│   │       └── AuditLogFilterDTO.php           # Filtres logs d'audit
│   │
│   ├── Command/Import/                         # Commandes d'import CLI
│   │   ├── ImportSpellsCommand.php             # app:import:spells
│   │   ├── ImportRacesCommand.php              # app:import:races
│   │   ├── ImportClassesCommand.php            # app:import:classes
│   │   ├── ImportItemsCommand.php              # app:import:items
│   │   ├── ImportBackgroundsCommand.php        # app:import:backgrounds
│   │   ├── ImportFeatsCommand.php              # app:import:feats
│   │   ├── ImportMonstersCommand.php           # app:import:monsters
│   │   ├── ImportSkillsCommand.php             # app:import:skills
│   │   ├── ImportConditionsCommand.php         # app:import:conditions
│   │   └── ImportAllCommand.php                # app:import:all (orchestrateur)
│   │
│   ├── Enum/                                   # Énumérations
│   │   ├── GameStatus.php                      # Statut partie (waiting, active, finished)
│   │   ├── PlayerRole.php                      # Rôle (gm, player)
│   │   ├── TokenType.php                       # Type token (character, monster, npc)
│   │   ├── MessageType.php                     # Type message (text, roll, system)
│   │   └── AuditAction.php                     # Actions auditables (OWASP A09, wiki favoris)
│   │
│   ├── Repository/                             # Requêtes Doctrine
│   │   ├── Srd/                                # Repositories SRD (findWithFilters + findByNameAndSource)
│   │   │   ├── SpellRepository.php             # Filtres niveau/école/dégâts/classe/source
│   │   │   ├── RaceRepository.php
│   │   │   ├── ClassRepository.php
│   │   │   ├── ItemRepository.php
│   │   │   ├── MonsterRepository.php           # Filtres type/taille/CR/source
│   │   │   ├── BackgroundRepository.php
│   │   │   └── FeatRepository.php
│   │   └── Wiki/
│   │       ├── WikiArticleRepository.php       # findBySrdEntity()
│   │       ├── WikiCategoryRepository.php
│   │       └── WikiFavoriteRepository.php      # isFavorited(), getFavoritedIds()
│   │
│   ├── EventSubscriber/                        # Event Subscribers
│   │   ├── AuthenticationSuccessSubscriber.php  # Cookie JWT + sliding session
│   │   ├── JwtCookieRefreshSubscriber.php       # Renouvellement auto cookie
│   │   └── SecurityHeadersSubscriber.php        # Headers OWASP (CSP, etc.)
│   │
│   ├── Validator/                              # Validateurs personnalisés
│   │   ├── NotCommonPassword.php               # Contrainte NIST
│   │   └── NotCommonPasswordValidator.php      # Validation wikimedia/common-passwords
│   │
│   ├── Exception/                              # Exceptions personnalisées
│   │   └── Profile/                            # Exceptions profil
│   │       ├── ProfileException.php            # Exception base profil
│   │       └── InvalidPasswordException.php    # Mot de passe invalide
│   │
│   └── Security/                               # Authentification JWT
│
├── migrations/                                 # Migrations BDD
├── src/
│   └── DataFixtures/
│       ├── LoadTestFixtures.php                # Fixtures pour tests de charge (k6)
│       └── FuzzFixtures.php                    # Fixtures pour tests de fuzzing
│
├── tests/
│   ├── Unit/                                   # Tests unitaires
│   │   ├── Entity/
│   │   │   ├── UserTest.php
│   │   │   ├── GameTest.php
│   │   │   ├── GameMapTest.php
│   │   │   ├── GamePlayerTest.php
│   │   │   ├── GameMessageTest.php
│   │   │   └── GameTokenTest.php
│   │   ├── Enum/
│   │   │   ├── GameStatusTest.php
│   │   │   ├── MapGridTypeTest.php
│   │   │   ├── TokenLayerTest.php
│   │   │   └── TokenTypeTest.php
│   │   ├── EventSubscriber/
│   │   │   └── AuthenticationSuccessSubscriberTest.php
│   │   ├── Repository/
│   │   │   ├── UserRepositoryTest.php
│   │   │   ├── GameRepositoryTest.php
│   │   │   ├── GameMapRepositoryTest.php
│   │   │   ├── GameMessageRepositoryTest.php
│   │   │   ├── GamePlayerRepositoryTest.php
│   │   │   └── GameTokenRepositoryTest.php
│   │   ├── Security/
│   │   │   └── JwtCookieAuthenticatorTest.php
│   │   └── Service/
│   │       ├── AuditLogServiceTest.php
│   │       ├── ChatServiceTest.php
│   │       ├── DtoValidatorServiceTest.php
│   │       ├── FileUploaderTest.php
│   │       ├── GameServiceTest.php
│   │       ├── MapServiceTest.php
│   │       ├── MercurePublisherTest.php
│   │       ├── MercureTokenServiceTest.php
│   │       ├── ProfileServiceTest.php
│   │       ├── TokenServiceTest.php
│   │       └── Admin/
│   │           └── AdminUserServiceTest.php
│   ├── Functional/                             # Tests d'intégration
│   │   └── Controller/
│   │       ├── AuthControllerTest.php
│   │       ├── ChatControllerTest.php
│   │       ├── GameControllerTest.php
│   │       ├── HealthControllerTest.php
│   │       ├── MapControllerTest.php
│   │       ├── PresenceControllerTest.php
│   │       ├── ProfileControllerTest.php
│   │       ├── TokenControllerTest.php
│   │       └── Admin/
│   │           ├── AdminDashboardControllerTest.php
│   │           ├── AdminUserControllerTest.php
│   │           └── AdminAuditLogControllerTest.php
│   └── Fuzzing/                                # Tests de fuzzing (PHPUnit)
│       ├── FuzzPayloadProvider.php             # Générateur de payloads malveillants
│       ├── AuthFuzzTest.php                    # Fuzzing endpoints auth
│       ├── ChatFuzzTest.php                    # Fuzzing endpoints chat
│       ├── GameFuzzTest.php                    # Fuzzing endpoints parties
│       ├── PathFuzzTest.php                    # Fuzzing traversée de chemins
│       └── TokenFuzzTest.php                  # Fuzzing endpoints tokens
│
└── public/
    └── uploads/                                # Fichiers uploadés
        └── maps/                               # Images des cartes
```

---

## Frontend (`frontend/`)

```
frontend/
├── Dockerfile                      # Multi-stage build
├── package.json                    # Dépendances Node
├── vite.config.ts                  # Configuration Vite
├── tailwind.config.js              # Configuration TailwindCSS
├── playwright.config.ts            # Tests E2E
│
├── src/
│   ├── main.ts                     # Point d'entrée
│   ├── App.vue                     # Composant racine
│   │
│   ├── router/
│   │   └── index.ts                # Définition des routes
│   │
│   ├── layouts/
│   │   ├── AuthLayout.vue          # Layout pages d'auth
│   │   └── AdminLayout.vue         # Layout administration
│   │
│   ├── views/                      # Pages
│   │   ├── HomeView.vue            # Page d'accueil
│   │   ├── NotFoundView.vue        # Page 404
│   │   ├── auth/
│   │   │   ├── LoginView.vue       # Connexion
│   │   │   ├── RegisterView.vue    # Inscription
│   │   │   └── RegisterSuccessView.vue  # Confirmation inscription
│   │   ├── dashboard/
│   │   │   └── DashboardView.vue   # Tableau de bord
│   │   ├── profile/
│   │   │   └── ProfileView.vue     # Profil utilisateur
│   │   ├── games/
│   │   │   ├── GameListView.vue    # Liste des parties (scroll infini)
│   │   │   └── GamePlayView.vue    # Table de jeu (carte + chat + dés)
│   │   ├── wiki/                   # Wiki D&D 5e (public)
│   │   │   ├── WikiHomeView.vue    # Accueil : grille 7 catégories + recherche
│   │   │   ├── WikiCategoryView.vue # Liste paginée + filtres sidebar + scroll infini
│   │   │   └── WikiDetailView.vue  # Détail entité + calculateur dégâts (sorts)
│   │   └── admin/
│   │       ├── AdminDashboardView.vue   # Dashboard admin
│   │       ├── AdminUsersView.vue       # Gestion utilisateurs
│   │       └── AdminAuditLogsView.vue   # Logs d'audit
│   │
│   ├── components/                 # Composants réutilisables
│   │   ├── a11y/                   # Accessibilité (RGAA 4.1)
│   │   │   ├── AccessibleModal.vue      # Dialog avec focus trap
│   │   │   ├── AccessiblePagination.vue # Pagination accessible
│   │   │   ├── AccessibleTable.vue      # Tableau paginé accessible
│   │   │   └── index.ts
│   │   ├── auth/
│   │   │   ├── LoginForm.vue       # Formulaire connexion
│   │   │   └── RegisterForm.vue    # Formulaire inscription
│   │   ├── common/
│   │   │   ├── FeatureCard.vue     # Carte fonctionnalité (accueil)
│   │   │   ├── UserProfileBadge.vue # Avatar + menu profil
│   │   │   └── KonamiVictory.vue   # Easter egg Konami
│   │   ├── dashboard/
│   │   │   ├── DashboardCard.vue   # Carte dashboard
│   │   │   └── DashboardNav.vue    # Navigation dashboard
│   │   ├── profile/
│   │   │   ├── AvatarUploader.vue  # Upload avatar
│   │   │   └── PasswordChangeForm.vue  # Formulaire changement MDP
│   │   ├── wiki/                   # Composants wiki
│   │   │   ├── WikiCategoryCard.vue     # Carte catégorie (grille accueil)
│   │   │   ├── WikiSpellCard.vue        # Carte sort (niveau, école, composants)
│   │   │   ├── WikiItemCard.vue         # Carte générique (race/classe/monstre/…)
│   │   │   ├── WikiFilters.vue          # Panneau filtres (sidebar/mobile)
│   │   │   ├── WikiFavoriteButton.vue   # Bouton étoile (⭐/☆, redirige si non connecté)
│   │   │   └── SpellDamageCalculator.vue # Calculateur min/moy/max par niveau de slot
│   │   └── game/
│   │       ├── GameCard.vue             # Carte de partie (liste)
│   │       ├── GameHeader.vue           # En-tête table de jeu
│   │       ├── GameMap.vue              # Carte tactique interactive (canvas)
│   │       ├── ChatPanel.vue            # Chat temps réel (scroll infini ascendant)
│   │       ├── PlayersList.vue          # Liste des joueurs connectés
│   │       ├── MapToolbar.vue           # Outils de carte (GM)
│   │       ├── EmptyMapState.vue        # Placeholder carte vide
│   │       ├── DiceRoller.vue           # Lanceur de dés (avantage/désavantage/super-avantage)
│   │       ├── DiceResultDisplay.vue    # Affichage résultat dés (formule, jets, total)
│   │       ├── SkeletonCard.vue         # Placeholder chargement carte de partie
│   │       ├── SkeletonMessage.vue      # Placeholder chargement message chat
│   │       ├── CreateGameModal.vue      # Modale création de partie
│   │       ├── JoinGameModal.vue        # Modale rejoindre une partie
│   │       ├── CreateTokenModal.vue     # Modale création token
│   │       ├── EditTokenModal.vue       # Modale édition token
│   │       ├── EditMapModal.vue         # Modale édition carte
│   │       └── UploadMapModal.vue       # Modale upload image de carte
│   │
│   ├── stores/                     # État (Pinia)
│   │   ├── auth.ts                 # Authentification
│   │   ├── game.ts                 # Parties (liste + partie courante, pagination serveur)
│   │   ├── mapStore.ts             # État de la carte tactique
│   │   ├── chatStore.ts            # Messages chat (historique paginé)
│   │   ├── presenceStore.ts        # Présence utilisateurs en ligne
│   │   └── wikiStore.ts            # État wiki (catégories, liste, détail, favoris)
│   │
│   ├── composables/                # Logique réutilisable
│   │   ├── useAuth.ts              # Gestion auth (wrapper store + utilitaires erreurs)
│   │   ├── useMercure.ts           # Abonnement WebSocket/SSE
│   │   ├── useFormValidation.ts    # Validation de formulaires
│   │   ├── usePagination.ts        # Pagination classique (page/totalPages)
│   │   ├── useInfiniteScroll.ts    # Scroll infini (IntersectionObserver, réutilisable wiki)
│   │   ├── usePasswordGenerator.ts # Génération + évaluation mots de passe
│   │   ├── useSeo.ts               # Gestion balises meta (OG, Twitter, JSON-LD)
│   │   └── useKonamiCode.ts        # Détection séquence Konami (easter egg)
│   │
│   ├── services/                   # Appels API
│   │   ├── mercure.ts              # Service WebSocket/SSE (Mercure)
│   │   └── api/
│   │       ├── apiClient.ts        # Client HTTP Axios (intercepteurs JWT)
│   │       ├── authApi.ts          # API authentification
│   │       ├── gameApi.ts          # API parties (pagination serveur)
│   │       ├── mapApi.ts           # API cartes
│   │       ├── chatApi.ts          # API chat (options before/after pour historique)
│   │       ├── tokenApi.ts         # API tokens (personnages sur carte)
│   │       ├── presenceApi.ts      # API présence
│   │       ├── securityApi.ts      # API sécurité (génération MDP)
│   │       ├── profileApi.ts       # API profil utilisateur
│   │       ├── adminApi.ts         # API administration (dashboard, users, audit)
│   │       ├── wikiApi.ts          # API wiki D&D 5e (sorts, races, monstres, favoris…)
│   │       └── index.ts            # Export centralisé
│   │
│   ├── types/                      # Types TypeScript
│   │   ├── game.ts                 # Entités jeu (Game, GameMessage, DiceResult, etc.)
│   │   ├── auth.ts                 # Types auth (User, LoginCredentials, etc.)
│   │   ├── errors.ts               # Types erreurs
│   │   ├── websocket.ts            # Types événements Mercure
│   │   └── wiki.ts                 # Types wiki (SpellDetail, MonsterDetail, WikiFavorite…)
│   │
│   └── utils/
│       ├── logger.ts               # Utilitaire de logging
│       └── errorHelpers.ts         # Extraction messages d'erreur
│
├── tests/
│   ├── unit/                       # Tests Vitest
│   │   ├── components/
│   │   ├── composables/
│   │   ├── stores/
│   │   └── services/
│   │       └── api/
│   │           ├── profileApi.spec.ts   # Tests API profil
│   │           └── adminApi.spec.ts     # Tests API admin
│   └── e2e/                        # Tests Playwright
│
└── public/
    └── sounds/                     # Sons (dés, notifications)
```

---

## Documentation (`docs/`)

```
docs/
├── Specs.md                        # Spécifications fonctionnelles
├── user_stories.md                 # User stories
├── process_flows.md                # Diagrammes de flux
├── websocket_events.md             # Documentation WebSocket
│
├── api/                            # Documentation API (Swagger)
│   ├── swagger_auth.yaml
│   ├── swagger_game.yaml
│   ├── swagger_actions.yaml
│   └── swagger_profile.yaml
│
├── database/
│   ├── OnlyRoll_Database.sql       # Schéma BDD
│   └── MCD.png                     # MCD
│
├── documentation_architecture/
│   ├── backend_architecture_doc.md
│   ├── frontend_architecture_doc.md
│   ├── docker_architecture_doc.md
│   └── websocket_doc.md
│
└── maquettes/                      # Maquettes UI (desktop & mobile)
```

---

## Tests de charge (`tests/load/`)

```
tests/load/
├── RESULTS.md                      # Résultats des scénarios de charge
├── data/
│   └── test-users.json             # Jeu de données utilisateurs (k6)
├── helpers/
│   └── auth.js                     # Helper d'authentification JWT pour k6
└── scripts/
    ├── 01-auth-load.js             # Scénario : authentification (login/register)
    ├── 02-games-list-load.js       # Scénario : liste des parties
    ├── 03-chat-load.js             # Scénario : envoi de messages chat
    ├── 04-join-game-load.js        # Scénario : rejoindre une partie
    └── 05-mixed-scenario.js        # Scénario mixte (trafic réaliste)
```

| Scénario | Outil | VUs | Description |
|----------|-------|-----|-------------|
| Auth load | k6 | 50 | Connexion / inscription sous charge |
| Games list | k6 | 100 | Parcours de la liste des parties |
| Chat load | k6 | 75 | Envoi massif de messages |
| Join game | k6 | 50 | Rejoindre et interagir dans une partie |
| Mixed | k6 | 200 | Simulation de trafic réel multi-endpoints |

---

## CI/CD (`.github/workflows/`)

```
.github/workflows/
├── ci.yml                          # Intégration continue
│   ├── PHP CS Fixer (lint)
│   ├── PHPStan (analyse statique)
│   ├── PHPUnit (tests backend)
│   ├── ESLint + TypeScript
│   ├── Vitest (tests frontend)
│   ├── Playwright (tests E2E)
│   └── Coverage reports
│
└── deploy.yml                      # Déploiement continu
    ├── Build production
    ├── Tests de validation
    ├── Build images Docker
    ├── Déploiement VPS (Hostinger)
    ├── Migrations BDD
    └── Health checks
```

> **Note :** Les tests de charge (k6) ne sont pas exécutés en CI automatique — ils se lancent manuellement contre un environnement dédié. Les tests de fuzzing (PHPUnit) sont intégrés dans la pipeline CI avec `phpunit --testsuite Fuzzing`.

```
```

---

## Sécurité OWASP Top 10:2025

Le projet implémente les mesures de prévention OWASP suivantes :

| Code | Vulnérabilité | Implémentation |
|------|---------------|----------------|
| **A02** | Security Misconfiguration | `SecurityHeadersSubscriber.php` (CSP, X-Frame-Options, HSTS) |
| **A06** | Insecure Design | `rate_limiter.yaml` (login: 5/15min, password: 3/h, admin: 100/h) |
| **A07** | Authentication Failures | `User.php` (lockout), `NotCommonPasswordValidator.php`, sliding session |
| **A09** | Logging Failures | `AuditLog.php`, `AuditAction.php`, `AuditLogService.php` |

### Tests de sécurité

| Type | Outil | Fichiers | Couverture |
|------|-------|----------|------------|
| **Fuzzing** | PHPUnit | `tests/Fuzzing/` | Auth, Chat, Game, Token, Path traversal |
| **Charge** | k6 | `tests/load/` | Auth, Games, Chat, Join, Scénario mixte |

### Conformité RGPD

| Élément | Implémentation |
|---------|----------------|
| **Hachage IP** | `AuditLogService::hashIpAddress()` - SHA-256 pour anonymisation |
| **Données sensibles** | Sanitisation automatique (password, token, secret, api_key) |
| **Soft Delete** | Suppression logique des utilisateurs (conservation audit) |
| **Droit à l'oubli** | Anonymisation des données utilisateur via admin panel |

### Packages de sécurité

| Package | Usage |
|---------|-------|
| `wikimedia/common-passwords` | Validation NIST des mots de passe courants |
| `symfony/rate-limiter` | Protection brute force |
| `lexik/jwt-authentication-bundle` | Authentification JWT RS256 |

### Politique de mots de passe (NIST SP 800-63B)

- Minimum **12 caractères**
- Au moins 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial
- Vérification contre les mots de passe courants
- Service de génération sécurisée (`PasswordGeneratorService.php`)

---

## Administration (`ROLE_ADMIN`)

### Endpoints API Admin

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/admin/dashboard/stats` | GET | Statistiques globales |
| `/api/admin/dashboard/recent-activity` | GET | Activité récente |
| `/api/admin/users` | GET | Liste utilisateurs (pagination, filtres) |
| `/api/admin/users/{id}` | GET | Détail utilisateur |
| `/api/admin/users/{id}` | PUT | Modifier utilisateur |
| `/api/admin/users/{id}` | DELETE | Soft delete utilisateur |
| `/api/admin/users/{id}/restore` | POST | Restaurer utilisateur |
| `/api/admin/users/{id}/lock` | POST | Verrouiller compte |
| `/api/admin/users/{id}/unlock` | POST | Déverrouiller compte |
| `/api/admin/users/statistics` | GET | Statistiques utilisateurs |
| `/api/admin/audit-logs` | GET | Liste logs d'audit |
| `/api/admin/audit-logs/{id}` | GET | Détail log |
| `/api/admin/audit-logs/user/{userId}` | GET | Logs par utilisateur |
| `/api/admin/audit-logs/statistics` | GET | Statistiques audit |
| `/api/admin/audit-logs/actions` | GET | Actions disponibles |

### Endpoints API Profil

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/profile` | GET | Profil utilisateur |
| `/api/profile` | PUT | Modifier profil |
| `/api/profile/password` | PUT | Changer mot de passe (rate limited) |
| `/api/profile/avatar` | POST | Upload avatar |
| `/api/profile/avatar` | DELETE | Supprimer avatar |
