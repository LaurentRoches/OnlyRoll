import { describe, it, expect, beforeEach, vi } from 'vitest'
import { presenceApi } from '@/services/api/presenceApi'
import * as apiClient from '@/services/api/apiClient'

vi.mock('@/services/api/apiClient')

describe('presenceApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('join', () => {
    it('should notify join', async () => {
      const mockResponse = { success: true, onlineUsers: [1, 2], onlineCount: 2 }
      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await presenceApi.join(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/presence/join')
      expect(result).toEqual(mockResponse)
    })
  })

  describe('leave', () => {
    it('should notify leave', async () => {
      const mockResponse = { success: true }
      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await presenceApi.leave(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/presence/leave')
      expect(result).toEqual(mockResponse)
    })
  })

  describe('heartbeat', () => {
    it('should send heartbeat', async () => {
      const mockResponse = { success: true, onlineCount: 3 }
      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await presenceApi.heartbeat(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/presence/heartbeat')
      expect(result).toEqual(mockResponse)
    })
  })

  describe('getOnlineUsers', () => {
    it('should get online users', async () => {
      const mockResponse = { success: true, onlineUsers: [1, 2, 3], onlineCount: 3 }
      vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

      const result = await presenceApi.getOnlineUsers(1)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/presence/online')
      expect(result).toEqual(mockResponse)
    })
  })
})
