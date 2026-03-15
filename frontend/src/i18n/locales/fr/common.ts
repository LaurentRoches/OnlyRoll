export default {
  errors: {
    sessionExpired: 'Session expirée, déconnexion automatique',
    generic: 'Une erreur est survenue',
    unexpected: "Une erreur inattendue est survenue",
    unexpectedOccurred: "Une erreur inattendue s'est produite",
  },

  notFound: {
    title: '404',
    heading: 'Echec critique !',
    description:
      'Vous avez fait un {roll} sur votre jet de navigation... Cette page semble avoir été dévorée par un Dragon Rouge.',
    rollValue: '1',
    suggestions: 'Peut-être cherchez-vous :',
    login: 'Se connecter',
    register: "S'inscrire",
    dashboard: 'Dashboard',
    wiki: 'Wiki D&D',
    back: 'Retour',
    home: 'Accueil',
    rerollQuote: 'Relancer le dé pour une nouvelle citation',
    quotes: {
      0: {
        text: 'Un aventurier averti en vaut deux !',
        author: 'Proverbe de taverne',
      },
      1: {
        text: "Il vaut mieux être perdu avec une carte qu'être trouvé sans direction.",
        author: 'Sage Ranger',
      },
      2: {
        text: 'Même les plus grands héros se perdent parfois en chemin.',
        author: "Chroniques d'Astarion",
      },
      3: {
        text: "Ce n'est pas l'erreur qui compte, c'est comment on s'en remet.",
        author: 'Manuel du Maître de Donjon',
      },
      4: {
        text: 'Parfois, se perdre mène aux plus grandes découvertes.',
        author: "Journal d'un Explorateur",
      },
      5: {
        text: 'Un chemin fermé en cache souvent un autre.',
        author: 'Proverbe elfique',
      },
    },
  },

  nav: {
    dashboard: 'Dashboard',
    games: 'Parties',
    admin: 'Admin',
    logout: 'Déconnexion',
    notifications: 'Notifications',
    navigationMenu: 'Menu de navigation',
    invitations: {
      title: 'Invitations en attente',
      loading: 'Chargement...',
      empty: 'Aucune invitation en attente',
      gameMaster: 'MJ : {pseudo}',
      accept: 'Rejoindre',
      decline: 'Refuser',
      acceptError: "Erreur lors de l'acceptation de l'invitation",
      declineError: "Erreur lors du refus de l'invitation",
    },
  },

  dashboardCard: {
    comingSoon: 'Bientôt',
  },

  userProfile: {
    avatarAlt: 'Avatar de {pseudo}',
    myProfile: 'Mon profil',
    settings: 'Paramètres',
    loggingOut: 'Déconnexion...',
    logoutButton: 'Se déconnecter',
  },

  konami: {
    message: "Vous avez découvert l'easter egg !",
  },

  accessibleTable: {
    emptyMessage: 'Aucune donnée disponible',
    regionLabel: 'Tableau de données',
    description: 'Ce tableau contient {count} ligne. | Ce tableau contient {count} lignes.',
    sortHint: 'Cliquez sur les en-têtes de colonnes pour trier les données.',
    sortedAsc: 'trié par ordre croissant',
    sortedDesc: 'trié par ordre décroissant',
    sortAnnouncement: 'Tableau trié par {column}, {direction}',
    ascending: 'ordre croissant',
    descending: 'ordre décroissant',
  },
}
