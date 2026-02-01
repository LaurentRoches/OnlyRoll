import { describe, it, expect, beforeEach, vi } from 'vitest'
import { profileApi } from '@/services/api/profileApi'
import { apiClient } from '@/services/api/apiClient'

vi.mock('@/services/api/apiClient', () => ({
  apiClient: {
    post: vi.fn(),
    get: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('profileApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('get', () => {
    it('should call GET /profile', async () => {
      const mockResponse = {
        data: {
          id: 1,
          email: 'user@example.com',
          pseudo: 'User',
          roles: ['ROLE_USER'],
          isVerified: true,
          timezone: 'UTC',
          language: 'fr',
          avatar: null,
          createdAt: '2026-01-01T00:00:00Z',
          lastLogin: '2026-01-31T10:00:00Z',
        },
      }

      vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

      const result = await profileApi.get()

      expect(apiClient.get).toHaveBeenCalledWith('/profile')
      expect(result).toEqual(mockResponse.data)
    })

    it('should handle unauthorized error', async () => {
      const mockError = new Error('Unauthorized')
      vi.mocked(apiClient.get).mockRejectedValue(mockError)

      await expect(profileApi.get()).rejects.toThrow('Unauthorized')
      expect(apiClient.get).toHaveBeenCalledWith('/profile')
    })
  })

  describe('update', () => {
    it('should call PUT /profile with data', async () => {
      const mockResponse = {
        data: {
          id: 1,
          email: 'user@example.com',
          pseudo: 'UpdatedPseudo',
          timezone: 'Europe/Paris',
          language: 'fr',
        },
      }

      vi.mocked(apiClient.put).mockResolvedValue(mockResponse)

      const data = { pseudo: 'UpdatedPseudo', timezone: 'Europe/Paris' }
      const result = await profileApi.update(data)

      expect(apiClient.put).toHaveBeenCalledWith('/profile', data)
      expect(result).toEqual(mockResponse.data)
    })

    it('should handle validation error', async () => {
      const mockError = new Error('Validation failed')
      vi.mocked(apiClient.put).mockRejectedValue(mockError)

      const data = { pseudo: 'ab' } // Too short
      await expect(profileApi.update(data)).rejects.toThrow('Validation failed')
    })
  })

  describe('changePassword', () => {
    it('should call PUT /profile/password with data', async () => {
      const mockResponse = {
        data: { message: 'Mot de passe modifié avec succès' },
      }

      vi.mocked(apiClient.put).mockResolvedValue(mockResponse)

      const data = {
        currentPassword: 'OldPassword123!',
        newPassword: 'NewPassword123!',
        confirmPassword: 'NewPassword123!',
      }
      const result = await profileApi.changePassword(data)

      expect(apiClient.put).toHaveBeenCalledWith('/profile/password', data)
      expect(result).toEqual(mockResponse.data)
    })

    it('should handle invalid current password error', async () => {
      const mockError = new Error('Mot de passe actuel incorrect')
      vi.mocked(apiClient.put).mockRejectedValue(mockError)

      const data = {
        currentPassword: 'WrongPassword!',
        newPassword: 'NewPassword123!',
        confirmPassword: 'NewPassword123!',
      }
      await expect(profileApi.changePassword(data)).rejects.toThrow('Mot de passe actuel incorrect')
    })

    it('should handle rate limit error', async () => {
      const mockError = new Error('Trop de tentatives')
      vi.mocked(apiClient.put).mockRejectedValue(mockError)

      const data = {
        currentPassword: 'Password123!',
        newPassword: 'NewPassword123!',
        confirmPassword: 'NewPassword123!',
      }
      await expect(profileApi.changePassword(data)).rejects.toThrow('Trop de tentatives')
    })
  })

  describe('uploadAvatar', () => {
    it('should call POST /profile/avatar with form data', async () => {
      const mockResponse = {
        data: { message: 'Avatar mis à jour avec succès', avatar: '/uploads/avatars/test.png' },
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const file = new File(['test'], 'test.png', { type: 'image/png' })
      const result = await profileApi.uploadAvatar(file)

      expect(apiClient.post).toHaveBeenCalledWith(
        '/profile/avatar',
        expect.any(FormData),
        expect.objectContaining({
          headers: { 'Content-Type': 'multipart/form-data' },
        })
      )
      expect(result).toEqual(mockResponse.data)
    })

    it('should handle file too large error', async () => {
      const mockError = new Error('Le fichier est trop volumineux')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      const file = new File(['test'], 'test.png', { type: 'image/png' })
      await expect(profileApi.uploadAvatar(file)).rejects.toThrow('Le fichier est trop volumineux')
    })

    it('should handle invalid file type error', async () => {
      const mockError = new Error('Type de fichier non autorisé')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      const file = new File(['test'], 'test.pdf', { type: 'application/pdf' })
      await expect(profileApi.uploadAvatar(file)).rejects.toThrow('Type de fichier non autorisé')
    })
  })

  describe('deleteAvatar', () => {
    it('should call DELETE /profile/avatar', async () => {
      const mockResponse = {
        data: { message: 'Avatar supprimé avec succès' },
      }

      vi.mocked(apiClient.delete).mockResolvedValue(mockResponse)

      const result = await profileApi.deleteAvatar()

      expect(apiClient.delete).toHaveBeenCalledWith('/profile/avatar')
      expect(result).toEqual(mockResponse.data)
    })

    it('should handle error when no avatar exists', async () => {
      const mockError = new Error('Aucun avatar à supprimer')
      vi.mocked(apiClient.delete).mockRejectedValue(mockError)

      await expect(profileApi.deleteAvatar()).rejects.toThrow('Aucun avatar à supprimer')
    })
  })
})
