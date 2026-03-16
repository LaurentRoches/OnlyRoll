export default {
  skipLink: 'Skip to main content',

  header: {
    title: 'My Profile',
    subtitle: 'Manage your personal information',
    backToDashboard: 'Back to dashboard',
  },

  loading: {
    ariaLabel: 'Loading',
    screenReader: 'Loading profile...',
  },

  error: {
    label: 'Error:',
    retry: 'Retry',
    loadProfile: 'Error loading profile',
    generic: 'An error occurred.',
  },

  avatar: {
    sectionTitle: 'Profile picture',
    altWithAvatar: '{pseudo}\'s avatar',
    altWithoutAvatar: 'No avatar set',
    uploadInProgress: 'Upload in progress',
    changeButton: 'Change avatar',
    addButton: 'Add an avatar',
    deleteButton: 'Delete avatar',
    helpText: 'Accepted formats: JPEG, PNG, GIF, WebP. Maximum size: 2 MB.',
    errors: {
      invalidType: 'File type not allowed. Accepted formats: JPEG, PNG, GIF, WebP.',
      tooLarge: 'The file is too large. Maximum size: 2 MB.',
      uploadFailed: 'Error uploading avatar.',
      deleteFailed: 'Error deleting avatar.',
    },
    success: {
      uploaded: 'Avatar updated successfully.',
      deleted: 'Avatar deleted successfully.',
    },
  },

  info: {
    sectionTitle: 'Personal information',
    pseudo: {
      label: 'Username',
      required: '(required)',
      errors: {
        required: 'Username is required',
        minLength: 'Username must be at least 3 characters long',
      },
    },
    email: {
      label: 'Email address',
      hint: 'Email address cannot be changed.',
    },
    timezone: {
      label: 'Timezone',
    },
    language: {
      label: 'Preferred language',
      options: {
        fr: 'Fran\u00e7ais',
        en: 'English',
      },
    },
    success: 'Your information has been updated successfully.',
    submit: {
      saving: 'Saving...',
      save: 'Save changes',
    },
  },

  password: {
    sectionTitle: 'Change password',
    currentPassword: {
      label: 'Current password',
      required: '(required)',
      toggleShow: 'Show current password',
      toggleHide: 'Hide current password',
      errors: {
        required: 'Current password is required',
      },
    },
    newPassword: {
      label: 'New password',
      required: '(required)',
      toggleShow: 'Show new password',
      toggleHide: 'Hide new password',
      generate: 'Generate',
      generating: 'Generating...',
      errors: {
        required: 'New password is required',
        invalid: 'Password does not meet security requirements',
      },
    },
    confirmPassword: {
      label: 'Confirm new password',
      required: '(required)',
      toggleShow: 'Show confirmation',
      toggleHide: 'Hide confirmation',
      errors: {
        required: 'Password confirmation is required',
        mismatch: 'Passwords do not match',
      },
    },
    strength: {
      ariaLabel: 'Password strength: {label}',
      weak: 'Weak',
      medium: 'Medium',
      good: 'Good',
      strong: 'Strong',
      valid: 'Password meets requirements',
      feedback: {
        minLength: 'At least 12 characters required',
        lowercase: 'At least one lowercase letter required',
        uppercase: 'At least one uppercase letter required',
        digit: 'At least one digit required',
        special: "At least one special character required (!{'@'}#$%&*()_+-=[]{}|;:,.<>?)",
      },
    },
    submit: {
      changing: 'Changing...',
      change: 'Change password',
    },
    success: 'Your password has been changed successfully.',
    errors: {
      generic: 'An error occurred while changing the password.',
      fallback: 'An error occurred.',
    },
  },

  account: {
    sectionTitle: 'Account information',
    memberSince: 'Member since',
    lastLogin: 'Last login',
    lastLoginNever: 'Never',
    accountStatus: 'Account status',
    verified: 'Verified',
    pendingVerification: 'Pending verification',
    roles: 'Roles',
  },
}
