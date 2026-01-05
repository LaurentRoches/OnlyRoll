import { describe, it, expect, beforeEach, vi } from 'vitest'
import { gameApi } from '@/services/api/gameApi'
import * as apiClient from '@/services/api/apiClient'
import type { CreateGameDTO, UpdateGameDTO, GameFilters } from '@/types/game'
import { GameStatus } from '@/types/game'

vi.mock('@/services/api/apiClient')

describe('gameApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('listPublic', () => {
    it('should call GET /games without filters', async () => {
      const mockResponse = {
        games: [],
        total: 0,
        page: 1,
        limit: 10
      }

      vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

      const result = await gameApi.listPublic()

      expect(apiClient.get).toHaveBeenCalledWith('/games')
      expect(result).toEqual(mockResponse)
    })

    it('should call GET /games with search filter', async () => {
      const filters: GameFilters = {
        search: 'Dragon'
      }

      const mockResponse = {
        games: [],
        total: 0,
        page: 1,
        limit: 10
      }

      vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

      await gameApi.listPublic(filters)

      expect(apiClient.get).toHaveBeenCalledWith('/games?search=Dragon')
    })

    it('should call GET /games with all filters', async () => {
      const filters: GameFilters = {
        search: 'Adventure',
        title: 'Epic Quest',
        gameMaster: 'GM1',
        status: GameStatus.IN_PROGRESS,
        page: 2,
        limit: 20
      }

      const mockResponse = {
        games: [],
        total: 0,
        page: 2,
        limit: 20
      }

      vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

      await gameApi.listPublic(filters)

      expect(apiClient.get).toHaveBeenCalledWith(
        '/games?search=Adventure&title=Epic+Quest&gameMaster=GM1&status=in_progress&page=2&limit=20'
      )
    })

    it('should handle errors when listing games', async () => {
      const mockError = new Error('Network error')
      vi.mocked(apiClient.get).mockRejectedValue(mockError)

      await expect(gameApi.listPublic()).rejects.toThrow('Network error')
    })
  })

  describe('myGames', () => {
    it('should call GET /games/my-games', async () => {
      const mockGames = [
        { id: 1, title: 'My Game' }
      ]

      vi.mocked(apiClient.get).mockResolvedValue(mockGames)

      const result = await gameApi.myGames()

      expect(apiClient.get).toHaveBeenCalledWith('/games/my-games')
      expect(result).toEqual(mockGames)
    })
  })

  describe('getById', () => {
    it('should call GET /games/:id', async () => {
      const mockGame = { id: 1, title: 'Test Game' }

      vi.mocked(apiClient.get).mockResolvedValue(mockGame)

      const result = await gameApi.getById(1)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1')
      expect(result).toEqual(mockGame)
    })

    it('should handle not found error', async () => {
      const mockError = new Error('Game not found')
      vi.mocked(apiClient.get).mockRejectedValue(mockError)

      await expect(gameApi.getById(999)).rejects.toThrow('Game not found')
    })
  })

  describe('create', () => {
    it('should call POST /games with DTO', async () => {
      const dto: CreateGameDTO = {
        name: 'New Game',
        description: 'A new adventure'
      }

      const mockGame = { id: 1, ...dto }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.create(dto)

      expect(apiClient.post).toHaveBeenCalledWith('/games', dto)
      expect(result).toEqual(mockGame)
    })
  })

  describe('update', () => {
    it('should call PUT /games/:id with DTO', async () => {
      const dto: UpdateGameDTO = {
        name: 'Updated Game'
      }

      const mockGame = { id: 1, ...dto }

      vi.mocked(apiClient.put).mockResolvedValue(mockGame)

      const result = await gameApi.update(1, dto)

      expect(apiClient.put).toHaveBeenCalledWith('/games/1', dto)
      expect(result).toEqual(mockGame)
    })
  })

  describe('delete', () => {
    it('should call DELETE /games/:id', async () => {
      vi.mocked(apiClient.delete).mockResolvedValue(undefined)

      await gameApi.delete(1)

      expect(apiClient.delete).toHaveBeenCalledWith('/games/1')
    })
  })

  describe('joinByCode', () => {
    it('should call POST /games/join with invite code', async () => {
      const inviteCode = 'ABC123'
      const mockGame = { id: 1, title: 'Joined Game' }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.joinByCode(inviteCode)

      expect(apiClient.post).toHaveBeenCalledWith('/games/join', {
        inviteCode,
        password: undefined
      })
      expect(result).toEqual(mockGame)
    })

    it('should call POST /games/join with invite code and password', async () => {
      const inviteCode = 'ABC123'
      const password = 'secret'
      const mockGame = { id: 1, title: 'Joined Game' }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.joinByCode(inviteCode, password)

      expect(apiClient.post).toHaveBeenCalledWith('/games/join', {
        inviteCode,
        password
      })
      expect(result).toEqual(mockGame)
    })
  })

  describe('join', () => {
    it('should call POST /games/:id/join', async () => {
      const mockGame = { id: 1, title: 'Joined Game' }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.join(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/join', { password: undefined })
      expect(result).toEqual(mockGame)
    })

    it('should call POST /games/:id/join with password', async () => {
      const mockGame = { id: 1, title: 'Joined Game' }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.join(1, 'secret')

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/join', { password: 'secret' })
      expect(result).toEqual(mockGame)
    })
  })

  describe('leave', () => {
    it('should call POST /games/:id/leave', async () => {
      vi.mocked(apiClient.post).mockResolvedValue(undefined)

      await gameApi.leave(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/leave')
    })
  })

  describe('start', () => {
    it('should call POST /games/:id/start', async () => {
      const mockGame = { id: 1, status: 'active' }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.start(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/start')
      expect(result).toEqual(mockGame)
    })
  })

  describe('pause', () => {
    it('should call POST /games/:id/pause', async () => {
      const mockGame = { id: 1, status: 'paused' }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.pause(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/pause')
      expect(result).toEqual(mockGame)
    })
  })

  describe('resume', () => {
    it('should call POST /games/:id/resume', async () => {
      const mockGame = { id: 1, status: 'active' }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.resume(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/resume')
      expect(result).toEqual(mockGame)
    })
  })

  describe('complete', () => {
    it('should call POST /games/:id/complete', async () => {
      const mockGame = { id: 1, status: 'completed' }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.complete(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/complete')
      expect(result).toEqual(mockGame)
    })
  })

  describe('archive', () => {
    it('should call POST /games/:id/archive', async () => {
      const mockGame = { id: 1, status: 'archived' }

      vi.mocked(apiClient.post).mockResolvedValue(mockGame)

      const result = await gameApi.archive(1)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/archive')
      expect(result).toEqual(mockGame)
    })
  })

  describe('getMercureToken', () => {
    it('should call GET /games/:id/mercure-token', async () => {
      const mockToken = {
        token: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
        expiresIn: 3600
      }

      vi.mocked(apiClient.get).mockResolvedValue(mockToken)

      const result = await gameApi.getMercureToken(1)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/mercure-token')
      expect(result).toEqual(mockToken)
    })
  })

  describe('getMercurePresenceToken', () => {
    it('should call POST /games/mercure-presence-token with gameIds', async () => {
      const gameIds = [1, 2, 3]
      const mockToken = {
        token: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
        expiresIn: 3600
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await gameApi.getMercurePresenceToken(gameIds)

      expect(apiClient.post).toHaveBeenCalledWith('/games/mercure-presence-token', { gameIds })
      expect(result).toEqual(mockToken)
    })
  })
})
