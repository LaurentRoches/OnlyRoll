# Plan : Panel Administration + Profil Utilisateur

> Document de planification pour le développement de l'interface d'administration et de la gestion des profils utilisateurs pour OnlyRoll.
>
> **Normes de conformité :**
> - OWASP Top 10:2025
> - RGAA 4.1.2 (Accessibilité)
> - SEO / Sitemap dynamique
> - NIST Password Guidelines (CommonPasswords)

---

## Table des matières

1. [Objectif](#objectif)
2. [Normes de sécurité - OWASP Top 10:2025](#normes-de-sécurité---owasp-top-102025)
3. [Normes d'accessibilité - RGAA 4.1](#normes-daccessibilité---rgaa-41)
4. [SEO et Sitemap dynamique](#seo-et-sitemap-dynamique)
5. [Phases d'implémentation](#phases-dimplémentation)
6. [Vérification et tests](#vérification-et-tests)

---

## Objectif

Développer l'infrastructure admin complète avant de poursuivre le wiki DnD 5e :
- Panel admin avec gestion des users, dashboard, audit logs, CRUD wiki
- Page profil utilisateur basique
- **Respect strict des normes OWASP, RGAA et SEO**

---

## Normes de sécurité - OWASP Top 10:2025

Chaque fonctionnalité doit respecter les mesures de prévention OWASP :

| Code | Vulnérabilité | Mesures dans OnlyRoll |
|------|---------------|----------------------|
| **A01** | Broken Access Control | Vérification `ROLE_ADMIN` sur chaque endpoint, Voters Symfony, guards frontend |
| **A02** | Security Misconfiguration | Headers sécurisés (CSP, X-Frame-Options), CORS strict, env variables |
| **A03** | Supply Chain Failures | Audit `composer audit` et `npm audit`, lockfiles versionnés |
| **A04** | Cryptographic Failures | Argon2id pour passwords, JWT RS256, HTTPS obligatoire |
| **A05** | Injection | Doctrine ORM (requêtes paramétrées), validation DTOs, sanitization |
| **A06** | Insecure Design | Threat modeling, rate limiting, validation côté serveur |
| **A07** | Authentication Failures | MDP forts, CommonPasswords, lockout après échecs, audit logs |
| **A08** | Data Integrity Failures | SRI pour assets externes, signature des JWT, CSP |
| **A09** | Logging Failures | AuditLog complet, alertes sur actions critiques |
| **A10** | Exception Handling | Messages d'erreur génériques, logging détaillé côté serveur |

### Validation des mots de passe - CommonPasswords

**Intégration** : `wikimedia/common-passwords`

```bash
composer require wikimedia/common-passwords
```

**Implémentation** dans `backend/src/Validator/` :

```php
// NotCommonPassword.php - Contrainte personnalisée
#[Attribute(Attribute::TARGET_PROPERTY)]
class NotCommonPassword extends Constraint
{
    public string $message = 'Ce mot de passe est trop courant. Veuillez en choisir un plus sécurisé.';
}

// NotCommonPasswordValidator.php
use Wikimedia\CommonPasswords\CommonPasswords;

class NotCommonPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (CommonPasswords::isCommon($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
```

**Règles de validation mot de passe** :

```php
// PasswordChangeDTO.php
class PasswordChangeDTO
{
    #[Assert\NotBlank(message: 'Le mot de passe est requis')]
    #[Assert\Length(min: 12, minMessage: 'Le mot de passe doit contenir au moins 12 caractères')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        message: 'Le mot de passe doit contenir majuscule, minuscule, chiffre et caractère spécial'
    )]
    #[NotCommonPassword]
    public string $newPassword;
}
```

### Rate Limiting

**Configuration** dans `config/packages/rate_limiter.yaml` :

```yaml
framework:
    rate_limiter:
        login_limiter:
            policy: 'sliding_window'
            limit: 5
            interval: '15 minutes'
        password_change_limiter:
            policy: 'fixed_window'
            limit: 3
            interval: '1 hour'
        admin_action_limiter:
            policy: 'sliding_window'
            limit: 100
            interval: '1 hour'
```

### Headers de sécurité

**Créer** : `backend/src/EventSubscriber/SecurityHeadersSubscriber.php`

```php
public function onKernelResponse(ResponseEvent $event): void
{
    $response = $event->getResponse();

    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

    // CSP - Content Security Policy
    $response->headers->set('Content-Security-Policy',
        "default-src 'self'; " .
        "script-src 'self'; " .
        "style-src 'self' 'unsafe-inline'; " .
        "img-src 'self' data: blob:; " .
        "font-src 'self'; " .
        "connect-src 'self' wss://*.onlyroll.fr; " .
        "frame-ancestors 'none';"
    );
}
```

---

## Normes d'accessibilité - RGAA 4.1

Le RGAA 4.1.2 comporte **106 critères** répartis en **13 thématiques**. Voici les points critiques pour OnlyRoll :

### Thématiques prioritaires

| Thème | Critères clés | Application OnlyRoll |
|-------|---------------|---------------------|
| **1. Images** | Alt text obligatoire | Avatars, icônes, images wiki |
| **3. Couleurs** | Contraste 4.5:1 minimum | Vérifier palette TailwindCSS |
| **6. Liens** | Intitulé explicite | Pas de "cliquez ici" |
| **7. Scripts** | Compatible clavier | Tous les composants interactifs |
| **11. Formulaires** | Labels explicites, erreurs accessibles | Tous les formulaires admin/profil |
| **12. Navigation** | Skip links, landmarks ARIA | AdminLayout, navigation principale |

### Checklist Formulaires (Thème 11)

```vue
<!-- Exemple composant accessible -->
<template>
  <form @submit.prevent="onSubmit" novalidate>
    <!-- Skip link -->
    <a href="#main-content" class="sr-only focus:not-sr-only">
      Aller au contenu principal
    </a>

    <!-- Champ avec label explicite -->
    <div class="form-group">
      <label :for="pseudoId" class="form-label">
        Pseudo
        <span class="text-red-600" aria-hidden="true">*</span>
        <span class="sr-only">(obligatoire)</span>
      </label>
      <input
        :id="pseudoId"
        v-model="form.pseudo"
        type="text"
        :aria-describedby="pseudoErrorId"
        :aria-invalid="errors.pseudo ? 'true' : 'false'"
        :aria-required="true"
        autocomplete="username"
        class="form-input"
      />
      <!-- Message d'erreur accessible -->
      <p
        v-if="errors.pseudo"
        :id="pseudoErrorId"
        class="form-error"
        role="alert"
        aria-live="assertive"
      >
        {{ errors.pseudo }}
      </p>
    </div>

    <!-- Bouton explicite -->
    <button type="submit" :disabled="isLoading">
      <span v-if="isLoading" aria-hidden="true">...</span>
      <span :class="{ 'sr-only': isLoading }">Enregistrer les modifications</span>
    </button>
  </form>
</template>
```

### Checklist Navigation (Thème 12)

```vue
<!-- AdminLayout.vue - Structure accessible -->
<template>
  <div class="admin-layout">
    <!-- Skip links -->
    <nav aria-label="Liens d'accès rapide" class="skip-links">
      <a href="#main-nav" class="skip-link">Aller à la navigation</a>
      <a href="#main-content" class="skip-link">Aller au contenu</a>
    </nav>

    <!-- Header avec role banner -->
    <header role="banner">
      <h1 class="sr-only">Administration OnlyRoll</h1>
      <!-- ... -->
    </header>

    <!-- Navigation principale -->
    <nav id="main-nav" role="navigation" aria-label="Navigation administration">
      <ul role="list">
        <li>
          <router-link
            to="/admin"
            :aria-current="isCurrentRoute('/admin') ? 'page' : undefined"
          >
            <DashboardIcon aria-hidden="true" />
            <span>Tableau de bord</span>
          </router-link>
        </li>
        <!-- ... autres liens -->
      </ul>
    </nav>

    <!-- Contenu principal -->
    <main id="main-content" role="main" tabindex="-1">
      <router-view />
    </main>

    <!-- Fil d'Ariane -->
    <nav aria-label="Fil d'Ariane">
      <ol role="list">
        <li v-for="(crumb, index) in breadcrumbs" :key="crumb.path">
          <router-link
            v-if="index < breadcrumbs.length - 1"
            :to="crumb.path"
          >
            {{ crumb.name }}
          </router-link>
          <span v-else aria-current="page">{{ crumb.name }}</span>
        </li>
      </ol>
    </nav>
  </div>
</template>
```

### Composants accessibles à créer

**Créer** : `frontend/src/components/a11y/`

| Composant | Description |
|-----------|-------------|
| `AccessibleTable.vue` | Tableau avec headers scope, caption, aria-sort |
| `AccessibleModal.vue` | Focus trap, aria-modal, fermeture Escape |
| `AccessibleToast.vue` | role="alert", aria-live="polite" |
| `AccessiblePagination.vue` | Navigation clavier, aria-label |
| `AccessibleDropdown.vue` | Menu avec aria-expanded, arrow keys |
| `SkipLinks.vue` | Liens d'évitement |

### Configuration Pa11y (tests automatisés)

**Modifier** : `frontend/pa11y.config.js`

```javascript
module.exports = {
  defaults: {
    standard: 'WCAG2AA',
    runners: ['axe', 'htmlcs'],
    timeout: 30000,
    wait: 1000,
    ignore: [],
  },
  urls: [
    { url: 'http://localhost:5173/admin', name: 'Admin Dashboard' },
    { url: 'http://localhost:5173/admin/users', name: 'Admin Users' },
    { url: 'http://localhost:5173/profile', name: 'User Profile' },
    { url: 'http://localhost:5173/login', name: 'Login Page' },
  ],
};
```

---

## SEO et Sitemap dynamique

### Structure SEO Backend

**Créer** : `backend/src/Controller/SeoController.php`

```php
#[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
public function sitemap(
    GameRepository $gameRepository,
    // Futurs repos wiki
): Response
{
    $urls = [];

    // Pages statiques
    $staticPages = [
        ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => '/login', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => '/register', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => '/games', 'priority' => '0.8', 'changefreq' => 'daily'],
    ];

    foreach ($staticPages as $page) {
        $urls[] = $page;
    }

    // Pages wiki dynamiques (futur)
    // $spells = $spellRepository->findAll();
    // foreach ($spells as $spell) {
    //     $urls[] = [
    //         'loc' => '/wiki/spells/' . $spell->getSlug(),
    //         'lastmod' => $spell->getUpdatedAt()->format('Y-m-d'),
    //         'priority' => '0.7',
    //         'changefreq' => 'weekly',
    //     ];
    // }

    // Parties publiques
    $publicGames = $gameRepository->findPublicGames();
    foreach ($publicGames as $game) {
        $urls[] = [
            'loc' => '/games/' . $game->getId(),
            'lastmod' => $game->getUpdatedAt()->format('Y-m-d'),
            'priority' => '0.6',
            'changefreq' => 'weekly',
        ];
    }

    $response = new Response();
    $response->headers->set('Content-Type', 'application/xml');

    return $this->render('seo/sitemap.xml.twig', [
        'urls' => $urls,
        'hostname' => $this->getParameter('app.hostname'),
    ], $response);
}

#[Route('/robots.txt', name: 'robots', methods: ['GET'])]
public function robots(): Response
{
    $content = <<<EOT
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/
Disallow: /profile/

Sitemap: https://onlyroll.fr/sitemap.xml
EOT;

    return new Response($content, 200, ['Content-Type' => 'text/plain']);
}
```

**Créer** : `backend/templates/seo/sitemap.xml.twig`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{% for url in urls %}
    <url>
        <loc>{{ hostname }}{{ url.loc }}</loc>
        {% if url.lastmod is defined %}
        <lastmod>{{ url.lastmod }}</lastmod>
        {% endif %}
        <changefreq>{{ url.changefreq }}</changefreq>
        <priority>{{ url.priority }}</priority>
    </url>
{% endfor %}
</urlset>
```

### Meta tags Frontend (Vue)

**Créer** : `frontend/src/composables/useSeo.ts`

```typescript
import { useHead } from '@vueuse/head'

interface SeoOptions {
  title: string
  description: string
  canonical?: string
  ogImage?: string
  noindex?: boolean
}

export function useSeo(options: SeoOptions) {
  const baseTitle = 'OnlyRoll - VTT D&D 5e'
  const baseUrl = 'https://onlyroll.fr'

  useHead({
    title: `${options.title} | ${baseTitle}`,
    meta: [
      { name: 'description', content: options.description },
      { name: 'robots', content: options.noindex ? 'noindex, nofollow' : 'index, follow' },

      // Open Graph
      { property: 'og:title', content: options.title },
      { property: 'og:description', content: options.description },
      { property: 'og:type', content: 'website' },
      { property: 'og:url', content: options.canonical || baseUrl },
      { property: 'og:image', content: options.ogImage || `${baseUrl}/og-image.png` },
      { property: 'og:site_name', content: 'OnlyRoll' },
      { property: 'og:locale', content: 'fr_FR' },

      // Twitter Card
      { name: 'twitter:card', content: 'summary_large_image' },
      { name: 'twitter:title', content: options.title },
      { name: 'twitter:description', content: options.description },
    ],
    link: [
      { rel: 'canonical', href: options.canonical || baseUrl },
    ],
  })
}
```

**Exemple d'utilisation** :

```vue
<!-- AdminDashboardView.vue -->
<script setup lang="ts">
import { useSeo } from '@/composables/useSeo'

useSeo({
  title: 'Administration',
  description: 'Tableau de bord d\'administration OnlyRoll',
  noindex: true, // Pages admin non indexées
})
</script>
```

### Structure sémantique HTML

```vue
<!-- Structure SEO-friendly pour pages publiques -->
<template>
  <article itemscope itemtype="https://schema.org/Game">
    <header>
      <h1 itemprop="name">{{ game.title }}</h1>
      <p itemprop="description">{{ game.description }}</p>
    </header>

    <section aria-labelledby="details-heading">
      <h2 id="details-heading">Détails de la partie</h2>
      <dl>
        <dt>Maître du jeu</dt>
        <dd itemprop="author" itemscope itemtype="https://schema.org/Person">
          <span itemprop="name">{{ game.gameMaster.pseudo }}</span>
        </dd>
        <dt>Nombre de joueurs</dt>
        <dd>{{ game.currentPlayers }} / {{ game.maxPlayers }}</dd>
      </dl>
    </section>
  </article>
</template>
```

---

## Phases d'implémentation

### Phase 1 : Fondations Backend (Priorité haute)

#### 1.1 Modifications de l'entité User

**Fichier** : `backend/src/Entity/User.php`

```php
#[ORM\Column(name: 'user_is_active', type: Types::BOOLEAN)]
#[Groups(['user:read', 'admin:user:read'])]
private bool $isActive = true;

#[ORM\Column(name: 'user_deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
#[Groups(['admin:user:read'])]
private ?DateTimeImmutable $deletedAt = null;

#[ORM\Column(name: 'user_failed_login_attempts', type: Types::INTEGER)]
private int $failedLoginAttempts = 0;

#[ORM\Column(name: 'user_locked_until', type: Types::DATETIME_IMMUTABLE, nullable: true)]
private ?DateTimeImmutable $lockedUntil = null;
```

#### 1.2 Nouvelle entité AuditLog

**Créer** : `backend/src/Entity/AuditLog.php`

| Champ | Type | Description |
|-------|------|-------------|
| id | int | PK |
| performer | User (nullable) | Admin qui a fait l'action |
| targetUser | User (nullable) | User affecté |
| action | AuditAction (enum) | Type d'action |
| entityType | string | Type d'entité |
| entityId | int | ID de l'entité |
| details | JSON | Avant/après (sans données sensibles) |
| ipAddress | string | IP hashée (RGPD) |
| userAgent | string | User agent |
| createdAt | datetime | Timestamp |

#### 1.3 Validators sécurité

**Créer** : `backend/src/Validator/`

- `NotCommonPassword.php` + `NotCommonPasswordValidator.php`
- `SecurePassword.php` (combinaison de toutes les règles)

#### 1.4 Migration SQL

```sql
-- Modification table user
ALTER TABLE user
ADD COLUMN user_is_active BOOLEAN NOT NULL DEFAULT TRUE,
ADD COLUMN user_deleted_at DATETIME NULL,
ADD COLUMN user_failed_login_attempts INT NOT NULL DEFAULT 0,
ADD COLUMN user_locked_until DATETIME NULL,
ADD INDEX idx_user_active (user_is_active),
ADD INDEX idx_user_deleted (user_deleted_at);

-- Table audit_log
CREATE TABLE audit_log (
    log_id INT(11) NOT NULL AUTO_INCREMENT,
    log_user_id INT(11) NULL,
    log_target_user_id INT(11) NULL,
    log_action VARCHAR(50) NOT NULL,
    log_entity_type VARCHAR(50) NULL,
    log_entity_id INT(11) NULL,
    log_details JSON NULL,
    log_ip_address VARCHAR(64) NULL COMMENT 'Hashed IP for GDPR',
    log_user_agent VARCHAR(500) NULL,
    log_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    KEY idx_log_user (log_user_id),
    KEY idx_log_action (log_action),
    KEY idx_log_created (log_created_at),
    CONSTRAINT fk_audit_log_user FOREIGN KEY (log_user_id)
        REFERENCES user(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Phase 2 : Services Backend

#### 2.1 AuditLogService

**Créer** : `backend/src/Service/AuditLogService.php`

- `log(AuditAction, ?User, ?User, ?string, ?int, array, Request)`
- Hash IP address pour RGPD
- Ne jamais logger les mots de passe

#### 2.2 AdminUserService

**Créer** : `backend/src/Service/Admin/AdminUserService.php`

- `listUsers(UserFilterDTO): PaginatedResult`
- `updateUser(User, UserUpdateDTO): User`
- `softDelete(User): void`
- `restore(User): void`
- `lockAccount(User, int $minutes): void`
- `unlockAccount(User): void`

#### 2.3 ProfileService avec sécurité renforcée

**Créer** : `backend/src/Service/ProfileService.php`

```php
public function changePassword(User $user, PasswordChangeDTO $dto): void
{
    // Vérifier l'ancien mot de passe
    if (!$this->passwordHasher->isPasswordValid($user, $dto->currentPassword)) {
        $this->auditLogService->log(
            AuditAction::PASSWORD_CHANGE_FAILED,
            $user, $user, 'user', $user->getId(),
            ['reason' => 'invalid_current_password'],
            $this->requestStack->getCurrentRequest()
        );
        throw new InvalidPasswordException('Mot de passe actuel incorrect');
    }

    // Le nouveau mot de passe est validé via DTO (NotCommonPassword, etc.)
    $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->newPassword);
    $user->setPassword($hashedPassword);

    $this->entityManager->flush();

    $this->auditLogService->log(
        AuditAction::PASSWORD_CHANGE,
        $user, $user, 'user', $user->getId(),
        [], // Ne jamais logger le mot de passe
        $this->requestStack->getCurrentRequest()
    );
}
```

#### 2.4 SecurityHeadersSubscriber

**Créer** : `backend/src/EventSubscriber/SecurityHeadersSubscriber.php`

---

### Phase 3 : Controllers & API Endpoints

#### Admin Controllers

| Controller | Endpoints | Sécurité |
|------------|-----------|----------|
| AdminDashboardController | `GET /api/admin/dashboard/*` | ROLE_ADMIN, rate limit |
| AdminUserController | `GET/PUT/DELETE /api/admin/users/*` | ROLE_ADMIN, audit log |
| AdminAuditLogController | `GET /api/admin/audit-logs` | ROLE_ADMIN |
| SeoController | `GET /sitemap.xml`, `GET /robots.txt` | Public |

#### ProfileController

| Endpoint | Sécurité |
|----------|----------|
| `PUT /api/profile/password` | Rate limit (3/heure), audit log |
| `POST /api/profile/avatar` | Validation MIME, taille max 2MB |

---

### Phase 4 : Frontend - Structure accessible

#### 4.1 Layout Admin accessible

**Créer** : `frontend/src/layouts/AdminLayout.vue`

- Skip links
- Landmarks ARIA (banner, navigation, main)
- Focus management
- Breadcrumbs accessibles

#### 4.2 Composants accessibles

**Créer** dans `frontend/src/components/a11y/` :

- `AccessibleTable.vue`
- `AccessibleModal.vue`
- `AccessiblePagination.vue`
- `AccessibleToast.vue`

#### 4.3 Routes avec meta SEO

```typescript
{
  path: '/admin',
  component: AdminLayout,
  meta: {
    requiresAuth: true,
    requiresAdmin: true,
    seo: { noindex: true } // Pages admin non indexées
  },
  // ...
}
```

---

### Phase 5 : Frontend - Vues Admin accessibles

#### Dashboard
- Cards avec heading levels corrects (h2, h3)
- Graphiques avec alternatives textuelles
- Skip links vers sections

#### Gestion Users
- Tableau accessible (caption, th scope)
- Actions avec focus visible
- Confirmations dans modales accessibles

#### Audit Logs
- Tableau avec aria-sort
- Filtres avec labels
- Pagination accessible

---

### Phase 6 : Frontend - Profil Utilisateur

#### Formulaires accessibles
- Labels explicites liés aux inputs
- Messages d'erreur avec `aria-describedby`
- Indicateurs obligatoires accessibles
- Autocomplete attributes

#### Validation mot de passe côté client

```typescript
// frontend/src/utils/passwordValidation.ts
export interface PasswordStrength {
  score: number // 0-4
  feedback: string[]
  isValid: boolean
}

export function validatePassword(password: string): PasswordStrength {
  const feedback: string[] = []
  let score = 0

  if (password.length >= 12) score++
  else feedback.push('Au moins 12 caractères requis')

  if (/[a-z]/.test(password)) score++
  else feedback.push('Au moins une minuscule requise')

  if (/[A-Z]/.test(password)) score++
  else feedback.push('Au moins une majuscule requise')

  if (/\d/.test(password)) score++
  else feedback.push('Au moins un chiffre requis')

  if (/[@$!%*?&]/.test(password)) score++
  else feedback.push('Au moins un caractère spécial requis (@$!%*?&)')

  return {
    score: Math.min(score, 4),
    feedback,
    isValid: score >= 4,
  }
}
```

---

### Phase 7 : SEO & Sitemap

#### Backend
- SeoController avec sitemap.xml dynamique
- robots.txt
- Structured data (JSON-LD) pour pages publiques

#### Frontend
- Composable `useSeo()`
- Meta tags dynamiques
- Canonical URLs
- Open Graph / Twitter Cards

---

### Phase 8 : Entités SRD pour Wiki (préparation SEO)

Chaque entité wiki doit avoir :
- `slug` unique pour URLs SEO-friendly
- `metaDescription` pour balise meta
- `updatedAt` pour lastmod sitemap

---

## Fichiers critiques à modifier/créer

| Fichier | Action | Priorité |
|---------|--------|----------|
| `backend/src/Entity/User.php` | Modifier | Haute |
| `backend/src/Entity/AuditLog.php` | Créer | Haute |
| `backend/src/Validator/NotCommonPassword*.php` | Créer | Haute |
| `backend/src/EventSubscriber/SecurityHeadersSubscriber.php` | Créer | Haute |
| `backend/src/Controller/SeoController.php` | Créer | Moyenne |
| `backend/config/packages/rate_limiter.yaml` | Créer | Haute |
| `frontend/src/layouts/AdminLayout.vue` | Créer | Haute |
| `frontend/src/components/a11y/*.vue` | Créer | Haute |
| `frontend/src/composables/useSeo.ts` | Créer | Moyenne |
| `frontend/pa11y.config.js` | Modifier | Moyenne |

---

## Vérification et tests

### Tests sécurité

```bash
# Audit dépendances
composer audit
npm audit

# Tests PHPUnit avec couverture sécurité
docker compose exec backend php bin/phpunit --testsuite=security
```

### Tests accessibilité

```bash
# Pa11y automatisé
npm run test:a11y

# Axe DevTools (manuel dans navigateur)
# VoiceOver/NVDA (test lecteur d'écran)
```

### Tests SEO

```bash
# Lighthouse
npx lighthouse https://localhost:5173 --only-categories=seo,accessibility

# Validation sitemap
curl -s http://localhost/sitemap.xml | xmllint --noout -
```

### Checklist pré-déploiement

- [ ] Tous les endpoints admin requièrent `ROLE_ADMIN`
- [ ] Rate limiting actif sur login et password change
- [ ] Headers de sécurité présents (CSP, X-Frame-Options, etc.)
- [ ] Validation CommonPasswords fonctionnelle
- [ ] Audit logs enregistrent toutes les actions admin
- [ ] Score Pa11y >= 90% sur toutes les pages
- [ ] Sitemap.xml valide et à jour
- [ ] robots.txt bloque /admin/ et /api/
- [ ] Meta tags présents sur pages publiques

---

## Ressources

- [OWASP Top 10:2025](https://owasp.org/Top10/2025/)
- [RGAA 4.1.2 - Critères et tests](https://accessibilite.numerique.gouv.fr/methode/criteres-et-tests/)
- [wikimedia/common-passwords](https://github.com/wikimedia/mediawiki-libs-CommonPasswords)
- [Vue A11y Guide](https://vuejs.org/guide/best-practices/accessibility.html)
- [Schema.org](https://schema.org/)
