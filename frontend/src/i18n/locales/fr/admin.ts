export default {
  dashboard: {
    title: 'Tableau de bord',
    subtitle: "Vue d'ensemble de l'administration",
    loading: 'Chargement en cours',
    loadingData: 'Chargement des données...',
    errorLabel: 'Erreur :',
    errorLoading: 'Erreur lors du chargement des données',

    users: {
      heading: 'Utilisateurs',
      total: 'Total',
      totalSrOnly: '{count} utilisateurs au total',
      active: 'Actifs',
      activeSrOnly: '{count} utilisateurs actifs',
      thisWeek: 'Cette semaine',
      thisWeekSrOnly: '{count} nouveaux utilisateurs cette semaine',
      admins: 'Administrateurs',
      adminsSrOnly: '{count} administrateurs',
    },

    audit: {
      heading: 'Audit',
      total: 'Total',
      totalSrOnly: "{count} événements d'audit au total",
      today: "Aujourd'hui",
      todaySrOnly: "{count} événements aujourd'hui",
      warnings: 'Warnings',
      warningsSrOnly: "{count} warnings aujourd'hui",
      failedLogins: 'Échecs login',
      failedLoginsSrOnly: "{count} échecs de connexion aujourd'hui",
    },

    recentActivity: {
      heading: 'Activité récente',
      caption: 'Dernières actions enregistrées dans le système',
      emptyMessage: 'Aucune activité récente',
      systemUser: 'Système',
    },

    columns: {
      action: 'Action',
      user: 'Utilisateur',
      date: 'Date',
    },
  },

  users: {
    title: 'Gestion des utilisateurs',
    totalUsers: '{count} utilisateurs au total',
    searchAriaLabel: 'Filtrer les utilisateurs',
    searchLabel: 'Rechercher un utilisateur',
    searchPlaceholder: 'Rechercher...',
    loading: 'Chargement en cours',
    loadingUsers: 'Chargement des utilisateurs...',
    errorLabel: 'Erreur :',
    errorLoading: 'Erreur lors du chargement',
    errorDeleting: 'Erreur lors de la suppression',
    errorRestoring: 'Erreur lors de la restauration',

    filters: {
      statusLabel: 'Filtrer par statut',
      allStatuses: 'Tous les statuts',
      active: 'Actifs',
      inactive: 'Inactifs',
      deleted: 'Supprimés',
      locked: 'Verrouillés',
      roleLabel: 'Filtrer par rôle',
      allRoles: 'Tous les rôles',
      users: 'Utilisateurs',
      administrators: 'Administrateurs',
    },

    columns: {
      id: 'ID',
      pseudo: 'Pseudo',
      email: 'Email',
      roles: 'Rôles',
      status: 'Statut',
      lastLogin: 'Dernière connexion',
      actions: 'Actions',
    },

    table: {
      caption: 'Liste des utilisateurs avec leurs informations et actions disponibles',
      emptyMessage: 'Aucun utilisateur trouvé',
    },

    status: {
      deleted: 'Supprimé',
      locked: 'Verrouillé',
      active: 'Actif',
      inactive: 'Inactif',
    },

    lastLoginNever: 'Jamais',

    actions: {
      restore: 'Restaurer',
      restoreAriaLabel: "Restaurer l'utilisateur {pseudo}",
      delete: 'Supprimer',
      deleteAriaLabel: "Supprimer l'utilisateur {pseudo}",
    },

    deleteModal: {
      title: 'Supprimer {pseudo} ?',
      titleFallback: "Supprimer l'utilisateur ?",
      message:
        'Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est réversible (soft delete).',
      cancel: 'Annuler',
      confirm: 'Supprimer',
    },

    pagination: {
      ariaLabel: 'Pagination des utilisateurs',
    },
  },

  auditLogs: {
    title: "Logs d'audit",
    subtitle: 'Historique des actions du système',
    searchAriaLabel: "Filtrer les logs d'audit",
    loading: 'Chargement en cours',
    loadingLogs: 'Chargement des logs...',
    errorLabel: 'Erreur :',
    errorLoading: 'Erreur lors du chargement',

    filters: {
      actionLabel: 'Action',
      allActions: 'Toutes les actions',
      severityLabel: 'Sévérité',
      allSeverities: 'Toutes les sévérités',
      severityInfo: 'Info',
      severityWarning: 'Warning',
      severityHigh: 'High',
      dateFrom: 'Date de début',
      dateTo: 'Date de fin',
      reset: 'Réinitialiser',
    },

    columns: {
      date: 'Date',
      action: 'Action',
      user: 'Utilisateur',
      target: 'Cible',
      ip: 'IP',
      details: 'Détails',
    },

    table: {
      caption: "Historique des événements d'audit du système",
      emptyMessage: 'Aucun log trouvé pour ces critères',
    },

    systemUser: 'Système',
    noTarget: '-',
    noIp: '-',
    viewDetails: 'Voir détails',
    viewDetailsAriaLabel: 'Voir les détails du log {id}',

    detailModal: {
      title: 'Détails du log',
      id: 'ID',
      action: 'Action',
      user: 'Utilisateur',
      target: 'Cible',
      date: 'Date',
      ipAddress: 'Adresse IP',
      details: 'Détails',
      userAgent: 'User Agent',
      close: 'Fermer',
    },

    pagination: {
      ariaLabel: "Pagination des logs d'audit",
    },
  },
}
