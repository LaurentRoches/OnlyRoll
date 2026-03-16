export default {
  list: {
    titles: {
      public: 'Parties Publiques',
      myGames: 'Mes Parties (M.J.)',
      playerGames: 'Mes Parties (Joueur)',
      myAll: 'Mes Parties',
    },
    subtitles: {
      public: 'D\u00e9couvrez et rejoignez des parties en cours',
      myGames: 'G\u00e9rez vos parties actives et archiv\u00e9es',
      playerGames: 'Les parties auxquelles vous participez en tant que joueur',
      myAll: 'Toutes vos parties, en tant que MJ et joueur',
    },
    newButton: 'Nouvelle',
    loginButton: 'Se connecter',
    tabs: {
      all: 'Toutes',
      myGames: 'Mes parties',
      gm: 'M.J.',
      player: 'Joueur',
    },
    filtersToggle: 'Filtres',
    filters: {
      title: 'Filtres de recherche',
      search: 'Recherche',
      searchPlaceholder: 'Rechercher...',
      gameTitle: 'Titre',
      gameTitlePlaceholder: 'Titre de la campagne...',
      gameMaster: 'Ma\u00eetre du Jeu',
      gameMasterPlaceholder: 'Nom du MJ...',
      status: 'Statut',
      statusAll: 'Tous les statuts',
      statusPreparation: 'En pr\u00e9paration',
      statusInProgress: 'En cours',
      statusPaused: 'En pause',
      statusCompleted: 'Termin\u00e9e',
      reset: 'R\u00e9initialiser',
    },
    count: {
      singular: 'partie charg\u00e9e',
      plural: 'parties charg\u00e9es',
      outOf: 'sur',
    },
    empty: {
      publicTitle: 'Aucune partie trouv\u00e9e',
      myAllTitle: 'Vous ne participez \u00e0 aucune partie',
      defaultTitle: 'Aucune partie',
      publicMessage: 'Aucune partie publique ne correspond \u00e0 vos crit\u00e8res',
      myGamesMessage: 'Vous ne g\u00e9rez aucune partie pour le moment',
      playerGamesMessage: 'Vous ne participez \u00e0 aucune partie en tant que joueur',
      myAllMessage: 'Vous ne participez \u00e0 aucune partie pour le moment',
      createFirst: 'Cr\u00e9er votre premi\u00e8re partie',
      resetFilters: 'R\u00e9initialiser les filtres',
    },
  },

  play: {
    loading: 'Chargement de la partie...',
    tabs: {
      chat: 'Chat',
      players: 'Joueurs',
      dice: 'D\u00e9s',
    },
    panel: {
      hide: 'Masquer le panel (chat, joueurs, d\u00e9s)',
      show: 'Afficher le panel (chat, joueurs, d\u00e9s)',
      label: 'Panel',
    },
    leaveConfirm: '\u00cates-vous s\u00fbr de vouloir quitter cette partie ?\n\nVous serez retir\u00e9 en tant que membre et ne pourrez plus y acc\u00e9der.',
    mapLoadError: 'Impossible de charger les donn\u00e9es de la carte',
  },

  card: {
    status: {
      preparation: 'En pr\u00e9paration',
      in_progress: 'En cours',
      paused: 'En pause',
      completed: 'Termin\u00e9e',
      archived: 'Archiv\u00e9e',
    },
    private: 'Priv\u00e9e',
    gameMaster: 'Ma\u00eetre du jeu',
    campaignLevel: 'Campagne niveau : {level}',
    onlinePlayers: 'joueur(s) connect\u00e9(s)',
    joinButton: 'Rejoindre',
    fullButton: 'Compl\u00e8te',
    deleteTitle: 'Supprimer la partie',
    deleteConfirm: '\u00cates-vous s\u00fbr de vouloir supprimer "{name}" ?\n\nCette action est d\u00e9finitive : tous les joueurs, messages et cartes associ\u00e9s seront effac\u00e9s.',
    deleteError: 'Impossible de supprimer cette partie',
    viewLabel: 'Voir la partie {name}',
    joinHint: 'Cliquez sur "Rejoindre" pour acc\u00e9der \u00e0 cette partie',
  },

  header: {
    loading: 'Chargement...',
    unknownGM: 'Inconnu',
    connection: {
      connected: 'Connect\u00e9',
      connecting: 'Connexion...',
      reconnecting: 'Reconnexion...',
      disconnected: 'D\u00e9connect\u00e9',
      error: 'Erreur de connexion',
      unknown: 'Inconnu',
    },
    backButton: 'Retour',
    backTitle: 'Retour \u00e0 la liste des parties',
    settingsTitle: 'Param\u00e8tres de la partie',
    leaveButton: 'Quitter la partie',
    leaveTitle: 'Se retirer d\u00e9finitivement de cette partie',
    help: {
      buttonLabel: 'Aide',
      buttonTitle: "Guide d'utilisation de la partie",
      modalTitle: 'Guide de la partie',
      gm: {
        title: 'Guide du Ma\u00eetre du Jeu',
        settings: {
          title: '\u2699\ufe0f Param\u00e8tres de la partie',
          description:
            "Cliquez sur l'ic\u00f4ne engrenage dans la barre sup\u00e9rieure pour modifier le nom, la description, le nombre maximum de joueurs et la visibilit\u00e9 (publique/priv\u00e9e) de votre partie. Disponible uniquement en phase de pr\u00e9paration.",
        },
        maps: {
          title: '\ud83d\uddfa\ufe0f Gestion des cartes',
          description:
            "Utilisez la barre d'outils au-dessus de la carte pour cr\u00e9er une nouvelle carte, importer une image, modifier ou supprimer une carte existante. Vous pouvez d\u00e9finir la carte active que tous les joueurs verront. Configurez la grille (couleur, opacit\u00e9) depuis les param\u00e8tres de carte.",
        },
        tokens: {
          title: '\ud83c\udfad Gestion des tokens',
          description:
            "S\u00e9lectionnez l'outil de placement dans la barre d'outils, puis cliquez sur la carte pour placer un token. Vous pouvez cr\u00e9er des tokens pour les PNJ et monstres, les \u00e9diter (nom, couleur, taille) et les d\u00e9placer librement sur la carte.",
        },
        players: {
          title: '\ud83d\udc65 Gestion des joueurs',
          description:
            "Depuis le panneau Joueurs, vous pouvez inviter de nouveaux joueurs par pseudo, voir leur statut de connexion et exclure un joueur si n\u00e9cessaire. Les invitations appara\u00eetront dans les notifications du joueur.",
        },
        chat: {
          title: '\ud83d\udcac Tchat et messages syst\u00e8me',
          description:
            "En tant que MJ, vous disposez de toutes les commandes de tchat classiques (/roll, /whisper, /me) ainsi que la possibilit\u00e9 d'envoyer des messages syst\u00e8me visibles par tous. Utilisez le mode In Character pour parler en tant que PNJ.",
        },
        gameControl: {
          title: '\ud83c\udfae Contr\u00f4le de la partie',
          description:
            "Vous pouvez d\u00e9marrer la partie une fois qu'au moins un joueur a rejoint (en phase de pr\u00e9paration). Le bouton \u00ab Quitter la partie \u00bb vous retire d\u00e9finitivement \u2014 cette action est irr\u00e9versible.",
        },
      },
      player: {
        title: 'Guide du Joueur',
        chat: {
          title: '\ud83d\udcac Tchat',
          description:
            "Envoyez des messages dans le tchat en tapant dans la zone de texte et en appuyant sur Entr\u00e9e. Utilisez Shift+Entr\u00e9e pour un retour \u00e0 la ligne. Basculez entre le mode \u00ab In Character \u00bb (parler en tant que personnage) et \u00ab Out of Character \u00bb (hors personnage) avec le bouton IC/OOC.",
        },
        dice: {
          title: '\ud83c\udfb2 Lanceur de d\u00e9s',
          description:
            "Le panneau D\u00e9s vous permet de lancer rapidement des d\u00e9s pr\u00e9d\u00e9finis (d4 \u00e0 d20), d'utiliser des lancers courants (Initiative, Attaque, D\u00e9g\u00e2ts...) ou de composer une formule personnalis\u00e9e. Vous pouvez aussi lancer depuis le tchat avec /roll ou /r suivi de la formule.",
        },
        map: {
          title: '\ud83d\uddfa\ufe0f Carte et tokens',
          description:
            "Visualisez la carte partag\u00e9e par le MJ. Utilisez la molette pour zoomer, et cliquez-glissez pour naviguer. Vos tokens sont visibles sur la carte si le MJ en a plac\u00e9 pour vous.",
        },
        leave: {
          title: '\ud83d\udeaa Quitter la partie',
          description:
            "Le bouton \u00ab Quitter la partie \u00bb dans la barre sup\u00e9rieure vous retire d\u00e9finitivement de la partie. Cette action est irr\u00e9versible. Le bouton \u00ab Retour \u00bb vous ram\u00e8ne \u00e0 la liste des parties sans quitter.",
        },
      },
      close: 'Fermer',
    },
  },

  chat: {
    empty: {
      title: 'Aucun message pour le moment',
      subtitle: 'Soyez le premier \u00e0 parler !',
    },
    scrollDown: 'Aller en bas',
    inCharacter: 'In Character',
    outOfCharacter: 'Out of Character',
    help: {
      tooltip: '/roll \u2022 /whisper \u2022 /me \u2022 Cliquez pour plus de d\u00e9tails',
      modalTitle: 'Commandes du tchat',
      commands: {
        title: 'Commandes',
        roll: {
          name: '/roll (/r)',
          syntax: '1d20, 2d6+3, 3d8...',
          description: 'Lance des d\u00e9s en utilisant la notation standard. Le r\u00e9sultat est visible par tous les joueurs.',
        },
        whisper: {
          name: '/whisper (/w)',
          syntax: '/w pseudo message',
          description: 'Envoie un message priv\u00e9 \u00e0 un joueur sp\u00e9cifique. Seuls vous et le destinataire pouvez le voir.',
        },
        whisperRoll: {
          name: '/w pseudo /r',
          syntax: '/w pseudo /r 1d20+5',
          description: 'Lance des d\u00e9s en priv\u00e9. Seuls vous et le destinataire voyez le r\u00e9sultat.',
        },
        emote: {
          name: '/me',
          syntax: '/me action',
          description: "Effectue une action narrative. Ex : « /me d\u00e9gaine son \u00e9p\u00e9e » affiche votre pseudo suivi de l'action.",
        },
      },
      dice: {
        title: 'D\u00e9s pr\u00e9construits',
        quickDice: 'D\u00e9s rapides : d4, d6, d8, d10, d12, d20 \u2014 un clic pour lancer.',
        commonRolls: 'Lancers courants : Initiative, Attaque, D\u00e9g\u00e2ts (\u00e9p\u00e9e/arc), Jet de sauvegarde, Soin.',
        commonRollsHint: 'Disponibles dans le panneau D\u00e9s \u00e0 droite du tchat.',
      },
      advantage: {
        title: 'Avantage / D\u00e9savantage',
        normal: 'Normal \u2014 Lance les d\u00e9s normalement.',
        advantage: 'Avantage \u2014 Lance 2 d\u00e9s et garde le meilleur (2d20kh1).',
        disadvantage: 'D\u00e9savantage \u2014 Lance 2 d\u00e9s et garde le pire (2d20kl1).',
        super: 'Super-avantage \u2014 Lance 3 d\u00e9s et garde le meilleur.',
      },
      modes: {
        title: 'Modes de discussion',
        inCharacter: 'In Character (IC)',
        inCharacterDesc: 'Vos messages apparaissent comme paroles de votre personnage. Id\u00e9al pour le roleplay.',
        outOfCharacter: 'Out of Character (OOC)',
        outOfCharacterDesc: 'Vos messages apparaissent comme vous-m\u00eame, le joueur. Pour les discussions hors-jeu.',
      },
      close: 'Fermer',
    },
    placeholder: 'Enter text... (Shift+Enter pour nouvelle ligne)',
    sendTitle: 'Envoyer (Enter)',
    closeError: 'Fermer',
    suggestions: {
      title: 'Suggestions (\u2191\u2193 pour naviguer, Tab/Enter pour s\u00e9lectionner)',
    },
    errors: {
      invalidWhisperFormat: 'Format invalide. Utilisez : /w <pseudo> <message> ou /w <pseudo> /r <formule>',
      emptyMessage: 'Le message ne peut pas \u00eatre vide.',
      playerNotFound: 'Joueur "{pseudo}" introuvable dans cette partie.',
      emptyDiceFormula: 'La formule de d\u00e9s ne peut pas \u00eatre vide.',
      sendFailed: "Une erreur est survenue lors de l'envoi.",
    },
  },

  dice: {
    title: 'Lanceur de d\u00e9s',
    subtitle: 'Formule personnalis\u00e9e ou raccourcis rapides',
    quickDice: 'D\u00e9s rapides',
    mode: 'Mode',
    advantageModes: {
      normal: 'Normal',
      advantage: 'Avantage',
      disadvantage: 'D\u00e9savantage',
      super: 'Super-avantage',
    },
    customFormula: 'Formule personnalis\u00e9e',
    formulaPlaceholder: '2d6, 1d20, 3d8...',
    clearFormula: 'Effacer la formule',
    addNumber: 'Ajouter {num}',
    addDie: 'Ajouter d (d\u00e9)',
    addPlus: 'Ajouter +',
    addMinus: 'Ajouter -',
    backspace: 'Effacer le dernier caract\u00e8re',
    modifier: 'Modificateur',
    decreaseModifier: 'Diminuer le modificateur',
    increaseModifier: 'Augmenter le modificateur',
    noFormula: 'Aucune formule',
    rollInCharacter: 'Lancer en tant que personnage (IC)',
    rollButton: 'Lancer {formula}',
    rollButtonDefault: 'Lancer les d\u00e9s',
    rollError: 'Erreur lors du lancer de d\u00e9s.',
    closeError: 'Fermer',
    commonRolls: 'Lancers courants',
    presets: {
      initiative: 'Initiative',
      initiativeAdvantage: 'Initiative (avantage)',
      attack: 'Attaque',
      attackDisadvantage: 'Attaque (d\u00e9savantage)',
      damageSword: 'D\u00e9g\u00e2ts (\u00e9p\u00e9e)',
      damageBow: 'D\u00e9g\u00e2ts (arc)',
      savingThrow: 'Jet de sauvegarde',
      healingPotion: 'Soin (potion)',
    },
  },

  create: {
    title: 'Cr\u00e9er une nouvelle partie',
    closeModal: 'Fermer la modale',
    name: {
      label: 'Nom de la partie',
      placeholder: 'Ex: La Campagne des Dragons Oubli\u00e9s',
      error: 'Le nom doit faire au moins 3 caract\u00e8res',
    },
    description: {
      label: 'Description',
      placeholder: 'D\u00e9crivez votre campagne, son univers, son ambiance...',
    },
    maxPlayers: {
      label: 'Nombre maximum de joueurs',
      suffix: 'joueurs max',
    },
    visibility: {
      public: 'Partie publique',
      private: 'Partie priv\u00e9e',
      publicHint: 'Visible par tous les joueurs dans la liste publique',
      privateHint: 'Accessible uniquement avec le mot de passe',
    },
    password: {
      label: 'Mot de passe',
      placeholder: 'Mot de passe de la partie',
      error: 'Mot de passe requis pour une partie priv\u00e9e',
    },
    cancel: 'Annuler',
    submit: {
      creating: 'Cr\u00e9ation...',
      create: 'Cr\u00e9er la partie',
    },
  },

  join: {
    title: 'Rejoindre la partie',
    closeModal: 'Fermer la modale',
    gameMaster: 'Ma\u00eetre du jeu :',
    public: 'Publique',
    private: 'Priv\u00e9e',
    players: '{current} / {max} joueurs',
    full: 'Partie compl\u00e8te',
    password: {
      label: 'Mot de passe',
      placeholder: 'Entrez le mot de passe de la partie',
      error: 'Le mot de passe est requis',
    },
    publicHint: 'Cette partie est publique, vous pouvez la rejoindre directement sans mot de passe.',
    joinError: 'Impossible de rejoindre la partie',
    cancel: 'Annuler',
    submit: {
      joining: 'Connexion...',
      full: 'Partie compl\u00e8te',
      join: 'Rejoindre',
    },
  },

  players: {
    title: 'Joueurs',
    stats: {
      online: 'En ligne',
      members: 'Membres',
      gm: 'MJ',
    },
    roles: {
      gameMaster: 'MJ',
      player: 'Joueur',
      spectator: 'Spectateur',
      unknown: 'Inconnu',
    },
    status: {
      online: 'En ligne',
      offline: 'Hors ligne',
      pending: 'En attente',
    },
    timeAgo: {
      justNow: "\u00c0 l'instant",
      minutes: 'Il y a {count} min',
      hours: 'Il y a {count}h',
      days: 'Il y a {count}j',
    },
    kick: {
      confirm: '\u00cates-vous s\u00fbr de vouloir expulser {pseudo} de la partie ?',
      title: 'Expulser ce joueur',
      error: "Impossible d'expulser ce joueur",
    },
    empty: {
      title: 'Aucun joueur dans la partie',
      subtitle: 'Invitez vos amis \u00e0 vous rejoindre',
    },
    invite: {
      button: 'Inviter un joueur',
      atCapacity: 'Partie compl\u00e8te ({current}/{max})',
    },
    spectators: {
      title: 'Spectateurs ({count})',
    },
  },

  settings: {
    title: 'Param\u00e8tres de la partie',
    name: {
      label: 'Nom de la partie',
      placeholder: 'Ex : La For\u00eat Oubli\u00e9e',
    },
    description: {
      label: 'Description',
      placeholder: 'D\u00e9crivez votre partie...',
    },
    maxPlayers: {
      label: 'Nombre maximum de joueurs',
      hint: 'joueurs (1 \u00e0 20)',
    },
    errors: {
      nameRequired: 'Le nom de la partie est requis',
      maxPlayersRange: 'Le nombre de joueurs doit \u00eatre entre 1 et 20',
      saveFailed: 'Erreur lors de la sauvegarde',
    },
    cancel: 'Annuler',
    submit: {
      saving: 'Sauvegarde...',
      save: 'Enregistrer',
    },
  },

  invite: {
    title: 'Inviter un joueur',
    search: {
      label: 'Rechercher par pseudo',
      placeholder: 'Entrez un pseudo...',
    },
    alreadyMember: 'D\u00e9j\u00e0 membre',
    error: "Erreur lors de l'invitation",
    cancel: 'Annuler',
    submit: {
      sending: 'Envoi...',
      send: 'Inviter',
    },
  },

  uploadMap: {
    title: 'Cr\u00e9er une carte',
    subtitle: "Uploadez l'image de fond de votre carte",
    image: {
      label: 'Image de la carte *',
      selectPrompt: 'Cliquez pour s\u00e9lectionner une image',
      formats: 'JPEG, PNG, WebP ou GIF (max 10 Mo)',
      preview: 'Aper\u00e7u',
    },
    name: {
      label: 'Nom de la carte *',
      placeholder: 'Ex: Donjon du Dragon Rouge',
    },
    description: {
      label: 'Description',
      placeholder: 'D\u00e9crivez bri\u00e8vement votre carte...',
    },
    grid: {
      type: {
        label: 'Type de grille',
        square: 'Carr\u00e9e',
        hex: 'Hexagonale',
        none: 'Aucune',
      },
      size: 'Taille de case (px)',
      width: 'Largeur (cases)',
      height: 'Hauteur (cases)',
    },
    errors: {
      invalidType: 'Veuillez s\u00e9lectionner une image (JPEG, PNG, WebP, GIF)',
      tooLarge: "L'image est trop volumineuse (max 10 Mo)",
      uploadFailed: "Erreur lors de l'upload de la carte",
    },
    cancel: 'Annuler',
    submit: {
      uploading: 'Upload en cours...',
      upload: 'Cr\u00e9er la carte',
    },
  },

  editMap: {
    title: '\u00c9diter la carte',
    subtitle: 'Modifiez les informations de votre carte',
    image: {
      label: 'Image de la carte',
      optional: '(Optionnel - Laissez vide pour conserver l\'image actuelle)',
      selectPrompt: 'Cliquez pour s\u00e9lectionner une nouvelle image',
      formats: 'JPEG, PNG, WebP ou GIF (max 10 Mo)',
      newImage: 'Nouvelle image : {name}',
      currentImage: 'Image actuelle - Cliquez sur \u2715 pour changer',
      changeTitle: "Changer l'image",
      preview: 'Aper\u00e7u',
    },
    name: {
      label: 'Nom de la carte *',
      placeholder: 'Ex: Donjon du Dragon Rouge',
    },
    description: {
      label: 'Description',
      placeholder: 'D\u00e9crivez bri\u00e8vement votre carte...',
    },
    grid: {
      type: {
        label: 'Type de grille',
        square: 'Carr\u00e9e',
        hex: 'Hexagonale',
        none: 'Aucune',
      },
      size: 'Taille de case (px)',
      width: 'Largeur (cases)',
      height: 'Hauteur (cases)',
    },
    errors: {
      invalidType: 'Veuillez s\u00e9lectionner une image (JPEG, PNG, WebP, GIF)',
      tooLarge: "L'image est trop volumineuse (max 10 Mo)",
      updateFailed: 'Erreur lors de la mise \u00e0 jour de la carte',
    },
    cancel: 'Annuler',
    submit: {
      updating: 'Mise \u00e0 jour...',
      update: 'Mettre \u00e0 jour',
    },
  },

  createToken: {
    title: 'Cr\u00e9er un Token',
    position: 'Position: ({x}, {y})',
    name: {
      label: 'Nom du token *',
      placeholder: 'Ex: Gobelin, Coffre, etc.',
    },
    type: {
      label: 'Type de token *',
      character: 'Personnage',
      monster: 'Monstre',
      npc: 'PNJ',
      object: 'Objet',
    },
    size: {
      label: 'Taille',
      small: 'Petit (0.5)',
      medium: 'Moyen (1)',
      large: 'Grand (2)',
      huge: '\u00c9norme (3)',
      gargantuan: 'Gigantesque (4)',
    },
    image: {
      label: 'Image du token (optionnel)',
      selectPrompt: 'Cliquez pour s\u00e9lectionner une image',
      formats: 'JPEG, PNG, WebP ou GIF (max 5 Mo)',
      deleteTitle: "Supprimer l'image",
      noImageHint: 'Si aucune image n\'est fournie, les initiales du nom seront utilis\u00e9es',
    },
    errors: {
      invalidType: 'Veuillez s\u00e9lectionner une image (JPEG, PNG, WebP, GIF)',
      tooLarge: "L'image est trop volumineuse (max 5 Mo)",
      createFailed: 'Erreur lors de la cr\u00e9ation du token',
    },
    cancel: 'Annuler',
    submit: {
      creating: 'Cr\u00e9ation...',
      create: 'Cr\u00e9er le Token',
    },
  },

  editToken: {
    title: 'Modifier le Token',
    name: {
      label: 'Nom du token *',
      placeholder: 'Ex: Gobelin, Coffre, etc.',
    },
    type: {
      label: 'Type de token *',
      character: 'Personnage',
      monster: 'Monstre',
      npc: 'PNJ',
      object: 'Objet',
    },
    size: {
      label: 'Taille',
      small: 'Petit (0.5)',
      medium: 'Moyen (1)',
      large: 'Grand (2)',
      huge: '\u00c9norme (3)',
      gargantuan: 'Gigantesque (4)',
    },
    image: {
      label: 'Image du token (optionnel)',
      selectPrompt: 'Cliquez pour s\u00e9lectionner une image',
      formats: 'JPEG, PNG, WebP ou GIF (max 5 Mo)',
      newImage: 'Nouvelle image : {name}',
      currentImage: 'Image actuelle - Cliquez sur \u2715 pour changer',
      changeTitle: "Changer l'image",
      preview: 'Aper\u00e7u',
    },
    errors: {
      invalidType: 'Veuillez s\u00e9lectionner une image (JPEG, PNG, WebP, GIF)',
      tooLarge: "L'image est trop volumineuse (max 5 Mo)",
      updateFailed: 'Erreur lors de la mise \u00e0 jour du token',
    },
    cancel: 'Annuler',
    submit: {
      updating: 'Mise \u00e0 jour...',
      update: 'Mettre \u00e0 jour',
    },
  },

  emptyMap: {
    title: 'Aucune carte active',
    gmMessage: "Cette partie n'a pas encore de carte. Uploadez une carte pour commencer \u00e0 jouer et placer des tokens.",
    playerMessage: "Le ma\u00eetre du jeu n'a pas encore cr\u00e9\u00e9 de carte pour cette session. Patientez pendant qu'il pr\u00e9pare le terrain de jeu.",
    createButton: 'Cr\u00e9er une carte',
    tips: {
      title: 'Conseil',
      formats: 'Formats support\u00e9s : JPEG, PNG, WebP, GIF',
      maxSize: 'Taille max : 10 Mo',
      resolution: 'Utilisez une r\u00e9solution adapt\u00e9e (1920x1080 recommand\u00e9)',
    },
  },

  toolbar: {
    map: 'Carte',
    mapTitle: 'Gestion des cartes',
    tools: 'Outils',
    toolsTitle: 'Outils de dessin',
    toolNames: {
      select: 'S\u00e9lection',
      token: 'Ajouter Token',
      fog: 'Brouillard',
      measure: 'Mesure',
      draw: 'Dessin',
      ping: 'Ping',
    },
    zoom: {
      out: 'D\u00e9zoomer',
      in: 'Zoomer',
      customTitle: 'Cliquer pour entrer une valeur personnalis\u00e9e',
    },
    center: 'Centrer',
    centerTitle: 'Centrer la vue',
    mapSelector: {
      noActiveMap: 'Aucune carte active',
      selectTitle: 'S\u00e9lectionner une carte',
      searchPlaceholder: 'Filtrer les cartes...',
      active: 'Active',
      editTitle: '\u00c9diter cette carte',
      deleteTitle: 'Supprimer cette carte',
      deleteConfirm: '\u00cates-vous s\u00fbr de vouloir supprimer la carte "{name}" ?',
      deleteError: 'Erreur lors de la suppression de la carte',
      noMaps: 'Aucune carte trouv\u00e9e',
    },
    newMap: 'Nouvelle carte',
    newMapTitle: 'Cr\u00e9er une nouvelle carte',
    grid: {
      button: 'Grille',
      buttonTitle: 'Param\u00e8tres de grille',
      settingsTitle: 'Param\u00e8tres de la grille',
      showGrid: 'Afficher la grille',
      color: 'Couleur',
      opacity: 'Opacit\u00e9',
    },
  },

  map: {
    tokenActions: {
      edit: 'Modifier',
      editTitle: 'Modifier le token',
      visibility: 'Visibilit\u00e9',
      visibilityTitle: 'Toggle visibilit\u00e9',
      lock: 'Verrouiller',
      lockTitle: 'Verrouiller/D\u00e9verrouiller',
      permissions: 'Permissions',
      permissionsTitle: 'G\u00e9rer les permissions',
      delete: 'Supprimer',
      deleteTitle: 'Supprimer',
      deleteConfirm: 'Supprimer ce token ?',
    },
    permissions: {
      title: 'Permissions de contr\u00f4le',
      close: 'Fermer',
      description: 'S\u00e9lectionnez les joueurs qui peuvent contr\u00f4ler ce token avec les fl\u00e8ches directionnelles',
      noPlayers: 'Aucun joueur disponible',
      closeButton: 'Fermer',
    },
  },

  stores: {
    game: {
      fetchPublicError: 'Erreur lors du chargement des parties',
      fetchMyError: 'Erreur lors du chargement de vos parties',
      fetchByIdError: 'Partie introuvable',
      createError: 'Erreur lors de la cr\u00e9ation de la partie',
      updateError: 'Erreur lors de la mise \u00e0 jour de la partie',
      deleteError: 'Erreur lors de la suppression de la partie',
      joinError: 'Impossible de rejoindre la partie',
      leaveError: 'Impossible de quitter la partie',
      startError: 'Impossible de d\u00e9marrer la partie',
      appendError: 'Erreur lors du chargement des parties',
    },
    chat: {
      loadError: 'Erreur lors du chargement des messages',
      loadNewError: 'Erreur lors du chargement des nouveaux messages',
      sendError: "Erreur lors de l'envoi du message",
      sendEmoteError: "Erreur lors de l'envoi de l'\u00e9mote",
      sendWhisperError: "Erreur lors de l'envoi du chuchotement",
      sendSystemError: "Erreur lors de l'envoi du message syst\u00e8me",
      rollError: 'Erreur lors du lancer de d\u00e9s',
      deleteError: 'Erreur lors de la suppression du message',
    },
    map: {
      loadMapsError: 'Erreur lors du chargement des cartes',
      loadActiveError: 'Erreur lors du chargement de la carte active',
      loadMapError: 'Erreur lors du chargement de la carte',
      activateError: "Erreur lors de l'activation de la carte",
      deleteError: 'Erreur lors de la suppression de la carte',
      updateSettingsError: 'Erreur lors de la mise \u00e0 jour des param\u00e8tres de la carte',
      loadTokensError: 'Erreur lors du chargement des tokens',
      createTokenError: 'Erreur lors de la cr\u00e9ation du token',
      moveTokenError: 'Erreur lors du d\u00e9placement du token',
      updateTokenError: 'Erreur lors de la mise \u00e0 jour du token',
      deleteTokenError: 'Erreur lors de la suppression du token',
      visibilityError: 'Erreur lors du changement de visibilit\u00e9',
      lockError: 'Erreur lors du changement de verrouillage',
      permissionsError: 'Erreur lors de la gestion des permissions',
    },
  },
}
