/**
 * Configuration Pa11y pour les tests d'accessibilité automatisés
 *
 * Exécution :
 * - npm run test:a11y (toutes les pages)
 * - npx pa11y http://localhost:5173/auth/login (page spécifique)
 *
 * Standards :
 * - WCAG 2.1 AA (via htmlcs et axe)
 * - Compatible RGAA 4.1.2
 */

module.exports = {
  /**
   * Configuration par défaut pour tous les tests
   */
  defaults: {
    // Standard WCAG 2.1 niveau AA
    standard: 'WCAG2AA',

    // Runners : axe-core (moderne) et HTML CodeSniffer (complet)
    runners: ['axe', 'htmlcs'],

    // Timeout pour le chargement des pages (30 secondes)
    timeout: 30000,

    // Attente après chargement (pour les SPAs avec rendu async)
    wait: 2000,

    // Viewport desktop par défaut
    viewport: {
      width: 1280,
      height: 800,
    },

    // Headers pour les requêtes (utile pour l'auth si nécessaire)
    headers: {
      'Accept-Language': 'fr-FR,fr;q=0.9',
    },

    // Ignorer certaines règles si nécessaire (avec prudence)
    ignore: [
      // Exemple : 'WCAG2AA.Principle1.Guideline1_4.1_4_3.G18.Fail'
    ],

    // Inclure les avertissements (pas seulement les erreurs)
    includeWarnings: true,
    includeNotices: false,

    // Configuration Chrome/Puppeteer
    chromeLaunchConfig: {
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
      ],
      headless: true,
    },
  },

  /**
   * URLs à tester
   *
   * Chaque entrée peut être :
   * - Une string (URL simple)
   * - Un objet avec url, name, et options spécifiques
   */
  urls: [
    // ========== PAGES PUBLIQUES ==========
    {
      url: 'http://localhost:5173/',
      name: 'Page d\'accueil',
      screenCapture: './pa11y-screenshots/home.png',
    },
    {
      url: 'http://localhost:5173/auth/login',
      name: 'Page de connexion',
      screenCapture: './pa11y-screenshots/login.png',
    },
    {
      url: 'http://localhost:5173/auth/register',
      name: 'Page d\'inscription',
      screenCapture: './pa11y-screenshots/register.png',
    },

    // ========== PAGES AUTHENTIFIÉES ==========
    // Note: Ces pages nécessitent une authentification
    // Utilisez les actions ci-dessous ou un cookie de session
    {
      url: 'http://localhost:5173/dashboard',
      name: 'Dashboard utilisateur',
      screenCapture: './pa11y-screenshots/dashboard.png',
      // Actions pour s'authentifier avant le test
      actions: [
        // Remplir le formulaire de connexion
        'navigate to http://localhost:5173/auth/login',
        'wait for element #email to be visible',
        'set field #email to test@example.com',
        'set field #password to TestPassword123!',
        'click element button[type="submit"]',
        'wait for url to be http://localhost:5173/dashboard',
      ],
    },
    {
      url: 'http://localhost:5173/profile',
      name: 'Page profil',
      screenCapture: './pa11y-screenshots/profile.png',
    },
    {
      url: 'http://localhost:5173/games',
      name: 'Liste des parties',
      screenCapture: './pa11y-screenshots/games.png',
    },

    // ========== PAGES ADMIN ==========
    {
      url: 'http://localhost:5173/admin',
      name: 'Admin - Dashboard',
      screenCapture: './pa11y-screenshots/admin-dashboard.png',
    },
    {
      url: 'http://localhost:5173/admin/users',
      name: 'Admin - Utilisateurs',
      screenCapture: './pa11y-screenshots/admin-users.png',
    },
    {
      url: 'http://localhost:5173/admin/audit-logs',
      name: 'Admin - Logs d\'audit',
      screenCapture: './pa11y-screenshots/admin-audit.png',
    },
  ],
}

/**
 * Configuration alternative pour les tests CI
 * (sans authentification, pages publiques uniquement)
 */
module.exports.ci = {
  ...module.exports.defaults,
  timeout: 60000,
  urls: [
    'http://localhost:5173/',
    'http://localhost:5173/auth/login',
    'http://localhost:5173/auth/register',
  ],
}

/**
 * Thresholds pour les tests CI
 * Le build échoue si ces seuils sont dépassés
 */
module.exports.thresholds = {
  errors: 0, // Zéro erreur acceptable
  warnings: 10, // Maximum 10 warnings
}
