import i18n from '@/i18n'

/**
 * Types pour la gestion des erreurs API
 */

/**
 * Erreur de validation d'un champ
 */
export interface ValidationError {
  field?: string
  propertyPath?: string
  message: string
  invalidValue?: unknown
}

/**
 * Erreur API générique
 * Consolidation de tous les formats d'erreurs possibles du backend
 */
export interface ApiError {
  error?: string
  message?: string

  code?: string
  statusCode?: number

  violations?: ValidationError[]

  errors?: Record<string, string[]>

  response?: {
    data?: {
      error?: string
      message?: string
      violations?: ValidationError[]
    }
    status?: number
    statusText?: string
  }
}

/**
 * Type guard pour vérifier si une erreur est une ApiError
 */
export function isApiError(error: unknown): error is ApiError {
  return (
    typeof error === 'object' &&
    error !== null &&
    ('error' in error || 'message' in error || 'response' in error)
  )
}

/**
 * Extrait le message d'erreur d'une ApiError
 */
export function getErrorMessage(error: unknown): string {
  if (!isApiError(error)) {
    return i18n.global.t('common.errors.unexpected')
  }

  if (error.response?.data?.error) {
    return error.response.data.error
  }

  if (error.response?.data?.message) {
    return error.response.data.message
  }

  if (error.error) {
    return error.error
  }

  if (error.message) {
    return error.message
  }

  if (error.violations && error.violations.length > 0) {
    return error.violations.map((v) => v.message).join(', ')
  }

  return i18n.global.t('common.errors.generic')
}
