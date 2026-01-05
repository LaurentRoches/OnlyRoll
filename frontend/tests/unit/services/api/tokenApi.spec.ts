import { describe, it, expect, beforeEach, vi } from 'vitest'
import { tokenApi } from '@/services/api/tokenApi'
import * as apiClient from '@/services/api/apiClient'

vi.mock('@/services/api/apiClient')

describe('tokenApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('listByMapWithGame', () => {
    it('should list tokens by map', async () => {
      const mockTokens = [{ id: 1, name: 'Token 1' }]
      vi.mocked(apiClient.get).mockResolvedValue(mockTokens)

      const result = await tokenApi.listByMapWithGame(1, 5)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/maps/5/tokens')
      expect(result).toEqual(mockTokens)
    })
  })

  describe('listVisible', () => {
    it('should list visible tokens', async () => {
      const mockTokens = [{ id: 1, isVisible: true }]
      vi.mocked(apiClient.get).mockResolvedValue(mockTokens)

      const result = await tokenApi.listVisible(1, 5)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/maps/5/tokens')
      expect(result).toEqual(mockTokens)
    })
  })

  describe('getById', () => {
    it('should get token by id', async () => {
      const mockToken = { id: 10, name: 'Hero' }
      vi.mocked(apiClient.get).mockResolvedValue(mockToken)

      const result = await tokenApi.getById(1, 5, 10)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/maps/5/tokens/10')
      expect(result).toEqual(mockToken)
    })
  })

  describe('create', () => {
    it('should create token', async () => {
      const dto = { name: 'NewToken', type: 'player', x: 5, y: 10 }
      const mockToken = { id: 1, ...dto }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.create(1, 5, dto)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens', dto)
      expect(result).toEqual(mockToken)
    })
  })

  describe('update', () => {
    it('should update token', async () => {
      const dto = { name: 'Updated' }
      const mockToken = { id: 10, ...dto }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.update(1, 5, 10, dto)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10', dto)
      expect(result).toEqual(mockToken)
    })
  })

  describe('partialUpdate', () => {
    it('should partially update token', async () => {
      const dto = { x: 15 }
      const mockToken = { id: 10, x: 15 }
      vi.mocked(apiClient.patch).mockResolvedValue(mockToken)

      const result = await tokenApi.partialUpdate(1, 5, 10, dto)

      expect(apiClient.patch).toHaveBeenCalledWith('/games/1/maps/5/tokens/10', dto)
      expect(result).toEqual(mockToken)
    })
  })

  describe('delete', () => {
    it('should delete token', async () => {
      vi.mocked(apiClient.delete).mockResolvedValue(undefined)

      await tokenApi.delete(1, 5, 10)

      expect(apiClient.delete).toHaveBeenCalledWith('/games/1/maps/5/tokens/10')
    })
  })

  describe('listByMap', () => {
    it('should throw error for deprecated function', async () => {
      await expect(tokenApi.listByMap()).rejects.toThrow('Use listByMapWithGame instead - needs gameId')
    })
  })

  describe('move', () => {
    it('should move token', async () => {
      const position = { x: 10, y: 20 }
      const mockToken = { id: 10, x: 10, y: 20 }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.move(1, 5, 10, position)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/move', position)
      expect(result).toEqual(mockToken)
    })
  })

  describe('rotate', () => {
    it('should rotate token', async () => {
      const mockToken = { id: 10, rotation: 90 }
      vi.mocked(apiClient.patch).mockResolvedValue(mockToken)

      const result = await tokenApi.rotate(1, 5, 10, 90)

      expect(apiClient.patch).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/rotate', { degrees: 90 })
      expect(result).toEqual(mockToken)
    })
  })

  describe('show', () => {
    it('should show token', async () => {
      const mockToken = { id: 10, isVisible: true }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.show(1, 5, 10)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/toggle-visibility')
      expect(result).toEqual(mockToken)
    })
  })

  describe('hide', () => {
    it('should hide token', async () => {
      const mockToken = { id: 10, isVisible: false }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.hide(1, 5, 10)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/toggle-visibility')
      expect(result).toEqual(mockToken)
    })
  })

  describe('lock', () => {
    it('should lock token', async () => {
      const mockToken = { id: 10, isLocked: true }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.lock(1, 5, 10)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/toggle-lock')
      expect(result).toEqual(mockToken)
    })
  })

  describe('unlock', () => {
    it('should unlock token', async () => {
      const mockToken = { id: 10, isLocked: false }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.unlock(1, 5, 10)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/toggle-lock')
      expect(result).toEqual(mockToken)
    })
  })

  describe('duplicate', () => {
    it('should duplicate token without offset', async () => {
      const mockToken = { id: 11, name: 'Copy' }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.duplicate(1, 5, 10)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/duplicate', { offset: undefined })
      expect(result).toEqual(mockToken)
    })

    it('should duplicate token with offset', async () => {
      const mockToken = { id: 11, name: 'Copy', x: 15, y: 25 }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.duplicate(1, 5, 10, { x: 5, y: 5 })

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/duplicate', { offset: { x: 5, y: 5 } })
      expect(result).toEqual(mockToken)
    })
  })

  describe('moveBulk', () => {
    it('should move multiple tokens', async () => {
      const movements = [
        { tokenId: 1, x: 10, y: 20 },
        { tokenId: 2, x: 30, y: 40 }
      ]
      const mockTokens = [{ id: 1, x: 10, y: 20 }, { id: 2, x: 30, y: 40 }]
      vi.mocked(apiClient.patch).mockResolvedValue(mockTokens)

      const result = await tokenApi.moveBulk(1, 5, movements)

      expect(apiClient.patch).toHaveBeenCalledWith('/games/1/maps/5/tokens/move-bulk', { movements })
      expect(result).toEqual(mockTokens)
    })
  })

  describe('toggleBulkVisibility', () => {
    it('should toggle visibility for multiple tokens', async () => {
      const mockTokens = [{ id: 1, isVisible: true }, { id: 2, isVisible: true }]
      vi.mocked(apiClient.patch).mockResolvedValue(mockTokens)

      const result = await tokenApi.toggleBulkVisibility(1, 5, [1, 2], true)

      expect(apiClient.patch).toHaveBeenCalledWith('/games/1/maps/5/tokens/visibility-bulk', {
        tokenIds: [1, 2],
        isVisible: true
      })
      expect(result).toEqual(mockTokens)
    })
  })

  describe('managePermissions', () => {
    it('should add permissions', async () => {
      const mockToken = { id: 10, controlledBy: [5] }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.managePermissions(1, 5, 10, 'add', 5)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/permissions', {
        action: 'add',
        userId: 5
      })
      expect(result).toEqual(mockToken)
    })

    it('should remove permissions', async () => {
      const mockToken = { id: 10, controlledBy: [] }
      vi.mocked(apiClient.post).mockResolvedValue(mockToken)

      const result = await tokenApi.managePermissions(1, 5, 10, 'remove', 5)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/tokens/10/permissions', {
        action: 'remove',
        userId: 5
      })
      expect(result).toEqual(mockToken)
    })
  })
})
