/**
 * Composable Vue pour utiliser le service Mercure
 * Permet d'utiliser Mercure dans les composants Vue avec la Composition API
 */

import { onUnmounted, ref } from 'vue'
import { mercureService, type EventType } from '@/services/mercure'

/**
 * Type pour les callbacks d'événements
 */
type EventCallback<T = unknown> = (data: T) => void

/**
 * Composable pour gérer la connexion Mercure dans un composant Vue
 * Nettoie automatiquement la connexion lors du démontage du composant
 *
 * @example
 * ```ts
 * const { connect, on, isConnected } = useMercure()
 *
 * onMounted(() => {
 *   connect(gameId)
 *   on('chat', (data) => console.log('Message reçu:', data))
 *   on('dice', (data) => console.log('Dés lancés:', data))
 * })
 * ```
 */
export function useMercure() {
  const isConnected = ref(false)
  const connectionState = ref<'connecting' | 'open' | 'closed'>('closed')

  const registeredCallbacks = new Map<EventType, Set<EventCallback>>()

  /**
   * Connecter à Mercure pour une partie spécifique
   * @param gameId - ID de la partie
   */
  const connect = (gameId: number): void => {
    mercureService.connect(gameId)
    updateConnectionState()
  }

  /**
   * Se déconnecter de Mercure
   */
  const disconnect = (): void => {
    registeredCallbacks.forEach((callbacks, eventType) => {
      callbacks.forEach((callback) => {
        mercureService.off(eventType, callback)
      })
    })
    registeredCallbacks.clear()

    mercureService.disconnect()
    updateConnectionState()
  }

  /**
   * Écouter un type d'événement spécifique
   * Les listeners sont automatiquement nettoyés lors du démontage
   *
   * @param eventType - Type d'événement à écouter ('chat' | 'token' | 'map' | 'dice' | 'player' | 'system')
   * @param callback - Fonction appelée lors de la réception
   */
  const on = <T = unknown>(eventType: EventType, callback: EventCallback<T>): void => {
    if (!registeredCallbacks.has(eventType)) {
      registeredCallbacks.set(eventType, new Set())
    }
    registeredCallbacks.get(eventType)!.add(callback as EventCallback)

    mercureService.on(eventType, callback)
  }

  /**
   * Arrêter d'écouter un événement
   * @param eventType - Type d'événement
   * @param callback - Fonction à retirer
   */
  const off = <T = unknown>(eventType: EventType, callback: EventCallback<T>): void => {
    const callbacks = registeredCallbacks.get(eventType)
    if (callbacks) {
      callbacks.delete(callback as EventCallback)
      if (callbacks.size === 0) {
        registeredCallbacks.delete(eventType)
      }
    }

    mercureService.off(eventType, callback as EventCallback)
  }

  /**
   * Mettre à jour l'état de connexion réactif
   */
  const updateConnectionState = (): void => {
    isConnected.value = mercureService.isConnected()
    connectionState.value = mercureService.getConnectionState()
  }

  /**
   * Vérifier l'état de connexion
   */
  const checkConnection = (): boolean => {
    updateConnectionState()
    return isConnected.value
  }

  onUnmounted(() => {
    registeredCallbacks.forEach((callbacks, eventType) => {
      callbacks.forEach((callback) => {
        mercureService.off(eventType, callback)
      })
    })
    registeredCallbacks.clear()
  })

  return {
    isConnected,
    connectionState,

    connect,
    disconnect,
    on,
    off,
    checkConnection,
  }
}

/**
 * Export des types pour utilisation dans les composants
 */
export type { EventType, EventCallback }
