import { describe, it, expect, beforeEach, vi } from 'vitest'
import { authApi } from '@/services/api/authApi'
import { apiClient } from '@/services/api/apiClient'
import type { LoginCredentials, RegisterCredentials } from '@/types/auth'

vi.mock('@/services/api/apiClient', () => ({
  apiClient: {
    post: vi.fn(),
    get: vi.fn()
  }
}))

describe('authApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('login', () => {
    it('should call POST /login with credentials', async () => {
      const credentials: LoginCredentials = {
        email: 'test@example.com',
        password: 'password123'
      }

      const mockResponse = {
        data: {
          success: true,
          message: 'Login successful',
          user: {
            id: 1,
            email: 'test@example.com',
            pseudo: 'TestUser'
          }
        }
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await authApi.login(credentials)

      expect(apiClient.post).toHaveBeenCalledWith('/login', credentials)
      expect(result).toEqual(mockResponse.data)
    })

    it('should handle login errors', async () => {
      const credentials: LoginCredentials = {
        email: 'test@example.com',
        password: 'wrongpassword'
      }

      const mockError = new Error('Invalid credentials')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(authApi.login(credentials)).rejects.toThrow('Invalid credentials')
      expect(apiClient.post).toHaveBeenCalledWith('/login', credentials)
    })
  })

  describe('register', () => {
    it('should call POST /register with credentials', async () => {
      const credentials: RegisterCredentials = {
        email: 'newuser@example.com',
        password: 'password123',
        confirmPassword: 'password123',
        pseudo: 'NewUser'
      }

      const mockResponse = {
        data: {
          message: 'Registration successful',
          user: {
            id: 2,
            email: 'newuser@example.com',
            pseudo: 'NewUser'
          }
        }
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await authApi.register(credentials)

      expect(apiClient.post).toHaveBeenCalledWith('/register', credentials)
      expect(result).toEqual(mockResponse.data)
    })

    it('should handle registration errors', async () => {
      const credentials: RegisterCredentials = {
        email: 'existing@example.com',
        password: 'password123',
        confirmPassword: 'password123',
        pseudo: 'ExistingUser'
      }

      const mockError = new Error('Email already exists')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(authApi.register(credentials)).rejects.toThrow('Email already exists')
      expect(apiClient.post).toHaveBeenCalledWith('/register', credentials)
    })
  })

  describe('me', () => {
    it('should call GET /me to fetch current user', async () => {
      const mockResponse = {
        data: {
          id: 1,
          email: 'test@example.com',
          pseudo: 'TestUser'
        }
      }

      vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

      const result = await authApi.me()

      expect(apiClient.get).toHaveBeenCalledWith('/me')
      expect(result).toEqual(mockResponse.data)
    })

    it('should handle unauthorized error', async () => {
      const mockError = new Error('Unauthorized')
      vi.mocked(apiClient.get).mockRejectedValue(mockError)

      await expect(authApi.me()).rejects.toThrow('Unauthorized')
      expect(apiClient.get).toHaveBeenCalledWith('/me')
    })
  })

  describe('logout', () => {
    it('should call POST /logout', async () => {
      const mockResponse = {
        data: {
          message: 'Logout successful'
        }
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await authApi.logout()

      expect(apiClient.post).toHaveBeenCalledWith('/logout')
      expect(result).toEqual(mockResponse.data)
    })

    it('should handle logout errors', async () => {
      const mockError = new Error('Logout failed')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(authApi.logout()).rejects.toThrow('Logout failed')
      expect(apiClient.post).toHaveBeenCalledWith('/logout')
    })
  })
})
