/**
 * Point d'entrée principal pour tous les services API OnlyRoll
 */
import { authApi } from './authApi'
import { chatApi } from './chatApi'
import { gameApi } from './gameApi'
import { mapApi } from './mapApi'
import { tokenApi } from './tokenApi'
import { securityApi } from './securityApi'
import { adminApi } from './adminApi'
import { profileApi } from './profileApi'

// Export du client HTTP de base
export { apiClient } from './apiClient'
export type { ApiError } from './apiClient'

// Export des services
export { authApi } from './authApi'
export { gameApi } from './gameApi'
export { mapApi } from './mapApi'
export { tokenApi } from './tokenApi'
export { chatApi } from './chatApi'
export { securityApi } from './securityApi'
export { adminApi } from './adminApi'
export { profileApi } from './profileApi'

export type { CreateMapDTO, UpdateMapDTO } from './mapApi'

export type { GetMessagesOptions } from './chatApi'

export type {
  CreateTokenDTO,
  UpdateTokenDTO,
  MoveTokenDTO,
  SendMessageDTO,
  RollDiceDTO,
} from '@/types/game'

// Export des types admin
export type {
  UserStats,
  AuditStats,
  GameStats,
  DashboardStats,
  AdminUser,
  UserFilterParams,
  UserUpdateData,
  PaginatedResponse,
  AuditLogEntry,
  AuditLogFilterParams,
  AuditActionInfo,
} from './adminApi'

// Export des types profile
export type { UserProfile, ProfileUpdateData, PasswordChangeData } from './profileApi'

/**
 * Objet regroupant tous les services pour un usage simplifié
 */
export const api = {
  auth: authApi,
  games: gameApi,
  maps: mapApi,
  tokens: tokenApi,
  chat: chatApi,
  security: securityApi,
  admin: adminApi,
  profile: profileApi,
}
