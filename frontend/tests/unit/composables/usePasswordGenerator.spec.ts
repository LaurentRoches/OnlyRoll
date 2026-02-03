import { describe, it, expect, beforeEach, vi } from 'vitest'
import { usePasswordGenerator } from '@/composables/usePasswordGenerator'
import { securityApi } from '@/services/api/securityApi'

vi.mock('@/services/api/securityApi', () => ({
  securityApi: {
    generatePassword: vi.fn(),
    evaluatePassword: vi.fn(),
    getPasswordOptions: vi.fn(),
  },
}))

describe('usePasswordGenerator', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('should initialize with default values', () => {
      const { isGenerating, isEvaluating, generatedPassword, generatedPasswords, strengthResult, error, options } =
        usePasswordGenerator()

      expect(isGenerating.value).toBe(false)
      expect(isEvaluating.value).toBe(false)
      expect(generatedPassword.value).toBeNull()
      expect(generatedPasswords.value).toEqual([])
      expect(strengthResult.value).toBeNull()
      expect(error.value).toBeNull()
      expect(options.value).toEqual({
        length: 16,
        includeLowercase: true,
        includeUppercase: true,
        includeDigits: true,
        includeSpecial: true,
        excludeAmbiguous: false,
        count: 1,
      })
    })
  })

  describe('generate', () => {
    it('should generate a single password', async () => {
      vi.mocked(securityApi.generatePassword).mockResolvedValue({
        password: 'SecureP@ss123!',
        options: { length: 16 },
      })

      const { generate, generatedPassword, generatedPasswords, isGenerating, error } =
        usePasswordGenerator()

      const result = await generate()

      expect(result).toBe('SecureP@ss123!')
      expect(generatedPassword.value).toBe('SecureP@ss123!')
      expect(generatedPasswords.value).toEqual(['SecureP@ss123!'])
      expect(isGenerating.value).toBe(false)
      expect(error.value).toBeNull()
    })

    it('should generate password with custom options', async () => {
      const customOptions = {
        length: 20,
        includeLowercase: true,
        includeUppercase: false,
        includeDigits: true,
        includeSpecial: false,
        excludeAmbiguous: true,
      }

      vi.mocked(securityApi.generatePassword).mockResolvedValue({
        password: 'custompassword123',
        options: customOptions,
      })

      const { generate, generatedPassword } = usePasswordGenerator()

      await generate(customOptions)

      expect(securityApi.generatePassword).toHaveBeenCalledWith(customOptions)
      expect(generatedPassword.value).toBe('custompassword123')
    })

    it('should handle multiple passwords response', async () => {
      vi.mocked(securityApi.generatePassword).mockResolvedValue({
        passwords: ['Pass1@!', 'Pass2@!', 'Pass3@!'],
        options: { length: 8, count: 3 },
      })

      const { generate, generatedPassword, generatedPasswords } = usePasswordGenerator()

      const result = await generate({ length: 8, count: 3 })

      expect(result).toBe('Pass1@!')
      expect(generatedPassword.value).toBe('Pass1@!')
      expect(generatedPasswords.value).toEqual(['Pass1@!', 'Pass2@!', 'Pass3@!'])
    })

    it('should handle error during generation', async () => {
      vi.mocked(securityApi.generatePassword).mockRejectedValue(new Error('Network error'))

      const { generate, generatedPassword, error } = usePasswordGenerator()

      const result = await generate()

      expect(result).toBeNull()
      expect(generatedPassword.value).toBeNull()
      expect(error.value).toBe('Network error')
    })

    it('should handle non-Error exception', async () => {
      vi.mocked(securityApi.generatePassword).mockRejectedValue('Unknown error')

      const { generate, error } = usePasswordGenerator()

      await generate()

      expect(error.value).toBe('Erreur lors de la génération')
    })

    it('should set isGenerating to true during generation', async () => {
      let resolvePromise: (value: unknown) => void
      const pendingPromise = new Promise((resolve) => {
        resolvePromise = resolve
      })

      vi.mocked(securityApi.generatePassword).mockReturnValue(pendingPromise as never)

      const { generate, isGenerating } = usePasswordGenerator()

      const generatePromise = generate()

      expect(isGenerating.value).toBe(true)

      resolvePromise!({ password: 'test', options: {} })
      await generatePromise

      expect(isGenerating.value).toBe(false)
    })

    it('should handle empty passwords array', async () => {
      vi.mocked(securityApi.generatePassword).mockResolvedValue({
        passwords: [],
        options: { length: 8 },
      })

      const { generate, generatedPassword, generatedPasswords } = usePasswordGenerator()

      const result = await generate()

      expect(result).toBeNull()
      expect(generatedPassword.value).toBeNull()
      expect(generatedPasswords.value).toEqual([])
    })
  })

  describe('generateMultiple', () => {
    it('should generate multiple passwords', async () => {
      vi.mocked(securityApi.generatePassword).mockResolvedValue({
        passwords: ['Pass1@!', 'Pass2@!', 'Pass3@!', 'Pass4@!', 'Pass5@!'],
        options: { length: 16, count: 5 },
      })

      const { generateMultiple, generatedPasswords, generatedPassword } = usePasswordGenerator()

      const result = await generateMultiple(5)

      expect(result).toHaveLength(5)
      expect(generatedPasswords.value).toEqual([
        'Pass1@!',
        'Pass2@!',
        'Pass3@!',
        'Pass4@!',
        'Pass5@!',
      ])
      expect(generatedPassword.value).toBe('Pass1@!')
    })

    it('should generate multiple passwords with custom options', async () => {
      const customOptions = { length: 12, includeSpecial: false }

      vi.mocked(securityApi.generatePassword).mockResolvedValue({
        passwords: ['Pass1', 'Pass2', 'Pass3'],
        options: { ...customOptions, count: 3 },
      })

      const { generateMultiple } = usePasswordGenerator()

      await generateMultiple(3, customOptions)

      expect(securityApi.generatePassword).toHaveBeenCalledWith(
        expect.objectContaining({ count: 3 })
      )
    })

    it('should handle single password response in generateMultiple', async () => {
      vi.mocked(securityApi.generatePassword).mockResolvedValue({
        password: 'SinglePass',
        options: { length: 16 },
      })

      const { generateMultiple, generatedPasswords } = usePasswordGenerator()

      const result = await generateMultiple(1)

      expect(result).toEqual(['SinglePass'])
      expect(generatedPasswords.value).toEqual(['SinglePass'])
    })

    it('should handle error in generateMultiple', async () => {
      vi.mocked(securityApi.generatePassword).mockRejectedValue(new Error('API Error'))

      const { generateMultiple, error } = usePasswordGenerator()

      const result = await generateMultiple(5)

      expect(result).toEqual([])
      expect(error.value).toBe('API Error')
    })

    it('should handle non-Error exception in generateMultiple', async () => {
      vi.mocked(securityApi.generatePassword).mockRejectedValue('Unknown')

      const { generateMultiple, error } = usePasswordGenerator()

      await generateMultiple(3)

      expect(error.value).toBe('Erreur lors de la génération')
    })
  })

  describe('evaluate', () => {
    it('should evaluate password strength', async () => {
      const mockResult = {
        score: 5,
        strength: 'fort' as const,
        feedback: [],
        isValid: true,
      }

      vi.mocked(securityApi.evaluatePassword).mockResolvedValue(mockResult)

      const { evaluate, strengthResult, isEvaluating, error } = usePasswordGenerator()

      const result = await evaluate('SecureP@ss123!')

      expect(result).toEqual(mockResult)
      expect(strengthResult.value).toEqual(mockResult)
      expect(isEvaluating.value).toBe(false)
      expect(error.value).toBeNull()
    })

    it('should return null for empty password', async () => {
      const { evaluate, strengthResult } = usePasswordGenerator()

      const result = await evaluate('')

      expect(result).toBeNull()
      expect(strengthResult.value).toBeNull()
      expect(securityApi.evaluatePassword).not.toHaveBeenCalled()
    })

    it('should handle error during evaluation', async () => {
      vi.mocked(securityApi.evaluatePassword).mockRejectedValue(new Error('Evaluation failed'))

      const { evaluate, strengthResult, error } = usePasswordGenerator()

      const result = await evaluate('test')

      expect(result).toBeNull()
      expect(strengthResult.value).toBeNull()
      expect(error.value).toBe('Evaluation failed')
    })

    it('should handle non-Error exception during evaluation', async () => {
      vi.mocked(securityApi.evaluatePassword).mockRejectedValue('Unknown')

      const { evaluate, error } = usePasswordGenerator()

      await evaluate('test')

      expect(error.value).toBe("Erreur lors de l'évaluation")
    })

    it('should set isEvaluating to true during evaluation', async () => {
      let resolvePromise: (value: unknown) => void
      const pendingPromise = new Promise((resolve) => {
        resolvePromise = resolve
      })

      vi.mocked(securityApi.evaluatePassword).mockReturnValue(pendingPromise as never)

      const { evaluate, isEvaluating } = usePasswordGenerator()

      const evalPromise = evaluate('test')

      expect(isEvaluating.value).toBe(true)

      resolvePromise!({ score: 3, strength: 'moyen', feedback: [], isValid: true })
      await evalPromise

      expect(isEvaluating.value).toBe(false)
    })
  })

  describe('reset', () => {
    it('should reset all state to initial values', async () => {
      vi.mocked(securityApi.generatePassword).mockResolvedValue({
        password: 'TestPass',
        options: { length: 16 },
      })

      const {
        generate,
        reset,
        generatedPassword,
        generatedPasswords,
        strengthResult,
        error,
        options,
      } = usePasswordGenerator()

      await generate()
      options.value.length = 20

      reset()

      expect(generatedPassword.value).toBeNull()
      expect(generatedPasswords.value).toEqual([])
      expect(strengthResult.value).toBeNull()
      expect(error.value).toBeNull()
      expect(options.value.length).toBe(16)
    })
  })

  describe('copyToClipboard', () => {
    it('should copy password to clipboard', async () => {
      const mockWriteText = vi.fn().mockResolvedValue(undefined)
      Object.assign(navigator, {
        clipboard: { writeText: mockWriteText },
      })

      vi.mocked(securityApi.generatePassword).mockResolvedValue({
        password: 'TestPass',
        options: { length: 16 },
      })

      const { generate, copyToClipboard } = usePasswordGenerator()

      await generate()
      const result = await copyToClipboard()

      expect(result).toBe(true)
      expect(mockWriteText).toHaveBeenCalledWith('TestPass')
    })

    it('should copy provided password to clipboard', async () => {
      const mockWriteText = vi.fn().mockResolvedValue(undefined)
      Object.assign(navigator, {
        clipboard: { writeText: mockWriteText },
      })

      const { copyToClipboard } = usePasswordGenerator()

      const result = await copyToClipboard('CustomPassword')

      expect(result).toBe(true)
      expect(mockWriteText).toHaveBeenCalledWith('CustomPassword')
    })

    it('should return false if no password to copy', async () => {
      const { copyToClipboard } = usePasswordGenerator()

      const result = await copyToClipboard()

      expect(result).toBe(false)
    })

    it('should return false on clipboard error', async () => {
      const mockWriteText = vi.fn().mockRejectedValue(new Error('Clipboard error'))
      Object.assign(navigator, {
        clipboard: { writeText: mockWriteText },
      })

      const { copyToClipboard } = usePasswordGenerator()

      const result = await copyToClipboard('TestPassword')

      expect(result).toBe(false)
    })
  })

  describe('computed properties', () => {
    describe('strengthColor', () => {
      it('should return secondary color when no result', () => {
        const { strengthColor } = usePasswordGenerator()

        expect(strengthColor.value).toBe('bg-secondary-600')
      })

      it('should return error color for très faible strength', async () => {
        vi.mocked(securityApi.evaluatePassword).mockResolvedValue({
          score: 1,
          strength: 'très faible',
          feedback: ['Too short'],
          isValid: false,
        })

        const { evaluate, strengthColor } = usePasswordGenerator()

        await evaluate('a')

        expect(strengthColor.value).toBe('bg-error')
      })

      it('should return error color for faible strength', async () => {
        vi.mocked(securityApi.evaluatePassword).mockResolvedValue({
          score: 2,
          strength: 'faible',
          feedback: ['Add special chars'],
          isValid: false,
        })

        const { evaluate, strengthColor } = usePasswordGenerator()

        await evaluate('password')

        expect(strengthColor.value).toBe('bg-error')
      })

      it('should return warning color for moyen strength', async () => {
        vi.mocked(securityApi.evaluatePassword).mockResolvedValue({
          score: 3,
          strength: 'moyen',
          feedback: [],
          isValid: true,
        })

        const { evaluate, strengthColor } = usePasswordGenerator()

        await evaluate('Password1')

        expect(strengthColor.value).toBe('bg-warning')
      })

      it('should return success color for fort strength', async () => {
        vi.mocked(securityApi.evaluatePassword).mockResolvedValue({
          score: 5,
          strength: 'fort',
          feedback: [],
          isValid: true,
        })

        const { evaluate, strengthColor } = usePasswordGenerator()

        await evaluate('Password1!')

        expect(strengthColor.value).toBe('bg-success')
      })

      it('should return success color for excellent strength', async () => {
        vi.mocked(securityApi.evaluatePassword).mockResolvedValue({
          score: 6,
          strength: 'excellent',
          feedback: [],
          isValid: true,
        })

        const { evaluate, strengthColor } = usePasswordGenerator()

        await evaluate('SecureP@ssword123!')

        expect(strengthColor.value).toBe('bg-success')
      })
    })

    describe('strengthTextColor', () => {
      it('should convert bg- to text- prefix', () => {
        const { strengthTextColor } = usePasswordGenerator()

        expect(strengthTextColor.value).toBe('text-secondary-600')
      })

      it('should return text-error for weak passwords', async () => {
        vi.mocked(securityApi.evaluatePassword).mockResolvedValue({
          score: 1,
          strength: 'très faible',
          feedback: [],
          isValid: false,
        })

        const { evaluate, strengthTextColor } = usePasswordGenerator()

        await evaluate('a')

        expect(strengthTextColor.value).toBe('text-error')
      })
    })

    describe('strengthPercentage', () => {
      it('should return 0 when no result', () => {
        const { strengthPercentage } = usePasswordGenerator()

        expect(strengthPercentage.value).toBe(0)
      })

      it('should calculate percentage based on score', async () => {
        vi.mocked(securityApi.evaluatePassword).mockResolvedValue({
          score: 3,
          strength: 'moyen',
          feedback: [],
          isValid: true,
        })

        const { evaluate, strengthPercentage } = usePasswordGenerator()

        await evaluate('Password1')

        expect(strengthPercentage.value).toBe(50)
      })

      it('should return 100% for max score', async () => {
        vi.mocked(securityApi.evaluatePassword).mockResolvedValue({
          score: 6,
          strength: 'excellent',
          feedback: [],
          isValid: true,
        })

        const { evaluate, strengthPercentage } = usePasswordGenerator()

        await evaluate('SuperSecure!')

        expect(strengthPercentage.value).toBe(100)
      })
    })
  })
})
