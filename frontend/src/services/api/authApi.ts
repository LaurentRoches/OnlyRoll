import { apiClient } from './apiClient'
import type { LoginCredentials, RegisterCredentials, MeResponse } from '@/types/auth'

/**
 * Type de réponse adapté au nouveau format avec cookie
 */
export interface LoginResponse {
  success: boolean
  message: string
  user?: {
    id: number
    email: string
    pseudo: string
  }
}

export interface RegisterResponse {
  message: string
  user: {
    id: number
    email: string
    pseudo: string
  }
}

/**
 * Service API pour l'authentification
 */
export const authApi = {
  /**
   * Connexion de l'utilisateur
   */
  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    return apiClient.post<LoginResponse>('/login', credentials).then((res) => res.data)
  },

  /**
   * Inscription d'un nouvel utilisateur
   */
  async register(credentials: RegisterCredentials): Promise<RegisterResponse> {
    return apiClient.post<RegisterResponse>('/register', credentials).then((res) => res.data)
  },

  /**
   * Récupération des informations de l'utilisateur connecté
   */
  async me(): Promise<MeResponse> {
    return apiClient.get<MeResponse>('/me').then((res) => res.data)
  },

  /**
   * Déconnexion de l'utilisateur
   */
  async logout(): Promise<{ message: string }> {
    return apiClient.post<{ message: string }>('/logout').then((res) => res.data)
  },

  /**
   * Vérifie un token de validation d'email
   */
  async verifyEmail(token: string): Promise<{ message: string }> {
    return apiClient
      .post<{ message: string }>('/auth/verify-email', { token })
      .then((res) => res.data)
  },

  /**
   * Renvoie l'email de vérification.
   * Passer l'email si l'utilisateur n'est pas connecté (ex: post-inscription).
   */
  async resendVerification(email?: string): Promise<{ message: string }> {
    return apiClient
      .post<{ message: string }>('/auth/resend-verification', email ? { email } : {})
      .then((res) => res.data)
  },

  /**
   * Demande de réinitialisation de mot de passe
   */
  async forgotPassword(email: string): Promise<{ message: string }> {
    return apiClient
      .post<{ message: string }>('/auth/forgot-password', { email })
      .then((res) => res.data)
  },

  /**
   * Réinitialisation du mot de passe via token
   */
  async resetPassword(token: string, password: string): Promise<{ message: string }> {
    return apiClient
      .post<{ message: string }>('/auth/reset-password', { token, password })
      .then((res) => res.data)
  },
}
