import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mapApi } from '@/services/api/mapApi'
import * as apiClient from '@/services/api/apiClient'

vi.mock('@/services/api/apiClient')

describe('mapApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('listByGame', () => {
    it('should list maps by game', async () => {
      const mockMaps = [{ id: 1, name: 'Map 1' }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMaps)

      const result = await mapApi.listByGame(1)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/maps')
      expect(result).toEqual(mockMaps)
    })
  })

  describe('getActive', () => {
    it('should get active map', async () => {
      const mockMap = { id: 1, name: 'Active Map', isActive: true }
      vi.mocked(apiClient.get).mockResolvedValue(mockMap)

      const result = await mapApi.getActive(1)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/maps/active')
      expect(result).toEqual(mockMap)
    })

    it('should return null on 404', async () => {
      const error = { statusCode: 404 }
      vi.mocked(apiClient.get).mockRejectedValue(error)

      const result = await mapApi.getActive(1)

      expect(result).toBeNull()
    })
  })

  describe('getById', () => {
    it('should get map by id', async () => {
      const mockMap = { id: 5, name: 'Test Map' }
      vi.mocked(apiClient.get).mockResolvedValue(mockMap)

      const result = await mapApi.getById(5)

      expect(apiClient.get).toHaveBeenCalledWith('/maps/5')
      expect(result).toEqual(mockMap)
    })
  })

  describe('create', () => {
    it('should create map', async () => {
      const dto = { name: 'New Map', gridSize: 32 }
      const mockMap = { id: 1, ...dto }
      vi.mocked(apiClient.post).mockResolvedValue(mockMap)

      const result = await mapApi.create(1, dto)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps', dto)
      expect(result).toEqual(mockMap)
    })
  })

  describe('update', () => {
    it('should update map', async () => {
      const dto = { name: 'Updated Map' }
      const mockMap = { id: 5, ...dto }
      vi.mocked(apiClient.put).mockResolvedValue(mockMap)

      const result = await mapApi.update(5, dto)

      expect(apiClient.put).toHaveBeenCalledWith('/maps/5', dto)
      expect(result).toEqual(mockMap)
    })
  })

  describe('partialUpdate', () => {
    it('should partially update map', async () => {
      const dto = { gridSize: 64 }
      const mockMap = { id: 5, gridSize: 64 }
      vi.mocked(apiClient.patch).mockResolvedValue(mockMap)

      const result = await mapApi.partialUpdate(5, dto)

      expect(apiClient.patch).toHaveBeenCalledWith('/maps/5', dto)
      expect(result).toEqual(mockMap)
    })
  })

  describe('delete', () => {
    it('should delete map', async () => {
      vi.mocked(apiClient.delete).mockResolvedValue(undefined)

      await mapApi.delete(1, 5)

      expect(apiClient.delete).toHaveBeenCalledWith('/games/1/maps/5')
    })
  })

  describe('activate', () => {
    it('should activate map', async () => {
      const mockMap = { id: 5, isActive: true }
      vi.mocked(apiClient.post).mockResolvedValue(mockMap)

      const result = await mapApi.activate(1, 5)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/activate')
      expect(result).toEqual(mockMap)
    })
  })

  describe('deactivate', () => {
    it('should deactivate map', async () => {
      const mockMap = { id: 5, isActive: false }
      vi.mocked(apiClient.post).mockResolvedValue(mockMap)

      const result = await mapApi.deactivate(1, 5)

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/maps/5/deactivate')
      expect(result).toEqual(mockMap)
    })
  })

  describe('duplicate', () => {
    it('should duplicate map', async () => {
      const mockMap = { id: 6, name: 'Copy of Map' }
      vi.mocked(apiClient.post).mockResolvedValue(mockMap)

      const result = await mapApi.duplicate(5, 'Copy of Map')

      expect(apiClient.post).toHaveBeenCalledWith('/maps/5/duplicate', { name: 'Copy of Map' })
      expect(result).toEqual(mockMap)
    })
  })

  describe('updateSettings', () => {
    it('should update map settings', async () => {
      const settings = { showGrid: true, gridColor: '#000000', gridOpacity: 0.5 }
      const mockMap = { id: 5, settings }
      vi.mocked(apiClient.patch).mockResolvedValue(mockMap)

      const result = await mapApi.updateSettings(1, 5, settings)

      expect(apiClient.patch).toHaveBeenCalledWith('/games/1/maps/5/settings', settings)
      expect(result).toEqual(mockMap)
    })
  })
})
