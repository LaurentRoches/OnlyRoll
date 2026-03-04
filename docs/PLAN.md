# OnlyRoll - Plan de Développement Wiki D&D + i18n

## État Actuel

**Aucune table n'est déployée.** Le fichier `/docs/database/OnlyRoll_Database.sql` est une spécification théorique servant de guide.

À créer :
- Tables de référence (damage_type, spell_school, condition_type, etc.)
- Tables contenu D&D (srd_spell, srd_monster, srd_item, srd_race, srd_class, etc.)
- Tables i18n (translation_locale)
- Tables Wiki (wiki_category, wiki_article, wiki_article_translation, etc.)

> **Accord WotC :** L'intégralité du contenu D&D est utilisable. Les données sources sont disponibles localement au format JSON (structure 5etools) dans `/docs/SRD/`. La table `content_source` est centrale — toutes les entrées référencent leur source (PHB, XGE, TCE, DMG, etc.).

## Documentation Projet

| Ressource | Chemin |
|-----------|--------|
| Structure projet | `/docs/STRUCTURE.md` |
| Spécification BDD | `/docs/database/OnlyRoll_Database.sql` |
| Maquettes UI | `/docs/maquettes/` |
| Données JSON D&D | `/docs/SRD/` |

## Conformité OWASP ASVS 5.0

**Objectif :** Niveau 1 minimum, Niveau 2 idéal

**Déjà implémenté :**
- Rate limiting (`rate_limiter.yaml`)
- Headers sécurité (`SecurityHeadersSubscriber.php`)
- Audit logs (`AuditLogService.php`)
- Password NIST (`NotCommonPasswordValidator.php`)
- JWT HttpOnly

---

## Architecture de peuplement de la base de données

Le contenu D&D est volumineux et structuré en sources multiples. L'approche retenue distingue trois couches :

### Fixtures Doctrine — groupe `reference`

Réservées aux **tables de référence statiques** : petites, immuables, sans logique de transformation.

```bash
php bin/console doctrine:fixtures:load --group=reference
```

```
DataFixtures/ReferenceFixtures.php
└── damage_type, spell_school, condition_type, alignment,
    creature_size, creature_type, item_category, item_rarity,
    weapon_property, language, content_source
```

> Ces données n'évoluent jamais et n'ont pas de source JSON externe — elles sont saisies directement dans la fixture.

### Commandes d'import Symfony — contenu D&D

Réservées à tout le **contenu de jeu** issu des fichiers JSON. Chaque commande est **idempotente** (upsert), **rejouable** en CI/CD, et supporte `--dry-run`.

```bash
# Import complet
php bin/console app:import:all

# Import par type
php bin/console app:import:skills
php bin/console app:import:spells              # lit l'index.json, importe toutes les sources
php bin/console app:import:spells --source=PHB # cible une source précise
php bin/console app:import:monsters
php bin/console app:import:races
php bin/console app:import:classes
php bin/console app:import:items
php bin/console app:import:backgrounds
php bin/console app:import:feats
php bin/console app:import:conditions
```

Structure des commandes :

```
src/Command/Import/
├── ImportAllCommand.php          # Orchestrateur, appelle les autres dans l'ordre
├── ImportSkillsCommand.php
├── ImportSpellsCommand.php       # Lit spells/index.json → itère les fichiers sources
├── ImportMonstersCommand.php
├── ImportRacesCommand.php
├── ImportClassesCommand.php
├── ImportItemsCommand.php
├── ImportBackgroundsCommand.php
├── ImportFeatsCommand.php
└── ImportConditionsCommand.php
```

Chaque commande suit le même patron :

```php
// Lecture JSON → transformation → upsert via Repository
// INSERT ... ON DUPLICATE KEY UPDATE (idempotent)
// --source=PHB  : filtre sur un fichier source
// --dry-run     : valide sans écrire
// Progress bar  : feedback visuel pendant l'import
```

