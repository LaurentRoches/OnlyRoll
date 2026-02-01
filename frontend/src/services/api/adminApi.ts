import { apiClient } from './apiClient'

/**
 * Types pour l'API d'administration
 */
export interface UserStats {
  total: number
  active: number
  inactive: number
  deleted: number
  locked: number
  admins: number
  newThisWeek: number
  activeToday: number
}

export interface AuditStats {
  total: number
  today: number
  warningsToday: number
  highSeverityToday: number
  failedLoginsToday: number
}

export interface GameStats {
  total: number
  public: number
}

export interface DashboardStats {
  users: UserStats
  audit: AuditStats
  games: GameStats
}

export interface AdminUser {
  id: number
  email: string
  pseudo: string
  roles: string[]
  isActive: boolean
  isVerified: boolean
  deletedAt: string | null
  createdAt: string
  lastLogin: string | null
}

export interface UserFilterParams {
  search?: string
  status?: 'all' | 'active' | 'inactive' | 'deleted' | 'locked'
  role?: 'ROLE_USER' | 'ROLE_ADMIN'
  page?: number
  limit?: number
  sortBy?: 'id' | 'email' | 'pseudo' | 'createdAt' | 'lastLogin'
  sortDirection?: 'ASC' | 'DESC'
}

export interface UserUpdateData {
  pseudo?: string
  email?: string
  isActive?: boolean
  isVerified?: boolean
  roles?: string[]
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    total: number
    page: number
    limit: number
    totalPages: number
  }
}

export interface AuditLogEntry {
  id: number
  action: string
  performer: { id: number; pseudo: string } | null
  targetUser: { id: number; pseudo: string } | null
  entityType: string | null
  entityId: number | null
  details: Record<string, unknown> | null
  ipAddress: string | null
  userAgent: string | null
  createdAt: string
}

export interface AuditLogFilterParams {
  userId?: number
  targetUserId?: number
  action?: string
  entityType?: string
  dateFrom?: string
  dateTo?: string
  severity?: 'info' | 'warning' | 'high'
  page?: number
  limit?: number
}

export interface AuditActionInfo {
  value: string
  label: string
  severity: string
}

/**
 * Service API pour l'administration
 */
export const adminApi = {
  /**
   * Dashboard
   */
  dashboard: {
    async getStats(): Promise<DashboardStats> {
      return apiClient.get<DashboardStats>('/admin/dashboard/stats').then((res) => res.data)
    },

    async getRecentActivity(): Promise<AuditLogEntry[]> {
      return apiClient.get<AuditLogEntry[]>('/admin/dashboard/recent-activity').then((res) => res.data)
    },
  },

  /**
   * Gestion des utilisateurs
   */
  users: {
    async list(params?: UserFilterParams): Promise<PaginatedResponse<AdminUser>> {
      return apiClient.get<PaginatedResponse<AdminUser>>('/admin/users', { params }).then((res) => res.data)
    },

    async get(id: number): Promise<AdminUser> {
      return apiClient.get<AdminUser>(`/admin/users/${id}`).then((res) => res.data)
    },

    async update(id: number, data: UserUpdateData): Promise<AdminUser> {
      return apiClient.put<AdminUser>(`/admin/users/${id}`, data).then((res) => res.data)
    },

    async delete(id: number): Promise<{ message: string }> {
      return apiClient.delete<{ message: string }>(`/admin/users/${id}`).then((res) => res.data)
    },

    async restore(id: number): Promise<{ message: string }> {
      return apiClient.post<{ message: string }>(`/admin/users/${id}/restore`).then((res) => res.data)
    },

    async lock(id: number, minutes: number): Promise<{ message: string }> {
      return apiClient.post<{ message: string }>(`/admin/users/${id}/lock`, { minutes }).then((res) => res.data)
    },

    async unlock(id: number): Promise<{ message: string }> {
      return apiClient.post<{ message: string }>(`/admin/users/${id}/unlock`).then((res) => res.data)
    },

    async getStatistics(): Promise<UserStats> {
      return apiClient.get<UserStats>('/admin/users/statistics').then((res) => res.data)
    },
  },

  /**
   * Logs d'audit
   */
  auditLogs: {
    async list(params?: AuditLogFilterParams): Promise<PaginatedResponse<AuditLogEntry>> {
      return apiClient.get<PaginatedResponse<AuditLogEntry>>('/admin/audit-logs', { params }).then((res) => res.data)
    },

    async get(id: number): Promise<AuditLogEntry> {
      return apiClient.get<AuditLogEntry>(`/admin/audit-logs/${id}`).then((res) => res.data)
    },

    async getByUser(userId: number, limit?: number): Promise<AuditLogEntry[]> {
      return apiClient
        .get<AuditLogEntry[]>(`/admin/audit-logs/user/${userId}`, { params: { limit } })
        .then((res) => res.data)
    },

    async getStatistics(): Promise<AuditStats> {
      return apiClient.get<AuditStats>('/admin/audit-logs/statistics').then((res) => res.data)
    },

    async getAvailableActions(): Promise<AuditActionInfo[]> {
      return apiClient.get<AuditActionInfo[]>('/admin/audit-logs/actions').then((res) => res.data)
    },
  },
}
