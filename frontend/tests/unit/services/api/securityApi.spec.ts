import { describe, it, expect, beforeEach, vi } from 'vitest'
import {
  securityApi,
  type GeneratePasswordOptions,
  type GeneratePasswordResponse,
  type GenerateMultiplePasswordsResponse,
  type PasswordStrengthResult,
  type PasswordOptionsResponse,
} from '@/services/api/securityApi'
import * as apiClient from '@/services/api/apiClient'

vi.mock('@/services/api/apiClient', () => ({
  get: vi.fn(),
  post: vi.fn(),
}))

describe('securityApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('generatePassword', () => {
    it('should call POST /security/generate-password without options', async () => {
      const mockResponse: GeneratePasswordResponse = {
        password: 'SecureP@ss123!',
        options: {
          length: 16,
          includeLowercase: true,
          includeUppercase: true,
          includeDigits: true,
          includeSpecial: true,
          excludeAmbiguous: false,
        },
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await securityApi.generatePassword()

      expect(apiClient.post).toHaveBeenCalledWith('/security/generate-password', {})
      expect(result).toEqual(mockResponse)
    })

    it('should call POST /security/generate-password with custom options', async () => {
      const options: GeneratePasswordOptions = {
        length: 20,
        includeLowercase: true,
        includeUppercase: true,
        includeDigits: false,
        includeSpecial: true,
        excludeAmbiguous: true,
      }

      const mockResponse: GeneratePasswordResponse = {
        password: 'SuperSecure@Password!',
        options: options,
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await securityApi.generatePassword(options)

      expect(apiClient.post).toHaveBeenCalledWith('/security/generate-password', options)
      expect(result).toEqual(mockResponse)
    })

    it('should return multiple passwords when count > 1', async () => {
      const options: GeneratePasswordOptions = {
        length: 12,
        count: 3,
      }

      const mockResponse: GenerateMultiplePasswordsResponse = {
        passwords: ['Pass1@word!', 'Secure2#Pass', 'Strong3$Key'],
        options: options,
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await securityApi.generatePassword(options)

      expect(apiClient.post).toHaveBeenCalledWith('/security/generate-password', options)
      expect(result).toEqual(mockResponse)
      expect('passwords' in result).toBe(true)
      if ('passwords' in result) {
        expect(result.passwords).toHaveLength(3)
      }
    })

    it('should handle error when generation fails', async () => {
      const mockError = new Error('Generation failed')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(securityApi.generatePassword()).rejects.toThrow('Generation failed')
    })

    it('should handle server error', async () => {
      const mockError = new Error('Internal Server Error')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(securityApi.generatePassword({ length: 8 })).rejects.toThrow(
        'Internal Server Error'
      )
    })
  })

  describe('evaluatePassword', () => {
    it('should call POST /security/evaluate-password with password', async () => {
      const mockResponse: PasswordStrengthResult = {
        score: 5,
        strength: 'fort',
        feedback: [],
        isValid: true,
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await securityApi.evaluatePassword('SecureP@ss123!')

      expect(apiClient.post).toHaveBeenCalledWith('/security/evaluate-password', {
        password: 'SecureP@ss123!',
      })
      expect(result).toEqual(mockResponse)
    })

    it('should return weak strength for simple password', async () => {
      const mockResponse: PasswordStrengthResult = {
        score: 1,
        strength: 'très faible',
        feedback: ['Au moins 12 caractères requis', 'Au moins une majuscule requise'],
        isValid: false,
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await securityApi.evaluatePassword('password')

      expect(result.strength).toBe('très faible')
      expect(result.isValid).toBe(false)
      expect(result.feedback.length).toBeGreaterThan(0)
    })

    it('should return excellent strength for complex password', async () => {
      const mockResponse: PasswordStrengthResult = {
        score: 6,
        strength: 'excellent',
        feedback: [],
        isValid: true,
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await securityApi.evaluatePassword('SuperSecure@Password123!')

      expect(result.strength).toBe('excellent')
      expect(result.isValid).toBe(true)
      expect(result.feedback).toHaveLength(0)
    })

    it('should handle evaluation error', async () => {
      const mockError = new Error('Evaluation failed')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(securityApi.evaluatePassword('test')).rejects.toThrow('Evaluation failed')
    })

    it('should return medium strength with feedback', async () => {
      const mockResponse: PasswordStrengthResult = {
        score: 3,
        strength: 'moyen',
        feedback: ['Ajoutez des caractères spéciaux'],
        isValid: true,
      }

      vi.mocked(apiClient.post).mockResolvedValue(mockResponse)

      const result = await securityApi.evaluatePassword('Password123')

      expect(result.strength).toBe('moyen')
      expect(result.feedback).toContain('Ajoutez des caractères spéciaux')
    })
  })

  describe('getPasswordOptions', () => {
    it('should call GET /security/password-options', async () => {
      const mockResponse: PasswordOptionsResponse = {
        defaults: {
          length: 16,
          minLength: 8,
          maxLength: 128,
          includeLowercase: true,
          includeUppercase: true,
          includeDigits: true,
          includeSpecial: true,
          excludeAmbiguous: false,
        },
        specialCharacters: '!@#$%&*()_+-=[]{}|;:,.<>?',
        ambiguousCharacters: ['0', 'O', 'l', '1', 'I'],
      }

      vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

      const result = await securityApi.getPasswordOptions()

      expect(apiClient.get).toHaveBeenCalledWith('/security/password-options')
      expect(result).toEqual(mockResponse)
    })

    it('should return default password options', async () => {
      const mockResponse: PasswordOptionsResponse = {
        defaults: {
          length: 16,
          minLength: 8,
          maxLength: 128,
          includeLowercase: true,
          includeUppercase: true,
          includeDigits: true,
          includeSpecial: true,
          excludeAmbiguous: false,
        },
        specialCharacters: '!@#$%&*()_+-=[]{}|;:,.<>?',
        ambiguousCharacters: ['0', 'O', 'l', '1', 'I'],
      }

      vi.mocked(apiClient.get).mockResolvedValue(mockResponse)

      const result = await securityApi.getPasswordOptions()

      expect(result.defaults.length).toBe(16)
      expect(result.defaults.minLength).toBe(8)
      expect(result.defaults.maxLength).toBe(128)
      expect(result.specialCharacters).toBe('!@#$%&*()_+-=[]{}|;:,.<>?')
    })

    it('should handle error when fetching options', async () => {
      const mockError = new Error('Network error')
      vi.mocked(apiClient.get).mockRejectedValue(mockError)

      await expect(securityApi.getPasswordOptions()).rejects.toThrow('Network error')
    })

    it('should handle unauthorized error', async () => {
      const mockError = new Error('Unauthorized')
      vi.mocked(apiClient.get).mockRejectedValue(mockError)

      await expect(securityApi.getPasswordOptions()).rejects.toThrow('Unauthorized')
    })
  })
})