Gestion des doublons multi-sources (ex. `skills.json` contient PHB + XPHB pour la même compétence) : source canonique = PHB pour la compatibilité historique, XPHB ignoré ou stocké comme entrée distincte selon la stratégie retenue à l'implémentation.

### Fixtures Doctrine — groupe `test`

Comportement inchangé. Données de test isolées pour PHPUnit et Playwright.

```bash
php bin/console doctrine:fixtures:load --group=test
```

### Initialisation complète (Makefile / CI)

```bash
make db-setup:
  doctrine:migrations:migrate
  doctrine:fixtures:load --group=reference
  app:import:all
```

---

## Étape 1 : Tables de Référence (3-4h)

Créer les tables lookup nécessaires au contenu D&D et Wiki.

### 1.1 Migration Doctrine

```bash
php bin/console make:migration
```

### 1.2 Tables à créer

Référence : `/docs/database/OnlyRoll_Database.sql` lignes 17-148

- [ ] `damage_type` - Types de dégâts (slashing, fire, etc.)
- [ ] `weapon_property` - Propriétés d'armes (finesse, heavy, etc.)
- [ ] `item_category` - Catégories d'objets (Weapon, Armor, etc.) **avec category_abbreviation**
- [ ] `item_rarity` - Raretés (common, rare, legendary, etc.)
- [ ] `creature_size` - Tailles (Tiny, Medium, Huge, etc.)
- [ ] `creature_type` - Types (humanoid, undead, dragon, etc.)
- [ ] `alignment` - Alignements (lawful good, chaotic evil, etc.)
- [ ] `spell_school` - Écoles de magie (Abjuration, Evocation, etc.)
- [ ] `condition_type` - Conditions (blinded, charmed, etc.)
- [ ] `skill` - Compétences (Acrobatics, Perception, etc.)
- [ ] `language` - Langues (Common, Elvish, etc.)
- [ ] `content_source` - Sources **(PHB, DMG, XGE, TCE, XPHB, etc. — toutes les sources WotC)**

### 1.3 Entités Doctrine

```
backend/src/Entity/Reference/
├── DamageType.php
├── WeaponProperty.php
├── ItemCategory.php
├── ItemRarity.php
├── CreatureSize.php
├── CreatureType.php
├── Alignment.php
├── SpellSchool.php
├── ConditionType.php
├── Skill.php
├── Language.php
└── ContentSource.php
```

### 1.4 Fixtures (données initiales)

- [ ] Créer `DataFixtures/ReferenceFixtures.php` (groupe `reference`) avec toutes les valeurs D&D

---

## Étape 2 : Tables Contenu D&D (10-12h)

### 2.1 Tables principales

Référence : `/docs/database/OnlyRoll_Database.sql` lignes 336-1000+

**Races :**
- [ ] `srd_race` + `srd_subrace`
- [ ] `race_ability_modifier` + `race_trait` + `race_language`
- [ ] `race_speed` (swim, fly, burrow, climb)
- [ ] `race_proficiency` (armes, armures, outils raciaux)
- [ ] `subrace_ability_modifier` + `subrace_trait`

**Classes :**
- [ ] `srd_class` + `srd_subclass`
- [ ] `class_feature` + `class_spell_slots` + `subclass_feature`
- [ ] `class_saving_throw_proficiency`
- [ ] `class_armor_proficiency` + `class_weapon_proficiency` + `class_tool_proficiency`
- [ ] `class_skill_option` + `class_starting_equipment`
- [ ] `subclass_spell`

**Sorts :**
- [ ] `srd_spell` + `spell_class` + `spell_damage`
- [ ] `spell_component` (V/S/M détaillés avec coût et consommable)

**Objets :**
- [ ] `srd_item` + `item_weapon_property` + `item_weapon_damage`
- [ ] `item_weapon` (catégorie simple/martial, portées)
- [ ] `item_armor` (type, AC base, max dex, str requirement, stealth disadvantage)

