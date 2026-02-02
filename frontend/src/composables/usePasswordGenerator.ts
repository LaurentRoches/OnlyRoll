import { ref, computed } from 'vue'
import {
  securityApi,
  type GeneratePasswordOptions,
  type PasswordStrengthResult,
} from '@/services/api/securityApi'

/**
 * Composable pour la génération et l'évaluation de mots de passe sécurisés
 */
export const usePasswordGenerator = () => {
  const isGenerating = ref(false)
  const isEvaluating = ref(false)
  const generatedPassword = ref<string | null>(null)
  const generatedPasswords = ref<string[]>([])
  const strengthResult = ref<PasswordStrengthResult | null>(null)
  const error = ref<string | null>(null)

  const defaultOptions: GeneratePasswordOptions = {
    length: 16,
    includeLowercase: true,
    includeUppercase: true,
    includeDigits: true,
    includeSpecial: true,
    excludeAmbiguous: false,
    count: 1,
  }

  const options = ref<GeneratePasswordOptions>({ ...defaultOptions })

  /**
   * Génère un mot de passe sécurisé
   */
  const generate = async (customOptions?: GeneratePasswordOptions): Promise<string | null> => {
    isGenerating.value = true
    error.value = null

    try {
      const opts = customOptions || options.value
      const response = await securityApi.generatePassword(opts)

      if ('passwords' in response) {
        generatedPasswords.value = response.passwords
        generatedPassword.value = response.passwords[0] || null
      } else {
        generatedPassword.value = response.password
        generatedPasswords.value = [response.password]
      }

      return generatedPassword.value
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur lors de la génération'
      return null
    } finally {
      isGenerating.value = false
    }
  }

  /**
   * Génère plusieurs mots de passe
   */
  const generateMultiple = async (
    count: number = 5,
    customOptions?: Omit<GeneratePasswordOptions, 'count'>
  ): Promise<string[]> => {
    isGenerating.value = true
    error.value = null

    try {
      const opts = { ...(customOptions || options.value), count }
      const response = await securityApi.generatePassword(opts)

      if ('passwords' in response) {
        generatedPasswords.value = response.passwords
        generatedPassword.value = response.passwords[0] || null
      } else {
        generatedPasswords.value = [response.password]
        generatedPassword.value = response.password
      }

      return generatedPasswords.value
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur lors de la génération'
      return []
    } finally {
      isGenerating.value = false
    }
  }

  /**
   * Évalue la force d'un mot de passe
   */
  const evaluate = async (password: string): Promise<PasswordStrengthResult | null> => {
    if (!password) {
      strengthResult.value = null
      return null
    }

    isEvaluating.value = true
    error.value = null

    try {
      const result = await securityApi.evaluatePassword(password)
      strengthResult.value = result
      return result
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Erreur lors de l'évaluation"
      return null
    } finally {
      isEvaluating.value = false
    }
  }

  /**
   * Réinitialise l'état
   */
  const reset = () => {
    generatedPassword.value = null
    generatedPasswords.value = []
    strengthResult.value = null
    error.value = null
    options.value = { ...defaultOptions }
  }

  /**
   * Copie le mot de passe dans le presse-papiers
   */
  const copyToClipboard = async (password?: string): Promise<boolean> => {
    const toCopy = password || generatedPassword.value
    if (!toCopy) return false

    try {
      await navigator.clipboard.writeText(toCopy)
      return true
    } catch {
      return false
    }
  }

  const strengthColor = computed(() => {
    if (!strengthResult.value) return 'bg-secondary-600'

    switch (strengthResult.value.strength) {
      case 'très faible':
      case 'faible':
        return 'bg-error'
      case 'moyen':
        return 'bg-warning'
      case 'fort':
      case 'excellent':
        return 'bg-success'
      default:
        return 'bg-secondary-600'
    }
  })

  const strengthTextColor = computed(() => {
    return strengthColor.value.replace('bg-', 'text-')
  })

  const strengthPercentage = computed(() => {
    if (!strengthResult.value) return 0
    return (strengthResult.value.score / 6) * 100
  })

  return {
    isGenerating,
    isEvaluating,
    generatedPassword,
    generatedPasswords,
    strengthResult,
    error,
    options,

    generate,
    generateMultiple,
    evaluate,
    reset,
    copyToClipboard,

    strengthColor,
    strengthTextColor,
    strengthPercentage,
  }
}
