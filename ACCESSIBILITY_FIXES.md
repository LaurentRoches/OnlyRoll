# Corrections d'accessibilité - OnlyRoll

## Date : 2026-01-05

## Résumé
Corrections des 54 erreurs critiques détectées par pa11y (axe-core) pour conformité WCAG 2.1 AA et RGAA 4.1.

## Problèmes identifiés et corrigés

### 1. SVG décoratifs sans `aria-hidden="true"` ✅
**Critère WCAG** : 1.3.1 (Info and Relationships)

**Fichiers modifiés :**
- `frontend/src/components/auth/LoginForm.vue`
- `frontend/src/components/auth/RegisterForm.vue`
- `frontend/src/components/game/CreateGameModal.vue`
- `frontend/src/components/game/JoinGameModal.vue`
- `frontend/src/components/game/GameCard.vue`
- `frontend/src/components/game/DiceRoller.vue`
- `frontend/src/views/HomeView.vue`

**Correction :** Ajout de `aria-hidden="true"` à tous les SVG purement décoratifs (icônes, spinners, etc.).

### 2. Images sans attribut `alt` ✅
**Critère WCAG** : 1.1.1 (Non-text Content)

**Fichier modifié :**
- `frontend/src/views/HomeView.vue` - Logo OnlyRoll

**Correction :** Ajout de `alt="Logo OnlyRoll"` à l'image du logo.

### 3. Boutons avec icônes sans `aria-label` ✅
**Critère WCAG** : 4.1.2 (Name, Role, Value)

**Fichiers modifiés :**
- `frontend/src/components/auth/LoginForm.vue` - Bouton toggle password
- `frontend/src/components/auth/RegisterForm.vue` - Boutons toggle password
- `frontend/src/components/game/CreateGameModal.vue` - Bouton fermer modal
- `frontend/src/components/game/JoinGameModal.vue` - Bouton fermer modal
- `frontend/src/components/game/DiceRoller.vue` - Boutons calculatrice et modificateur

**Correction :** Ajout de `aria-label` descriptifs sur tous les boutons n'ayant que des icônes comme contenu.

### 4. Modales sans attributs ARIA appropriés ✅
**Critère WCAG** : 4.1.2 (Name, Role, Value)

**Fichiers modifiés :**
- `frontend/src/components/game/CreateGameModal.vue`
- `frontend/src/components/game/JoinGameModal.vue`

**Corrections appliquées :**
- Ajout de `role="dialog"`
- Ajout de `aria-modal="true"`
- Ajout de `aria-labelledby` pointant vers le titre de la modale
- Ajout d'ID sur les titres de modales

### 5. Labels non associés aux inputs ✅
**Critère WCAG** : 3.3.2 (Labels or Instructions)

**Fichiers modifiés :**
- `frontend/src/components/game/CreateGameModal.vue`
- `frontend/src/components/game/JoinGameModal.vue`
- `frontend/src/components/game/DiceRoller.vue`

**Correction :** Association correcte des `<label for="id">` avec les `<input id="id">` correspondants.

### 6. Éléments cliquables non accessibles au clavier ✅
**Critère WCAG** : 2.1.1 (Keyboard)

**Fichier modifié :**
- `frontend/src/components/game/GameCard.vue`

**Corrections appliquées :**
- Changement de `<div>` en `<article>` avec `role="button"`
- Ajout de `tabindex="0"` pour permettre la navigation au clavier
- Ajout de `@keydown.enter` et `@keydown.space.prevent` pour l'activation au clavier
- Ajout de `aria-label` descriptif

### 7. Messages d'erreur sans `role="alert"` ✅
**Critère WCAG** : 4.1.3 (Status Messages)

**Fichiers modifiés :**
- `frontend/src/components/game/CreateGameModal.vue`
- `frontend/src/components/game/JoinGameModal.vue`

**Correction :** Ajout de `role="alert"` aux divs contenant les messages d'erreur.

## Tests

### Test local avec pa11y

```bash
cd frontend

# Installer pa11y-ci si nécessaire
npm install -g pa11y-ci serve

# Construire le projet
npm run build

# Lancer le serveur
npx serve -s dist -l 8080 &

# Attendre que le serveur démarre
sleep 5

# Lancer les tests d'accessibilité
pa11y-ci --json > pa11y-report-after-fixes.json

# Arrêter le serveur
pkill -f "serve"
```

### Vérification CI/CD

Le job `accessibility` dans `.github/workflows/ci.yml` exécutera automatiquement les tests pa11y lors du prochain push.

```bash
git add .
git commit -m "fix(a11y): correction des 54 erreurs d'accessibilité WCAG 2.1 AA

- Ajout aria-hidden aux SVG décoratifs
- Ajout alt au logo
- Ajout aria-label aux boutons avec icônes
- Ajout role=dialog et aria-modal aux modales
- Association correcte labels/inputs avec for/id
- Amélioration accessibilité clavier de GameCard
- Ajout role=alert aux messages d'erreur

Refs: WCAG 2.1 AA, RGAA 4.1, European Accessibility Act"

git push
```

## Conformité attendue

Après ces corrections, l'application devrait être conforme aux standards suivants :
- ✅ WCAG 2.1 niveau AA
- ✅ RGAA 4.1 (Référentiel Général d'Amélioration de l'Accessibilité)
- ✅ European Accessibility Act (juin 2025)

## Prochaines étapes recommandées

1. **Tests manuels avec lecteurs d'écran**
   - NVDA (Windows)
   - JAWS (Windows)
   - VoiceOver (macOS)

2. **Tests de navigation au clavier**
   - Vérifier que tous les éléments interactifs sont accessibles avec Tab
   - Vérifier les focus visibles
   - Tester la navigation dans les modales

3. **Tests de contraste**
   - Les couleurs actuelles semblent conformes
   - Vérifier avec un outil comme axe DevTools ou Lighthouse

4. **Documentation pour les développeurs**
   - Créer un guide des bonnes pratiques d'accessibilité pour l'équipe
   - Ajouter des vérifications d'accessibilité dans les revues de code

## Outils utilisés

- **pa11y-ci** : Tests automatisés d'accessibilité
- **axe-core** : Moteur de détection des problèmes WCAG
- **Lighthouse CI** : Audit global (performance + accessibilité)

## Ressources

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [RGAA 4.1](https://www.numerique.gouv.fr/publications/rgaa-accessibilite/)
- [ARIA Practices](https://www.w3.org/WAI/ARIA/apg/)