**Monstres :**
- [ ] `srd_monster` + `monster_action` + `monster_trait` + `monster_skill`
- [ ] `monster_saving_throw` + `monster_sense` + `monster_spell`
- [ ] `monster_damage_resistance` + `monster_condition_immunity`
- [ ] `monster_action_damage`
- [ ] `monster_subtype` (orc, goblinoid, shapechanger, etc.)
- [ ] `monster_language` (normalisé avec FK vers language)
- [ ] `monster_environment` (arctic, desert, forest, etc.)

**Backgrounds :**
- [ ] `srd_background` + `background_skill` + `background_language`
- [ ] `background_tool` + `background_equipment`

**Dons :**
- [ ] `srd_feat` + `feat_ability_modifier`
- [ ] `feat_prerequisite` (prérequis structurés : ability_score, race, class, level)
- [ ] `feat_benefit` (avantages mécaniques : skill, save, armor, weapon, spell, etc.)

### 2.2 Entités Doctrine

```
backend/src/Entity/Srd/
├── Race.php
├── Subrace.php
├── RaceSpeed.php
├── RaceProficiency.php
├── SrdClass.php
├── Subclass.php
├── Spell.php
├── SpellComponent.php
├── Item.php
├── ItemWeapon.php
├── ItemArmor.php
├── Monster.php
├── MonsterSubtype.php
├── MonsterLanguage.php
├── MonsterEnvironment.php
├── Background.php
├── Feat.php
├── FeatPrerequisite.php
└── FeatBenefit.php
```

### 2.3 Repositories

```
backend/src/Repository/Srd/
├── SpellRepository.php
├── MonsterRepository.php
├── ItemRepository.php
├── RaceRepository.php
├── ClassRepository.php
├── BackgroundRepository.php
└── FeatRepository.php
```

---

## Étape 3 : Commandes d'import D&D (6-8h)

> **Voir section "Architecture de peuplement"** pour le détail du patron et des options CLI.

### 3.1 Structure

```
src/Command/Import/
├── ImportAllCommand.php
├── ImportSkillsCommand.php
├── ImportSpellsCommand.php
├── ImportMonstersCommand.php
├── ImportRacesCommand.php
├── ImportClassesCommand.php
├── ImportItemsCommand.php
├── ImportBackgroundsCommand.php
├── ImportFeatsCommand.php
└── ImportConditionsCommand.php
```

### 3.2 Service de mapping JSON → Entité

```
src/Service/Import/
├── SpellImportMapper.php
├── MonsterImportMapper.php
├── ItemImportMapper.php
└── ...
```

Chaque mapper transforme la structure 5etools vers les entités Doctrine, résout les FK (ex. `spell_school_id` depuis le code texte `"Evocation"`), et retourne une entité prête à persister.

### 3.3 Checklist

- [ ] `ImportSpellsCommand` lit `spells/index.json` et itère les fichiers sources
- [ ] Upsert idempotent sur chaque commande (clé unique : `name` + `source`)
- [ ] Option `--source=PHB` disponible sur toutes les commandes avec sources multiples
- [ ] Option `--dry-run` disponible sur toutes les commandes
- [ ] Progress bar Symfony sur tous les imports
- [ ] Commande `app:import:all` orchestratrice avec ordre de dépendance respecté

---

## Étape 4 : Infrastructure i18n (6-8h)

### 4.1 Table locale

```sql
CREATE TABLE translation_locale (
    locale_id INT(11) NOT NULL AUTO_INCREMENT,
    locale_code VARCHAR(5) NOT NULL,
    locale_name VARCHAR(50) NOT NULL,
    locale_native_name VARCHAR(50) NOT NULL,
    locale_is_default BOOLEAN NOT NULL DEFAULT FALSE,
    locale_is_active BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (locale_id),
    UNIQUE KEY uk_locale_code (locale_code)
);

INSERT INTO translation_locale VALUES
(1, 'en', 'English', 'English', TRUE, TRUE),
(2, 'fr', 'French', 'Français', FALSE, TRUE);
```

