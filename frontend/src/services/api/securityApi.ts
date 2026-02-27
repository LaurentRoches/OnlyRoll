import { get, post } from './apiClient'

/**
 * Options de génération de mot de passe
 */
export interface GeneratePasswordOptions {
  length?: number
  includeLowercase?: boolean
  includeUppercase?: boolean
  includeDigits?: boolean
  includeSpecial?: boolean
  excludeAmbiguous?: boolean
  count?: number
}

/**
 * Réponse de génération d'un seul mot de passe
 */
export interface GeneratePasswordResponse {
  password: string
  options: GeneratePasswordOptions
}

/**
 * Réponse de génération de plusieurs mots de passe
 */
export interface GenerateMultiplePasswordsResponse {
  passwords: string[]
  options: GeneratePasswordOptions
}

/**
 * Résultat d'évaluation de la force d'un mot de passe
 */
export interface PasswordStrengthResult {
  score: number
  strength: 'très faible' | 'faible' | 'moyen' | 'fort' | 'excellent'
  feedback: string[]
  isValid: boolean
}

/**
 * Options par défaut pour la génération de mot de passe
 */
export interface PasswordOptionsResponse {
  defaults: {
    length: number
    minLength: number
    maxLength: number
    includeLowercase: boolean
    includeUppercase: boolean
    includeDigits: boolean
    includeSpecial: boolean
    excludeAmbiguous: boolean
  }
  specialCharacters: string
  ambiguousCharacters: string[]
}

/**
 * API de sécurité pour la génération et l'évaluation des mots de passe
 */
export const securityApi = {
  /**
   * Génère un mot de passe sécurisé
   */
  generatePassword: async (
    options?: GeneratePasswordOptions
  ): Promise<GeneratePasswordResponse | GenerateMultiplePasswordsResponse> => {
    return post<GeneratePasswordResponse | GenerateMultiplePasswordsResponse>(
      '/security/generate-password',
      options || {}
    )
  },

  /**
   * Évalue la force d'un mot de passe
   */
  evaluatePassword: async (password: string): Promise<PasswordStrengthResult> => {
    return post<PasswordStrengthResult>('/security/evaluate-password', { password })
  },

  /**
   * Récupère les options par défaut de génération
   */
  getPasswordOptions: async (): Promise<PasswordOptionsResponse> => {
    return get<PasswordOptionsResponse>('/security/password-options')
  },
}
