export default {
  skipLink: 'Aller au contenu principal',

  header: {
    title: 'Mon profil',
    subtitle: 'G\u00e9rez vos informations personnelles',
    backToDashboard: 'Retour au dashboard',
  },

  loading: {
    ariaLabel: 'Chargement en cours',
    screenReader: 'Chargement du profil...',
  },

  error: {
    label: 'Erreur :',
    retry: 'R\u00e9essayer',
    loadProfile: 'Erreur lors du chargement du profil',
    generic: 'Une erreur est survenue.',
  },

  avatar: {
    sectionTitle: 'Photo de profil',
    altWithAvatar: 'Avatar de {pseudo}',
    altWithoutAvatar: 'Aucun avatar d\u00e9fini',
    uploadInProgress: 'Upload en cours',
    changeButton: "Changer l'avatar",
    addButton: 'Ajouter un avatar',
    deleteButton: "Supprimer l'avatar",
    helpText: 'Formats accept\u00e9s : JPEG, PNG, GIF, WebP. Taille maximale : 2 Mo.',
    errors: {
      invalidType: 'Type de fichier non autoris\u00e9. Formats accept\u00e9s : JPEG, PNG, GIF, WebP.',
      tooLarge: 'Le fichier est trop volumineux. Taille maximale : 2 Mo.',
      uploadFailed: "Erreur lors de l'upload de l'avatar.",
      deleteFailed: "Erreur lors de la suppression de l'avatar.",
    },
    success: {
      uploaded: 'Avatar mis \u00e0 jour avec succ\u00e8s.',
      deleted: 'Avatar supprim\u00e9 avec succ\u00e8s.',
    },
  },

  info: {
    sectionTitle: 'Informations personnelles',
    pseudo: {
      label: 'Pseudo',
      required: '(obligatoire)',
      errors: {
        required: 'Le pseudo est requis',
        minLength: 'Le pseudo doit contenir au moins 3 caract\u00e8res',
      },
    },
    email: {
      label: 'Adresse email',
      hint: "L'adresse email ne peut pas \u00eatre modifi\u00e9e.",
    },
    timezone: {
      label: 'Fuseau horaire',
    },
    language: {
      label: 'Langue pr\u00e9f\u00e9r\u00e9e',
      options: {
        fr: 'Fran\u00e7ais',
        en: 'English',
      },
    },
    success: 'Vos informations ont \u00e9t\u00e9 mises \u00e0 jour avec succ\u00e8s.',
    submit: {
      saving: 'Enregistrement...',
      save: 'Enregistrer les modifications',
    },
  },

  password: {
    sectionTitle: 'Changer le mot de passe',
    currentPassword: {
      label: 'Mot de passe actuel',
      required: '(obligatoire)',
      toggleShow: 'Afficher le mot de passe actuel',
      toggleHide: 'Masquer le mot de passe actuel',
      errors: {
        required: 'Le mot de passe actuel est requis',
      },
    },
    newPassword: {
      label: 'Nouveau mot de passe',
      required: '(obligatoire)',
      toggleShow: 'Afficher le nouveau mot de passe',
      toggleHide: 'Masquer le nouveau mot de passe',
      generate: 'G\u00e9n\u00e9rer',
      generating: 'G\u00e9n\u00e9ration...',
      errors: {
        required: 'Le nouveau mot de passe est requis',
        invalid: 'Le mot de passe ne respecte pas les exigences de s\u00e9curit\u00e9',
      },
    },
    confirmPassword: {
      label: 'Confirmer le nouveau mot de passe',
      required: '(obligatoire)',
      toggleShow: 'Afficher la confirmation',
      toggleHide: 'Masquer la confirmation',
      errors: {
        required: 'La confirmation du mot de passe est requise',
        mismatch: 'Les mots de passe ne correspondent pas',
      },
    },
    strength: {
      ariaLabel: 'Force du mot de passe: {label}',
      weak: 'Faible',
      medium: 'Moyen',
      good: 'Bon',
      strong: 'Fort',
      valid: 'Mot de passe conforme aux exigences',
      feedback: {
        minLength: 'Au moins 12 caract\u00e8res requis',
        lowercase: 'Au moins une minuscule requise',
        uppercase: 'Au moins une majuscule requise',
        digit: 'Au moins un chiffre requis',
        special: 'Au moins un caract\u00e8re sp\u00e9cial requis (!@#$%&*()_+-=[]{}|;:,.<>?)',
      },
    },
    submit: {
      changing: 'Modification en cours...',
      change: 'Changer le mot de passe',
    },
    success: 'Votre mot de passe a \u00e9t\u00e9 modifi\u00e9 avec succ\u00e8s.',
    errors: {
      generic: 'Une erreur est survenue lors de la modification du mot de passe.',
      fallback: 'Une erreur est survenue.',
    },
  },

  account: {
    sectionTitle: 'Informations du compte',
    memberSince: 'Membre depuis',
    lastLogin: 'Derni\u00e8re connexion',
    lastLoginNever: 'Jamais',
    accountStatus: 'Statut du compte',
    verified: 'V\u00e9rifi\u00e9',
    pendingVerification: 'En attente de v\u00e9rification',
    roles: 'R\u00f4les',
  },
}
