import { apiClient } from './apiClient'

/**
 * Types pour l'API de profil utilisateur
 */
export interface UserProfile {
  id: number
  email: string
  pseudo: string
  roles: string[]
  isVerified: boolean
  timezone: string
  language: string
  avatar: string | null
  createdAt: string
  lastLogin: string | null
}

export interface ProfileUpdateData {
  pseudo?: string
  timezone?: string
  language?: string
}

export interface PasswordChangeData {
  currentPassword: string
  newPassword: string
  confirmPassword: string
}

/**
 * Service API pour la gestion du profil utilisateur
 */
export const profileApi = {
  /**
   * Récupère le profil de l'utilisateur connecté
   */
  async get(): Promise<UserProfile> {
    return apiClient.get<UserProfile>('/profile').then((res) => res.data)
  },

  /**
   * Met à jour le profil de l'utilisateur
   */
  async update(data: ProfileUpdateData): Promise<UserProfile> {
    return apiClient.put<UserProfile>('/profile', data).then((res) => res.data)
  },

  /**
   * Change le mot de passe de l'utilisateur
   */
  async changePassword(data: PasswordChangeData): Promise<{ message: string }> {
    return apiClient.put<{ message: string }>('/profile/password', data).then((res) => res.data)
  },

  /**
   * Upload un avatar
   */
  async uploadAvatar(file: File): Promise<{ message: string; avatar: string }> {
    const formData = new FormData()
    formData.append('avatar', file)

    return apiClient
      .post<{ message: string; avatar: string }>('/profile/avatar', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      .then((res) => res.data)
  },

  /**
   * Supprime l'avatar de l'utilisateur
   */
  async deleteAvatar(): Promise<{ message: string }> {
    return apiClient.delete<{ message: string }>('/profile/avatar').then((res) => res.data)
  },
}