### 4.2 Modifier table user

```sql
ALTER TABLE user ADD COLUMN user_locale VARCHAR(5) NOT NULL DEFAULT 'en';
```

### 4.3 Backend

- [ ] `src/Entity/TranslationLocale.php`
- [ ] `src/Service/LocaleService.php`
- [ ] `src/EventSubscriber/LocaleSubscriber.php`
- [ ] `translations/messages.en.yaml`
- [ ] `translations/messages.fr.yaml`

### 4.4 Frontend

- [ ] Installer `vue-i18n@9`
- [ ] `src/locales/en.json`
- [ ] `src/locales/fr.json`
- [ ] `src/plugins/i18n.ts`
- [ ] `src/composables/useLocale.ts`
- [ ] `src/components/common/LanguageSwitcher.vue`

---

## Étape 5 : Tables Wiki (3-4h)

### 5.1 Architecture

Le Wiki est une couche de présentation i18n au-dessus du contenu D&D :

```
wiki_article.article_srd_table = 'srd_spell'
wiki_article.article_srd_id = 42
→ Traductions dans wiki_article_translation (FR/EN)
```

### 5.2 Tables à créer

```sql
-- Catégories
wiki_category
wiki_category_translation

-- Articles
wiki_article (avec article_srd_table, article_srd_id)
wiki_article_translation (contenu Markdown FR/EN)

-- Tags
wiki_tag
wiki_tag_translation
wiki_article_tag

-- Utilisateur
wiki_favorite
wiki_article_revision
```

### 5.3 Données initiales catégories

9 catégories : rules, races, classes, spells, monsters, items, backgrounds, feats, conditions

---

## Étape 6 : Backend Wiki (12-14h)

### 6.1 Entités

```
src/Entity/Wiki/
├── WikiCategory.php
├── WikiCategoryTranslation.php
├── WikiArticle.php
├── WikiArticleTranslation.php
├── WikiTag.php
├── WikiTagTranslation.php
├── WikiFavorite.php
└── WikiArticleRevision.php
```

### 6.2 Services

```
src/Service/Wiki/
├── WikiCategoryService.php
├── WikiArticleService.php
├── WikiSearchService.php
├── WikiRevisionService.php
└── WikiSrdSyncService.php    # Génère wiki_article depuis le contenu D&D importé
```

### 6.3 Controllers

- [ ] `src/Controller/WikiController.php` (public)
- [ ] `src/Controller/Admin/AdminWikiController.php`

### 6.4 Endpoints

**Public :** `GET /api/wiki/{categories,articles,search,tags}`
**Auth :** `GET|POST|DELETE /api/wiki/favorites`
**Admin :** CRUD `/api/admin/wiki/*`, `POST /api/admin/wiki/sync-srd`

---

## Étape 7 : Frontend Wiki (14-18h)

### 7.1 Pages

```
src/views/wiki/
├── WikiHomeView.vue
├── WikiCategoryView.vue
├── WikiArticleView.vue
├── WikiSearchView.vue
└── WikiFavoritesView.vue

src/views/admin/wiki/
├── AdminWikiDashboard.vue
├── AdminWikiCategories.vue
├── AdminWikiArticles.vue
└── AdminWikiArticleEdit.vue
```

### 7.2 Composants

```
src/components/wiki/
├── WikiSidebar.vue
├── WikiArticleCard.vue
├── WikiSearchBar.vue
├── WikiMarkdownRenderer.vue
└── WikiFavoriteButton.vue
```

### 7.3 Store & API

- [ ] `src/stores/wikiStore.ts`
- [ ] `src/services/api/wikiApi.ts`
- [ ] `src/types/wiki.ts`

