export default {
  list: {
    titles: {
      public: 'Public Games',
      myGames: 'My Games (GM)',
      playerGames: 'My Games (Player)',
      myAll: 'My Games',
    },
    subtitles: {
      public: 'Discover and join games in progress',
      myGames: 'Manage your active and archived games',
      playerGames: 'Games you are participating in as a player',
      myAll: 'All your games, as GM and player',
    },
    newButton: 'New',
    loginButton: 'Log in',
    tabs: {
      all: 'All',
      myGames: 'My games',
      gm: 'GM',
      player: 'Player',
    },
    filtersToggle: 'Filters',
    filters: {
      title: 'Search filters',
      search: 'Search',
      searchPlaceholder: 'Search...',
      gameTitle: 'Title',
      gameTitlePlaceholder: 'Campaign title...',
      gameMaster: 'Game Master',
      gameMasterPlaceholder: 'GM name...',
      status: 'Status',
      statusAll: 'All statuses',
      statusPreparation: 'In preparation',
      statusInProgress: 'In progress',
      statusPaused: 'Paused',
      statusCompleted: 'Completed',
      reset: 'Reset',
    },
    count: {
      singular: 'game loaded',
      plural: 'games loaded',
      outOf: 'out of',
    },
    empty: {
      publicTitle: 'No games found',
      myAllTitle: 'You are not participating in any game',
      defaultTitle: 'No games',
      publicMessage: 'No public games match your criteria',
      myGamesMessage: 'You are not managing any game at the moment',
      playerGamesMessage: 'You are not participating in any game as a player',
      myAllMessage: 'You are not participating in any game at the moment',
      createFirst: 'Create your first game',
      resetFilters: 'Reset filters',
    },
  },

  play: {
    loading: 'Loading game...',
    tabs: {
      chat: 'Chat',
      players: 'Players',
      dice: 'Dice',
    },
    panel: {
      hide: 'Hide panel (chat, players, dice)',
      show: 'Show panel (chat, players, dice)',
      label: 'Panel',
    },
    leaveConfirm: 'Are you sure you want to leave this game?\n\nYou will be removed as a member and will no longer be able to access it.',
    mapLoadError: 'Unable to load map data',
  },

  card: {
    status: {
      preparation: 'In preparation',
      in_progress: 'In progress',
      paused: 'Paused',
      completed: 'Completed',
      archived: 'Archived',
    },
    private: 'Private',
    gameMaster: 'Game Master',
    campaignLevel: 'Campaign level: {level}',
    onlinePlayers: 'player(s) connected',
    joinButton: 'Join',
    fullButton: 'Full',
    deleteTitle: 'Delete game',
    deleteConfirm: 'Are you sure you want to delete "{name}"?\n\nThis action is permanent: all players, messages, and associated maps will be erased.',
    deleteError: 'Unable to delete this game',
    viewLabel: 'View game {name}',
    joinHint: 'Click "Join" to access this game',
  },

  header: {
    loading: 'Loading...',
    unknownGM: 'Unknown',
    connection: {
      connected: 'Connected',
      connecting: 'Connecting...',
      reconnecting: 'Reconnecting...',
      disconnected: 'Disconnected',
      error: 'Connection error',
      unknown: 'Unknown',
    },
    backButton: 'Back',
    backTitle: 'Back to game list',
    settingsTitle: 'Game settings',
    leaveButton: 'Leave game',
    leaveTitle: 'Permanently leave this game',
    help: {
      buttonLabel: 'Help',
      buttonTitle: 'Game user guide',
      modalTitle: 'Game Guide',
      gm: {
        title: 'Game Master Guide',
        settings: {
          title: '\u2699\ufe0f Game Settings',
          description:
            'Click the gear icon in the top bar to modify the game name, description, maximum players and visibility (public/private). Only available during the preparation phase.',
        },
        maps: {
          title: '\ud83d\uddfa\ufe0f Map Management',
          description:
            'Use the toolbar above the map to create a new map, upload an image, edit or delete an existing map. You can set the active map that all players will see. Configure the grid (color, opacity) from map settings.',
        },
        tokens: {
          title: '\ud83c\udfad Token Management',
          description:
            'Select the placement tool from the toolbar, then click on the map to place a token. You can create tokens for NPCs and monsters, edit them (name, color, size) and move them freely on the map.',
        },
        players: {
          title: '\ud83d\udc65 Player Management',
          description:
            'From the Players panel, you can invite new players by username, view their connection status and kick a player if necessary. Invitations will appear in the player\'s notifications.',
        },
        chat: {
          title: '\ud83d\udcac Chat & System Messages',
          description:
            'As GM, you have access to all standard chat commands (/roll, /whisper, /me) as well as the ability to send system messages visible to everyone. Use In Character mode to speak as an NPC.',
        },
        gameControl: {
          title: '\ud83c\udfae Game Control',
          description:
            'You can start the game once at least one player has joined (during preparation phase). The "Leave game" button permanently removes you \u2014 this action is irreversible.',
        },
      },
      player: {
        title: 'Player Guide',
        chat: {
          title: '\ud83d\udcac Chat',
          description:
            'Send messages by typing in the text area and pressing Enter. Use Shift+Enter for a new line. Toggle between "In Character" (speak as your character) and "Out of Character" mode with the IC/OOC button.',
        },
        dice: {
          title: '\ud83c\udfb2 Dice Roller',
          description:
            'The Dice panel lets you quickly roll predefined dice (d4 to d20), use common rolls (Initiative, Attack, Damage...) or compose a custom formula. You can also roll from chat with /roll or /r followed by the formula.',
        },
        map: {
          title: '\ud83d\uddfa\ufe0f Map & Tokens',
          description:
            'View the map shared by the GM. Use the scroll wheel to zoom, and click-drag to navigate. Your tokens are visible on the map if the GM has placed them for you.',
        },
        leave: {
          title: '\ud83d\udeaa Leave Game',
          description:
            'The "Leave game" button in the top bar permanently removes you from the game. This action is irreversible. The "Back" button takes you to the game list without leaving.',
        },
      },
      close: 'Close',
    },
  },

  chat: {
    empty: {
      title: 'No messages yet',
      subtitle: 'Be the first to speak!',
    },
    scrollDown: 'Scroll to bottom',
    inCharacter: 'In Character',
    outOfCharacter: 'Out of Character',
    help: {
      tooltip: '/roll \u2022 /whisper \u2022 /me \u2022 Click for details',
      modalTitle: 'Chat Commands',
      commands: {
        title: 'Commands',
        roll: {
          name: '/roll (/r)',
          syntax: '1d20, 2d6+3, 3d8...',
          description: 'Roll dice using standard notation. The result is visible to all players.',
        },
        whisper: {
          name: '/whisper (/w)',
          syntax: '/w username message',
          description: 'Send a private message to a specific player. Only you and the recipient can see it.',
        },
        whisperRoll: {
          name: '/w username /r',
          syntax: '/w username /r 1d20+5',
          description: 'Roll dice privately. Only you and the recipient see the result.',
        },
        emote: {
          name: '/me',
          syntax: '/me action',
          description: 'Perform a narrative action. E.g.: "/me draws their sword" displays your username followed by the action.',
        },
      },
      dice: {
        title: 'Pre-built Dice',
        quickDice: 'Quick dice: d4, d6, d8, d10, d12, d20 \u2014 one click to roll.',
        commonRolls: 'Common rolls: Initiative, Attack, Damage (sword/bow), Saving throw, Healing.',
        commonRollsHint: 'Available in the Dice panel to the right of the chat.',
      },
      advantage: {
        title: 'Advantage / Disadvantage',
        normal: 'Normal \u2014 Roll dice normally.',
        advantage: 'Advantage \u2014 Roll 2 dice and keep the highest (2d20kh1).',
        disadvantage: 'Disadvantage \u2014 Roll 2 dice and keep the lowest (2d20kl1).',
        super: 'Super advantage \u2014 Roll 3 dice and keep the highest.',
      },
      modes: {
        title: 'Chat Modes',
        inCharacter: 'In Character (IC)',
        inCharacterDesc: 'Your messages appear as your character\'s words. Ideal for roleplay.',
        outOfCharacter: 'Out of Character (OOC)',
        outOfCharacterDesc: 'Your messages appear as yourself, the player. For out-of-game discussions.',
      },
      close: 'Close',
    },
    placeholder: 'Enter text... (Shift+Enter for new line)',
    sendTitle: 'Send (Enter)',
    closeError: 'Close',
    suggestions: {
      title: 'Suggestions (\u2191\u2193 to navigate, Tab/Enter to select)',
    },
    errors: {
      invalidWhisperFormat: 'Invalid format. Use: /w <username> <message> or /w <username> /r <formula>',
      emptyMessage: 'Message cannot be empty.',
      playerNotFound: 'Player "{pseudo}" not found in this game.',
      emptyDiceFormula: 'Dice formula cannot be empty.',
      sendFailed: 'An error occurred while sending.',
    },
  },

  dice: {
    title: 'Dice Roller',
    subtitle: 'Custom formula or quick shortcuts',
    quickDice: 'Quick dice',
    mode: 'Mode',
    advantageModes: {
      normal: 'Normal',
      advantage: 'Advantage',
      disadvantage: 'Disadvantage',
      super: 'Super advantage',
    },
    customFormula: 'Custom formula',
    formulaPlaceholder: '2d6, 1d20, 3d8...',
    clearFormula: 'Clear formula',
    addNumber: 'Add {num}',
    addDie: 'Add d (die)',
    addPlus: 'Add +',
    addMinus: 'Add -',
    backspace: 'Delete last character',
    modifier: 'Modifier',
    decreaseModifier: 'Decrease modifier',
    increaseModifier: 'Increase modifier',
    noFormula: 'No formula',
    rollInCharacter: 'Roll as character (IC)',
    rollButton: 'Roll {formula}',
    rollButtonDefault: 'Roll dice',
    rollError: 'Error rolling dice.',
    closeError: 'Close',
    commonRolls: 'Common rolls',
    presets: {
      initiative: 'Initiative',
      initiativeAdvantage: 'Initiative (advantage)',
      attack: 'Attack',
      attackDisadvantage: 'Attack (disadvantage)',
      damageSword: 'Damage (sword)',
      damageBow: 'Damage (bow)',
      savingThrow: 'Saving throw',
      healingPotion: 'Healing (potion)',
    },
  },

  create: {
    title: 'Create a new game',
    closeModal: 'Close modal',
    name: {
      label: 'Game name',
      placeholder: 'Ex: The Forgotten Dragons Campaign',
      error: 'Name must be at least 3 characters long',
    },
    description: {
      label: 'Description',
      placeholder: 'Describe your campaign, its universe, its atmosphere...',
    },
    maxPlayers: {
      label: 'Maximum number of players',
      suffix: 'max players',
    },
    visibility: {
      public: 'Public game',
      private: 'Private game',
      publicHint: 'Visible to all players in the public list',
      privateHint: 'Accessible only with the password',
    },
    password: {
      label: 'Password',
      placeholder: 'Game password',
      error: 'Password required for a private game',
    },
    cancel: 'Cancel',
    submit: {
      creating: 'Creating...',
      create: 'Create game',
    },
  },

  join: {
    title: 'Join game',
    closeModal: 'Close modal',
    gameMaster: 'Game Master:',
    public: 'Public',
    private: 'Private',
    players: '{current} / {max} players',
    full: 'Game full',
    password: {
      label: 'Password',
      placeholder: 'Enter the game password',
      error: 'Password is required',
    },
    publicHint: 'This is a public game, you can join directly without a password.',
    joinError: 'Unable to join the game',
    cancel: 'Cancel',
    submit: {
      joining: 'Joining...',
      full: 'Game full',
      join: 'Join',
    },
  },

  players: {
    title: 'Players',
    stats: {
      online: 'Online',
      members: 'Members',
      gm: 'GM',
    },
    roles: {
      gameMaster: 'GM',
      player: 'Player',
      spectator: 'Spectator',
      unknown: 'Unknown',
    },
    status: {
      online: 'Online',
      offline: 'Offline',
      pending: 'Pending',
    },
    timeAgo: {
      justNow: 'Just now',
      minutes: '{count} min ago',
      hours: '{count}h ago',
      days: '{count}d ago',
    },
    kick: {
      confirm: 'Are you sure you want to kick {pseudo} from the game?',
      title: 'Kick this player',
      error: 'Unable to kick this player',
    },
    empty: {
      title: 'No players in the game',
      subtitle: 'Invite your friends to join',
    },
    invite: {
      button: 'Invite a player',
      atCapacity: 'Game full ({current}/{max})',
    },
    spectators: {
      title: 'Spectators ({count})',
    },
  },

  settings: {
    title: 'Game settings',
    name: {
      label: 'Game name',
      placeholder: 'Ex: The Forgotten Forest',
    },
    description: {
      label: 'Description',
      placeholder: 'Describe your game...',
    },
    maxPlayers: {
      label: 'Maximum number of players',
      hint: 'players (1 to 20)',
    },
    errors: {
      nameRequired: 'Game name is required',
      maxPlayersRange: 'Number of players must be between 1 and 20',
      saveFailed: 'Error saving',
    },
    cancel: 'Cancel',
    submit: {
      saving: 'Saving...',
      save: 'Save',
    },
  },

  invite: {
    title: 'Invite a player',
    search: {
      label: 'Search by username',
      placeholder: 'Enter a username...',
    },
    alreadyMember: 'Already a member',
    error: 'Error inviting player',
    cancel: 'Cancel',
    submit: {
      sending: 'Sending...',
      send: 'Invite',
    },
  },

  uploadMap: {
    title: 'Create a map',
    subtitle: 'Upload the background image for your map',
    image: {
      label: 'Map image *',
      selectPrompt: 'Click to select an image',
      formats: 'JPEG, PNG, WebP or GIF (max 10 MB)',
      preview: 'Preview',
    },
    name: {
      label: 'Map name *',
      placeholder: 'Ex: Red Dragon Dungeon',
    },
    description: {
      label: 'Description',
      placeholder: 'Briefly describe your map...',
    },
    grid: {
      type: {
        label: 'Grid type',
        square: 'Square',
        hex: 'Hexagonal',
        none: 'None',
      },
      size: 'Cell size (px)',
      width: 'Width (cells)',
      height: 'Height (cells)',
    },
    errors: {
      invalidType: 'Please select an image (JPEG, PNG, WebP, GIF)',
      tooLarge: 'The image is too large (max 10 MB)',
      uploadFailed: 'Error uploading map',
    },
    cancel: 'Cancel',
    submit: {
      uploading: 'Uploading...',
      upload: 'Create map',
    },
  },

  editMap: {
    title: 'Edit map',
    subtitle: 'Modify your map information',
    image: {
      label: 'Map image',
      optional: '(Optional - Leave empty to keep the current image)',
      selectPrompt: 'Click to select a new image',
      formats: 'JPEG, PNG, WebP or GIF (max 10 MB)',
      newImage: 'New image: {name}',
      currentImage: 'Current image - Click \u2715 to change',
      changeTitle: 'Change image',
      preview: 'Preview',
    },
    name: {
      label: 'Map name *',
      placeholder: 'Ex: Red Dragon Dungeon',
    },
    description: {
      label: 'Description',
      placeholder: 'Briefly describe your map...',
    },
    grid: {
      type: {
        label: 'Grid type',
        square: 'Square',
        hex: 'Hexagonal',
        none: 'None',
      },
      size: 'Cell size (px)',
      width: 'Width (cells)',
      height: 'Height (cells)',
    },
    errors: {
      invalidType: 'Please select an image (JPEG, PNG, WebP, GIF)',
      tooLarge: 'The image is too large (max 10 MB)',
      updateFailed: 'Error updating map',
    },
    cancel: 'Cancel',
    submit: {
      updating: 'Updating...',
      update: 'Update',
    },
  },

  createToken: {
    title: 'Create a Token',
    position: 'Position: ({x}, {y})',
    name: {
      label: 'Token name *',
      placeholder: 'Ex: Goblin, Chest, etc.',
    },
    type: {
      label: 'Token type *',
      character: 'Character',
      monster: 'Monster',
      npc: 'NPC',
      object: 'Object',
    },
    size: {
      label: 'Size',
      small: 'Small (0.5)',
      medium: 'Medium (1)',
      large: 'Large (2)',
      huge: 'Huge (3)',
      gargantuan: 'Gargantuan (4)',
    },
    image: {
      label: 'Token image (optional)',
      selectPrompt: 'Click to select an image',
      formats: 'JPEG, PNG, WebP or GIF (max 5 MB)',
      deleteTitle: 'Delete image',
      noImageHint: 'If no image is provided, the name initials will be used',
    },
    errors: {
      invalidType: 'Please select an image (JPEG, PNG, WebP, GIF)',
      tooLarge: 'The image is too large (max 5 MB)',
      createFailed: 'Error creating token',
    },
    cancel: 'Cancel',
    submit: {
      creating: 'Creating...',
      create: 'Create Token',
    },
  },

  editToken: {
    title: 'Edit Token',
    name: {
      label: 'Token name *',
      placeholder: 'Ex: Goblin, Chest, etc.',
    },
    type: {
      label: 'Token type *',
      character: 'Character',
      monster: 'Monster',
      npc: 'NPC',
      object: 'Object',
    },
    size: {
      label: 'Size',
      small: 'Small (0.5)',
      medium: 'Medium (1)',
      large: 'Large (2)',
      huge: 'Huge (3)',
      gargantuan: 'Gargantuan (4)',
    },
    image: {
      label: 'Token image (optional)',
      selectPrompt: 'Click to select an image',
      formats: 'JPEG, PNG, WebP or GIF (max 5 MB)',
      newImage: 'New image: {name}',
      currentImage: 'Current image - Click \u2715 to change',
      changeTitle: 'Change image',
      preview: 'Preview',
    },
    errors: {
      invalidType: 'Please select an image (JPEG, PNG, WebP, GIF)',
      tooLarge: 'The image is too large (max 5 MB)',
      updateFailed: 'Error updating token',
    },
    cancel: 'Cancel',
    submit: {
      updating: 'Updating...',
      update: 'Update',
    },
  },

  emptyMap: {
    title: 'No active map',
    gmMessage: 'This game does not have a map yet. Upload a map to start playing and place tokens.',
    playerMessage: 'The Game Master has not yet created a map for this session. Please wait while they prepare the playing field.',
    createButton: 'Create a map',
    tips: {
      title: 'Tip',
      formats: 'Supported formats: JPEG, PNG, WebP, GIF',
      maxSize: 'Max size: 10 MB',
      resolution: 'Use an appropriate resolution (1920x1080 recommended)',
    },
  },

  toolbar: {
    map: 'Map',
    mapTitle: 'Map management',
    tools: 'Tools',
    toolsTitle: 'Drawing tools',
    toolNames: {
      select: 'Select',
      token: 'Add Token',
      fog: 'Fog',
      measure: 'Measure',
      draw: 'Draw',
      ping: 'Ping',
    },
    zoom: {
      out: 'Zoom out',
      in: 'Zoom in',
      customTitle: 'Click to enter a custom value',
    },
    center: 'Center',
    centerTitle: 'Center view',
    mapSelector: {
      noActiveMap: 'No active map',
      selectTitle: 'Select a map',
      searchPlaceholder: 'Filter maps...',
      active: 'Active',
      editTitle: 'Edit this map',
      deleteTitle: 'Delete this map',
      deleteConfirm: 'Are you sure you want to delete the map "{name}"?',
      deleteError: 'Error deleting map',
      noMaps: 'No maps found',
    },
    newMap: 'New map',
    newMapTitle: 'Create a new map',
    grid: {
      button: 'Grid',
      buttonTitle: 'Grid settings',
      settingsTitle: 'Grid settings',
      showGrid: 'Show grid',
      color: 'Color',
      opacity: 'Opacity',
    },
  },

  map: {
    tokenActions: {
      edit: 'Edit',
      editTitle: 'Edit token',
      visibility: 'Visibility',
      visibilityTitle: 'Toggle visibility',
      lock: 'Lock',
      lockTitle: 'Lock/Unlock',
      permissions: 'Permissions',
      permissionsTitle: 'Manage permissions',
      delete: 'Delete',
      deleteTitle: 'Delete',
      deleteConfirm: 'Delete this token?',
    },
    permissions: {
      title: 'Control permissions',
      close: 'Close',
      description: 'Select the players who can control this token with the arrow keys',
      noPlayers: 'No players available',
      closeButton: 'Close',
    },
  },

  stores: {
    game: {
      fetchPublicError: 'Error loading games',
      fetchMyError: 'Error loading your games',
      fetchByIdError: 'Game not found',
      createError: 'Error creating game',
      updateError: 'Error updating game',
      deleteError: 'Error deleting game',
      joinError: 'Unable to join game',
      leaveError: 'Unable to leave game',
      startError: 'Unable to start game',
      appendError: 'Error loading games',
    },
    chat: {
      loadError: 'Error loading messages',
      loadNewError: 'Error loading new messages',
      sendError: 'Error sending message',
      sendEmoteError: 'Error sending emote',
      sendWhisperError: 'Error sending whisper',
      sendSystemError: 'Error sending system message',
      rollError: 'Error rolling dice',
      deleteError: 'Error deleting message',
    },
    map: {
      loadMapsError: 'Error loading maps',
      loadActiveError: 'Error loading active map',
      loadMapError: 'Error loading map',
      activateError: 'Error activating map',
      deleteError: 'Error deleting map',
      updateSettingsError: 'Error updating map settings',
      loadTokensError: 'Error loading tokens',
      createTokenError: 'Error creating token',
      moveTokenError: 'Error moving token',
      updateTokenError: 'Error updating token',
      deleteTokenError: 'Error deleting token',
      visibilityError: 'Error changing visibility',
      lockError: 'Error changing lock state',
      permissionsError: 'Error managing permissions',
    },
  },
}
