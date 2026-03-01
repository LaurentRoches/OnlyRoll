/**
 * Service API pour les opérations liées aux utilisateurs (recherche, invitations)
 */
import { get } from './apiClient'
import type { GameInvitation, UserSearchResult } from '@/types/game'

export const userApi = {
  /**
   * Recherche des utilisateurs par pseudo (autocomplétion pour invitations)
   */
  async searchUsers(pseudo: string): Promise<UserSearchResult[]> {
    const params = new URLSearchParams({ pseudo })
    const response = await get<{ data: UserSearchResult[] }>(`/users/search?${params}`)
    return response.data
  },

  /**
   * Récupère les invitations en attente de l'utilisateur connecté
   */
  async getInvitations(): Promise<GameInvitation[]> {
    const response = await get<{ data: GameInvitation[] }>('/user/invitations')
    return response.data
  },

  /**
   * Génère un token Mercure pour les notifications personnelles
   */
  async getNotificationToken(): Promise<{ token: string; expiresIn: number }> {
    return get<{ token: string; expiresIn: number }>('/user/mercure-notification-token')
  },
}
