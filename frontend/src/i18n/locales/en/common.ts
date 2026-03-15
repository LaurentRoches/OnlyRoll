export default {
  errors: {
    sessionExpired: 'Session expired, automatic logout',
    generic: 'An error occurred',
    unexpected: 'An unexpected error occurred',
    unexpectedOccurred: 'An unexpected error occurred',
  },

  notFound: {
    title: '404',
    heading: 'Critical failure!',
    description:
      'You rolled a {roll} on your navigation check... This page seems to have been devoured by a Red Dragon.',
    rollValue: '1',
    suggestions: 'Maybe you are looking for:',
    login: 'Log in',
    register: 'Sign up',
    dashboard: 'Dashboard',
    wiki: 'D&D Wiki',
    back: 'Back',
    home: 'Home',
    rerollQuote: 'Roll the dice for a new quote',
    quotes: {
      0: {
        text: 'A forewarned adventurer is worth two!',
        author: 'Tavern proverb',
      },
      1: {
        text: 'Better to be lost with a map than found without direction.',
        author: 'Wise Ranger',
      },
      2: {
        text: 'Even the greatest heroes sometimes lose their way.',
        author: "Astarion's Chronicles",
      },
      3: {
        text: "It's not the mistake that counts, it's how you recover from it.",
        author: "Dungeon Master's Guide",
      },
      4: {
        text: 'Sometimes, getting lost leads to the greatest discoveries.',
        author: "An Explorer's Journal",
      },
      5: {
        text: 'A closed path often hides another.',
        author: 'Elven proverb',
      },
    },
  },

  nav: {
    dashboard: 'Dashboard',
    games: 'Games',
    admin: 'Admin',
    logout: 'Logout',
    notifications: 'Notifications',
    navigationMenu: 'Navigation menu',
    invitations: {
      title: 'Pending invitations',
      loading: 'Loading...',
      empty: 'No pending invitations',
      gameMaster: 'GM: {pseudo}',
      accept: 'Join',
      decline: 'Decline',
      acceptError: 'Error accepting the invitation',
      declineError: 'Error declining the invitation',
    },
  },

  dashboardCard: {
    comingSoon: 'Coming soon',
  },

  userProfile: {
    avatarAlt: "{pseudo}'s avatar",
    myProfile: 'My profile',
    settings: 'Settings',
    loggingOut: 'Logging out...',
    logoutButton: 'Log out',
  },

  konami: {
    message: "You've discovered the easter egg!",
  },

  accessibleTable: {
    emptyMessage: 'No data available',
    regionLabel: 'Data table',
    description: 'This table contains {count} row. | This table contains {count} rows.',
    sortHint: 'Click column headers to sort data.',
    sortedAsc: 'sorted ascending',
    sortedDesc: 'sorted descending',
    sortAnnouncement: 'Table sorted by {column}, {direction}',
    ascending: 'ascending',
    descending: 'descending',
  },
}
