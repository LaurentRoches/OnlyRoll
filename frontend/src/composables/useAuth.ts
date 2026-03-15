import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { LoginCredentials, RegisterCredentials } from '@/types/auth'
import type { ApiError } from '@/services/api/apiClient'
import i18n from '@/i18n'

/**
 * Composable pour la gestion de l'authentification
 * Fournit une interface simplifiée pour interagir avec le store d'authentification
 */
export const useAuth = () => {
  const authStore = useAuthStore()
  const router = useRouter()

  const user = computed(() => authStore.user)
  const isAuthenticated = computed(() => authStore.isAuthenticated)
  const isLoading = computed(() => authStore.isLoading)
  const error = computed(() => authStore.error)

  /**
   * Connexion d'un utilisateur
   * @param credentials - Identifiants de connexion (email, password)
   * @param redirectTo - Route de redirection après connexion (par défaut: dashboard)
   */
  const login = async (credentials: LoginCredentials, redirectTo?: string) => {
    try {
      await authStore.login(credentials)
      const destination = redirectTo || { name: 'dashboard' }
      await router.push(destination)
    } catch (err) {
      throw err
    }
  }

  /**
   * Inscription d'un nouvel utilisateur
   * @param credentials - Données d'inscription (pseudo, email, password, confirmPassword)
   */
  const register = async (credentials: RegisterCredentials) => {
    try {
      await authStore.register(credentials)
      await router.push({
        name: 'register-success',
        query: { email: credentials.email },
      })
    } catch (err) {
      throw err
    }
  }

  /**
   * Déconnexion de l'utilisateur
   * @param redirectTo - Route de redirection après déconnexion (par défaut: home)
   */
  const logout = async (redirectTo?: string) => {
    try {
      await authStore.logout()
      const destination = redirectTo || { name: 'home' }
      await router.push(destination)
    } catch (err) {
      console.error('Erreur lors de la déconnexion:', err)
      const destination = redirectTo || { name: 'home' }
      await router.push(destination)
    }
  }

  /**
   * Vérifie si l'utilisateur possède un rôle spécifique
   * @param role - Nom du rôle à vérifier
   */
  const hasRole = (role: string): boolean => {
    return authStore.hasRole(role)
  }

  /**
   * Vérifie si l'utilisateur possède l'un des rôles requis
   * @param roles - Liste des rôles à vérifier
   */
  const hasAnyRole = (roles: string[]): boolean => {
    return authStore.hasAnyRole(roles)
  }

  /**
   * Vérifie si l'utilisateur possède tous les rôles requis
   * @param roles - Liste des rôles à vérifier
   */
  const hasAllRoles = (roles: string[]): boolean => {
    return authStore.hasAllRoles(roles)
  }

  /**
   * Guard de navigation - Requiert l'authentification
   * Redirige vers la page de login si non authentifié
   * @returns true si authentifié, false sinon
   */
  const requireAuth = (): boolean => {
    if (!isAuthenticated.value) {
      router.push({
        name: 'login',
        query: { redirect: router.currentRoute.value.fullPath },
      })
      return false
    }
    return true
  }

  /**
   * Guard de navigation - Requiert l'absence d'authentification
   * Redirige vers le dashboard si déjà authentifié
   * @returns true si non authentifié, false sinon
   */
  const requireGuest = (): boolean => {
    if (isAuthenticated.value) {
      router.push({ name: 'dashboard' })
      return false
    }
    return true
  }

  /**
   * Efface l'erreur actuelle du store
   */
  const clearError = (): void => {
    authStore.clearError()
  }

  /**
   * Définit une erreur dans le store
   * @param message - Message d'erreur à afficher
   */
  const setError = (message: string): void => {
    authStore.setError(message)
  }

  /**
   * Extrait un message d'erreur lisible depuis différents formats d'erreur
   * Compatible avec ApiError, Error natif, et objets personnalisés
   * @param error - Erreur à traiter
   * @returns Message d'erreur formaté
   */
  const getErrorMessage = (error: unknown): string => {
    if (typeof error === 'string') {
      return error
    }

    if (error && typeof error === 'object') {
      const apiError = error as ApiError

      if ('message' in apiError && typeof apiError.message === 'string') {
        return apiError.message
      }

      if ('error' in apiError && typeof apiError.error === 'string') {
        return apiError.error
      }
    }

    if (error instanceof Error) {
      return error.message
    }

    return i18n.global.t('auth.composable.unexpectedError')
  }

  /**
   * Formatte une erreur pour l'affichage utilisateur
   * Inclut le code de statut si disponible
   * @param error - Erreur à formater
   * @returns Message d'erreur formaté avec contexte
   */
  const formatError = (error: unknown): string => {
    const message = getErrorMessage(error)

    if (error && typeof error === 'object' && 'statusCode' in error) {
      const statusCode = (error as ApiError).statusCode
      if (statusCode && statusCode !== 0) {
        return `[${statusCode}] ${message}`
      }
    }

    return message
  }

  /**
   * Vérifie si l'erreur est de type authentification (401)
   * @param error - Erreur à vérifier
   */
  const isAuthError = (error: unknown): boolean => {
    return (
      error !== null &&
      typeof error === 'object' &&
      'statusCode' in error &&
      (error as ApiError).statusCode === 401
    )
  }

  /**
   * Vérifie si l'erreur est de type validation (422)
   * @param error - Erreur à vérifier
   */
  const isValidationError = (error: unknown): boolean => {
    return (
      error !== null &&
      typeof error === 'object' &&
      'statusCode' in error &&
      (error as ApiError).statusCode === 422
    )
  }

  return {
    user,
    isAuthenticated,
    isLoading,
    error,

    login,
    register,
    logout,

    hasRole,
    hasAnyRole,
    hasAllRoles,

    requireAuth,
    requireGuest,

    clearError,
    setError,
    getErrorMessage,
    formatError,
    isAuthError,
    isValidationError,
  }
}