### 7.4 Dépendances

```bash
npm install vue-i18n@9 marked dompurify @types/dompurify
```

---

## Étape 8 : OWASP Compléments (4-6h)

### 8.1 Rate limiting Wiki

```yaml
# config/packages/rate_limiter.yaml
wiki_search:
    policy: 'sliding_window'
    limit: 30
    interval: '1 minute'
```

### 8.2 Audit Actions

```php
// src/Enum/AuditAction.php
case WIKI_ARTICLE_CREATE = 'wiki_article_create';
case WIKI_ARTICLE_UPDATE = 'wiki_article_update';
case WIKI_ARTICLE_DELETE = 'wiki_article_delete';
case WIKI_SRD_SYNC = 'wiki_srd_sync';
```

---

## Étape 9 : Tests (6-8h)

- [ ] Tests unitaires services Wiki
- [ ] Tests unitaires mappers d'import (transformation JSON → entité)
- [ ] Tests fonctionnels controllers Wiki
- [ ] Tests fonctionnels commandes d'import (`--dry-run`)
- [ ] Tests frontend store + composants

---

## Estimation Totale : 64-86h

| Étape | Heures |
|-------|--------|
| 1. Tables référence | 3-4h |
| 2. Tables contenu D&D | 10-12h |
| 3. Commandes d'import | 6-8h |
| 4. i18n | 6-8h |
| 5. Tables Wiki | 3-4h |
| 6. Backend Wiki | 12-14h |
| 7. Frontend Wiki | 14-18h |
| 8. OWASP | 4-6h |
| 9. Tests | 6-8h |

---

## Changelog corrections apportées au SQL guide

| # | Correction | Impact |
|---|-----------|--------|
| 1 | Ajout `category_abbreviation` dans `item_category` | Fix INSERT qui échouait |
| 2 | Ajout table `spell_component` | Composants V/S/M requêtables (coût, consommable) |
| 3 | Ajout table `item_weapon` | Catégorie simple/martial et portées structurées |
| 4 | Ajout table `item_armor` | AC, max dex, str requirement, stealth disadvantage |
| 5 | Ajout table `monster_subtype` | Sous-types (orc, goblinoid, shapechanger) |
| 6 | Ajout table `monster_language` | Normalisation FK au lieu de TEXT brut |
| 7 | Ajout table `monster_environment` | Environnements favoris (arctic, forest, etc.) |
| 8 | Ajout table `race_speed` | Vitesses spéciales (swim, fly, burrow, climb) |
| 9 | Ajout table `race_proficiency` | Maîtrises raciales (armes, armures, outils) |
| 10 | Ajout table `feat_prerequisite` | Prérequis structurés au lieu de TEXT |
| 11 | Ajout table `feat_benefit` | Avantages mécaniques requêtables |
| 12 | Ajout colonnes `item_is_magical`, `item_charges_max`, `item_charges_reset` dans `srd_item` | Gestion charges objets magiques |
| 13 | Retrait colonnes armor de `srd_item` | Déplacées vers `item_armor` |
| 14 | Fix UNIQUE KEY partiel `character_class_level` | Syntaxe PostgreSQL invalide en MySQL |
| 15 | Fix alias dupliqué `cs` dans vue `monster_complete` | Erreur SQL à l'exécution |
| 16 | Fix `user_username` → `user_pseudo` dans vues et données test | Cohérence avec table `user` |
| 17 | Fix vues `character_stats`/`character_full_info` | JOIN via `character_class_level` (multiclasse) |
| 18 | Retrait `query_cache` deprecated | Supprimé depuis MySQL 8.0 |
| 19 | Remplacement fixtures SRD par commandes d'import Symfony | Import idempotent depuis JSON, support multi-sources |
| 20 | Ajout `content_source` étendu toutes sources WotC | Accord WotC — plus limité au SRD uniquement |