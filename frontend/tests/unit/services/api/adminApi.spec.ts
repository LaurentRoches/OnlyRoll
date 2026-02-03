import { describe, it, expect, beforeEach, vi } from 'vitest'
import { adminApi } from '@/services/api/adminApi'
import { apiClient } from '@/services/api/apiClient'

vi.mock('@/services/api/apiClient', () => ({
  apiClient: {
    post: vi.fn(),
    get: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('adminApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('dashboard', () => {
    describe('getStats', () => {
      it('should call GET /admin/dashboard/stats', async () => {
        const mockResponse = {
          data: {
            users: { total: 100, active: 90, inactive: 5, deleted: 3, locked: 2 },
            audit: { total: 500, today: 50 },
            games: { total: 25, public: 15 },
          },
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const result = await adminApi.dashboard.getStats()

        expect(apiClient.get).toHaveBeenCalledWith('/admin/dashboard/stats')
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('getRecentActivity', () => {
      it('should call GET /admin/dashboard/recent-activity', async () => {
        const mockResponse = {
          data: [
            { id: 1, action: 'login_success', createdAt: '2026-01-31T10:00:00Z' },
            { id: 2, action: 'user_updated', createdAt: '2026-01-31T09:00:00Z' },
          ],
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const result = await adminApi.dashboard.getRecentActivity()

        expect(apiClient.get).toHaveBeenCalledWith('/admin/dashboard/recent-activity')
        expect(result).toEqual(mockResponse.data)
      })
    })
  })

  describe('users', () => {
    describe('list', () => {
      it('should call GET /admin/users with params', async () => {
        const mockResponse = {
          data: {
            data: [{ id: 1, email: 'user@example.com', pseudo: 'User' }],
            meta: { total: 1, page: 1, limit: 20, totalPages: 1 },
          },
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const params = { search: 'user', page: 1, limit: 20 }
        const result = await adminApi.users.list(params)

        expect(apiClient.get).toHaveBeenCalledWith('/admin/users', { params })
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('get', () => {
      it('should call GET /admin/users/:id', async () => {
        const mockResponse = {
          data: { id: 1, email: 'user@example.com', pseudo: 'User' },
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const result = await adminApi.users.get(1)

        expect(apiClient.get).toHaveBeenCalledWith('/admin/users/1')
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('update', () => {
      it('should call PUT /admin/users/:id with data', async () => {
        const mockResponse = {
          data: { id: 1, email: 'user@example.com', pseudo: 'UpdatedUser' },
        }

        vi.mocked(apiClient.put).mockResolvedValue(mockResponse)

        const data = { pseudo: 'UpdatedUser' }
        const result = await adminApi.users.update(1, data)

        expect(apiClient.put).toHaveBeenCalledWith('/admin/users/1', data)
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('delete', () => {
      it('should call DELETE /admin/users/:id', async () => {
        const mockResponse = {
          data: { message: 'Utilisateur supprimé avec succès' },
        }

        vi.mocked(apiClient.delete).mockResolvedValue(mockResponse)

        const result = await adminApi.users.delete(1)

        expect(apiClient.delete).toHaveBeenCalledWith('/admin/users/1')
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('restore', () => {
      it('should call POST /admin/users/:id/restore', async () => {
        const mockResponse = {
          data: { message: 'Utilisateur restauré avec succès' },
        }

        vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

        const result = await adminApi.users.restore(1)

        expect(apiClient.post).toHaveBeenCalledWith('/admin/users/1/restore')
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('lock', () => {
      it('should call POST /admin/users/:id/lock with minutes', async () => {
        const mockResponse = {
          data: { message: 'Compte verrouillé avec succès' },
        }

        vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

        const result = await adminApi.users.lock(1, 60)

        expect(apiClient.post).toHaveBeenCalledWith('/admin/users/1/lock', { minutes: 60 })
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('unlock', () => {
      it('should call POST /admin/users/:id/unlock', async () => {
        const mockResponse = {
          data: { message: 'Compte déverrouillé avec succès' },
        }

        vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

        const result = await adminApi.users.unlock(1)

        expect(apiClient.post).toHaveBeenCalledWith('/admin/users/1/unlock')
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('getStatistics', () => {
      it('should call GET /admin/users/statistics', async () => {
        const mockResponse = {
          data: { total: 100, active: 90, inactive: 5, deleted: 3, locked: 2 },
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const result = await adminApi.users.getStatistics()

        expect(apiClient.get).toHaveBeenCalledWith('/admin/users/statistics')
        expect(result).toEqual(mockResponse.data)
      })
    })
  })

  describe('auditLogs', () => {
    describe('list', () => {
      it('should call GET /admin/audit-logs with params', async () => {
        const mockResponse = {
          data: {
            data: [{ id: 1, action: 'login_success' }],
            meta: { total: 1, page: 1, limit: 50, totalPages: 1 },
          },
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const params = { action: 'login_success', page: 1 }
        const result = await adminApi.auditLogs.list(params)

        expect(apiClient.get).toHaveBeenCalledWith('/admin/audit-logs', { params })
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('get', () => {
      it('should call GET /admin/audit-logs/:id', async () => {
        const mockResponse = {
          data: { id: 1, action: 'login_success', details: {} },
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const result = await adminApi.auditLogs.get(1)

        expect(apiClient.get).toHaveBeenCalledWith('/admin/audit-logs/1')
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('getByUser', () => {
      it('should call GET /admin/audit-logs/user/:userId', async () => {
        const mockResponse = {
          data: [{ id: 1, action: 'login_success' }],
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const result = await adminApi.auditLogs.getByUser(1, 50)

        expect(apiClient.get).toHaveBeenCalledWith('/admin/audit-logs/user/1', { params: { limit: 50 } })
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('getStatistics', () => {
      it('should call GET /admin/audit-logs/statistics', async () => {
        const mockResponse = {
          data: { total: 500, today: 50, failedLoginsToday: 3 },
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const result = await adminApi.auditLogs.getStatistics()

        expect(apiClient.get).toHaveBeenCalledWith('/admin/audit-logs/statistics')
        expect(result).toEqual(mockResponse.data)
      })
    })

    describe('getAvailableActions', () => {
      it('should call GET /admin/audit-logs/actions', async () => {
        const mockResponse = {
          data: [
            { value: 'login_success', label: 'Connexion réussie', severity: 'info' },
            { value: 'login_failed', label: 'Échec de connexion', severity: 'warning' },
          ],
        }

        vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

        const result = await adminApi.auditLogs.getAvailableActions()

        expect(apiClient.get).toHaveBeenCalledWith('/admin/audit-logs/actions')
        expect(result).toEqual(mockResponse.data)
      })
    })
  })
})
