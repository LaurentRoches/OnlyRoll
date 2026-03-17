export default {
  dashboard: {
    title: 'Dashboard',
    subtitle: 'Administration overview',
    loading: 'Loading',
    loadingData: 'Loading data...',
    errorLabel: 'Error:',
    errorLoading: 'Error loading data',

    users: {
      heading: 'Users',
      total: 'Total',
      totalSrOnly: '{count} total users',
      active: 'Active',
      activeSrOnly: '{count} active users',
      thisWeek: 'This week',
      thisWeekSrOnly: '{count} new users this week',
      admins: 'Administrators',
      adminsSrOnly: '{count} administrators',
    },

    audit: {
      heading: 'Audit',
      total: 'Total',
      totalSrOnly: '{count} total audit events',
      today: 'Today',
      todaySrOnly: '{count} events today',
      warnings: 'Warnings',
      warningsSrOnly: '{count} warnings today',
      failedLogins: 'Login failures',
      failedLoginsSrOnly: '{count} login failures today',
    },

    recentActivity: {
      heading: 'Recent activity',
      caption: 'Latest actions recorded in the system',
      emptyMessage: 'No recent activity',
      systemUser: 'System',
    },

    columns: {
      action: 'Action',
      user: 'User',
      date: 'Date',
    },
  },

  users: {
    title: 'User management',
    totalUsers: '{count} total users',
    searchAriaLabel: 'Filter users',
    searchLabel: 'Search for a user',
    searchPlaceholder: 'Search...',
    loading: 'Loading',
    loadingUsers: 'Loading users...',
    errorLabel: 'Error:',
    errorLoading: 'Error loading',
    errorDeleting: 'Error deleting',
    errorRestoring: 'Error restoring',

    filters: {
      statusLabel: 'Filter by status',
      allStatuses: 'All statuses',
      active: 'Active',
      inactive: 'Inactive',
      deleted: 'Deleted',
      locked: 'Locked',
      roleLabel: 'Filter by role',
      allRoles: 'All roles',
      users: 'Users',
      administrators: 'Administrators',
    },

    columns: {
      id: 'ID',
      pseudo: 'Username',
      email: 'Email',
      roles: 'Roles',
      status: 'Status',
      lastLogin: 'Last login',
      actions: 'Actions',
    },

    table: {
      caption: 'User list with their information and available actions',
      emptyMessage: 'No users found',
    },

    status: {
      deleted: 'Deleted',
      locked: 'Locked',
      active: 'Active',
      inactive: 'Inactive',
    },

    lastLoginNever: 'Never',

    actions: {
      restore: 'Restore',
      restoreAriaLabel: 'Restore user {pseudo}',
      delete: 'Delete',
      deleteAriaLabel: 'Delete user {pseudo}',
    },

    deleteModal: {
      title: 'Delete {pseudo}?',
      titleFallback: 'Delete the user?',
      message:
        'Are you sure you want to delete this user? This action is reversible (soft delete).',
      cancel: 'Cancel',
      confirm: 'Delete',
    },

    pagination: {
      ariaLabel: 'User pagination',
    },
  },

  auditLogs: {
    title: 'Audit logs',
    subtitle: 'System action history',
    searchAriaLabel: 'Filter audit logs',
    loading: 'Loading',
    loadingLogs: 'Loading logs...',
    errorLabel: 'Error:',
    errorLoading: 'Error loading',

    filters: {
      actionLabel: 'Action',
      allActions: 'All actions',
      severityLabel: 'Severity',
      allSeverities: 'All severities',
      severityInfo: 'Info',
      severityWarning: 'Warning',
      severityHigh: 'High',
      dateFrom: 'Start date',
      dateTo: 'End date',
      reset: 'Reset',
    },

    columns: {
      date: 'Date',
      action: 'Action',
      user: 'User',
      target: 'Target',
      ip: 'IP',
      details: 'Details',
    },

    table: {
      caption: 'System audit event history',
      emptyMessage: 'No logs found for these criteria',
    },

    systemUser: 'System',
    noTarget: '-',
    noIp: '-',
    viewDetails: 'View details',
    viewDetailsAriaLabel: 'View details for log {id}',

    detailModal: {
      title: 'Log details',
      id: 'ID',
      action: 'Action',
      user: 'User',
      target: 'Target',
      date: 'Date',
      ipAddress: 'IP address',
      details: 'Details',
      userAgent: 'User Agent',
      close: 'Close',
    },

    pagination: {
      ariaLabel: 'Audit log pagination',
    },
  },
}
