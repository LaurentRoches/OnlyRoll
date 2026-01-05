import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import type { AxiosInstance } from 'axios'

// Import apiClient directly to test it
let apiClient: AxiosInstance
let get: <T>(url: string) => Promise<T>
let post: <T>(url: string, data?: unknown) => Promise<T>
let put: <T>(url: string, data?: unknown) => Promise<T>
let patch: <T>(url: string, data?: unknown) => Promise<T>
let deleteFunc: <T = void>(url: string) => Promise<T>

describe('apiClient', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    vi.clearAllMocks()

    // Dynamic import to get fresh instance
    const module = await import('@/services/api/apiClient')
    apiClient = module.apiClient
    get = module.get
    post = module.post
    put = module.put
    patch = module.patch
    deleteFunc = module.delete
  })

  describe('Configuration', () => {
    it('should be created with baseURL ending in /api', () => {
      expect(apiClient.defaults.baseURL).toContain('/api')
      expect(apiClient.defaults.baseURL).toBeTruthy()
    })

    it('should have correct default headers', () => {
      expect(apiClient.defaults.headers['Content-Type']).toBe('application/json')
    })

    it('should have withCredentials enabled', () => {
      expect(apiClient.defaults.withCredentials).toBe(true)
    })

    it('should have interceptors configured', () => {
      expect(apiClient.interceptors.response).toBeDefined()
      expect(apiClient.interceptors.request).toBeDefined()
    })
  })

  describe('GET helper', () => {
    it('should make GET request and return data', async () => {
      const mockData = { id: 1, name: 'Test' }
      vi.spyOn(apiClient, 'get').mockResolvedValue({ data: mockData })

      const result = await get('/test')

      expect(apiClient.get).toHaveBeenCalledWith('/test')
      expect(result).toEqual(mockData)
    })

    it('should handle GET request with query parameters', async () => {
      const mockData = [{ id: 1 }, { id: 2 }]
      vi.spyOn(apiClient, 'get').mockResolvedValue({ data: mockData })

      const result = await get('/users?page=1')

      expect(apiClient.get).toHaveBeenCalledWith('/users?page=1')
      expect(result).toEqual(mockData)
    })
  })

  describe('POST helper', () => {
    it('should make POST request with data', async () => {
      const mockResponse = { id: 1, created: true }
      const postData = { name: 'New Item' }
      vi.spyOn(apiClient, 'post').mockResolvedValue({ data: mockResponse })

      const result = await post('/items', postData)

      expect(apiClient.post).toHaveBeenCalledWith('/items', postData)
      expect(result).toEqual(mockResponse)
    })

    it('should make POST request without data', async () => {
      const mockResponse = { success: true }
      vi.spyOn(apiClient, 'post').mockResolvedValue({ data: mockResponse })

      const result = await post('/action')

      expect(apiClient.post).toHaveBeenCalledWith('/action', undefined)
      expect(result).toEqual(mockResponse)
    })
  })

  describe('PUT helper', () => {
    it('should make PUT request with data', async () => {
      const mockResponse = { id: 1, updated: true }
      const putData = { name: 'Updated Item' }
      vi.spyOn(apiClient, 'put').mockResolvedValue({ data: mockResponse })

      const result = await put('/items/1', putData)

      expect(apiClient.put).toHaveBeenCalledWith('/items/1', putData)
      expect(result).toEqual(mockResponse)
    })

    it('should make PUT request without data', async () => {
      const mockResponse = { success: true }
      vi.spyOn(apiClient, 'put').mockResolvedValue({ data: mockResponse })

      const result = await put('/items/1')

      expect(apiClient.put).toHaveBeenCalledWith('/items/1', undefined)
      expect(result).toEqual(mockResponse)
    })
  })

  describe('PATCH helper', () => {
    it('should make PATCH request with data', async () => {
      const mockResponse = { id: 1, patched: true }
      const patchData = { status: 'active' }
      vi.spyOn(apiClient, 'patch').mockResolvedValue({ data: mockResponse })

      const result = await patch('/items/1', patchData)

      expect(apiClient.patch).toHaveBeenCalledWith('/items/1', patchData)
      expect(result).toEqual(mockResponse)
    })

    it('should make PATCH request without data', async () => {
      const mockResponse = { success: true }
      vi.spyOn(apiClient, 'patch').mockResolvedValue({ data: mockResponse })

      const result = await patch('/items/1')

      expect(apiClient.patch).toHaveBeenCalledWith('/items/1', undefined)
      expect(result).toEqual(mockResponse)
    })
  })

  describe('DELETE helper', () => {
    it('should make DELETE request', async () => {
      const mockResponse = { deleted: true }
      vi.spyOn(apiClient, 'delete').mockResolvedValue({ data: mockResponse })

      const result = await deleteFunc('/items/1')

      expect(apiClient.delete).toHaveBeenCalledWith('/items/1')
      expect(result).toEqual(mockResponse)
    })

    it('should handle DELETE request with void response', async () => {
      vi.spyOn(apiClient, 'delete').mockResolvedValue({ data: undefined })

      const result = await deleteFunc('/items/1')

      expect(apiClient.delete).toHaveBeenCalledWith('/items/1')
      expect(result).toBeUndefined()
    })
  })

  describe('Response Interceptor', () => {
    it('should return response for successful requests', async () => {
      const mockData = { success: true, data: 'test' }
      vi.spyOn(apiClient, 'get').mockResolvedValue({ data: mockData })

      const result = await get('/success')

      expect(result).toEqual(mockData)
    })
  })
})
