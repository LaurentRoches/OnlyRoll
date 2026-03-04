import { defineStore } from 'pinia'
import { ref, triggerRef } from 'vue'

/**
 * Store pour gérer la présence en temps réel des joueurs
 */
export const usePresenceStore = defineStore('presence', () => {
  const connectedUsers = ref<Map<number, Set<number>>>(new Map())

  const lastHeartbeat = ref<number>(Date.now())

  /**
   * Vérifier si un utilisateur est connecté dans une partie
   */
  function isUserOnline(gameId: number, userId: number): boolean {
    const gameUsers = connectedUsers.value.get(gameId)
    return gameUsers ? gameUsers.has(userId) : false
  }

  /**
   * Obtenir la liste des utilisateurs connectés pour une partie
   */
  function getOnlineUsers(gameId: number): number[] {
    const gameUsers = connectedUsers.value.get(gameId)
    return gameUsers ? Array.from(gameUsers) : []
  }

  /**
   * Obtenir le nombre d'utilisateurs connectés pour une partie
   */
  function getOnlineCount(gameId: number): number {
    const gameUsers = connectedUsers.value.get(gameId)
    return gameUsers ? gameUsers.size : 0
  }

  // ========== Actions ==========
  /**
   * Marquer un utilisateur comme connecté
   */
  function setUserOnline(gameId: number, userId: number) {
    if (!connectedUsers.value.has(gameId)) {
      connectedUsers.value.set(gameId, new Set())
    }
    connectedUsers.value.get(gameId)!.add(userId)
    triggerRef(connectedUsers)
  }

  /**
   * Marquer un utilisateur comme déconnecté
   */
  function setUserOffline(gameId: number, userId: number) {
    const gameUsers = connectedUsers.value.get(gameId)
    if (gameUsers) {
      gameUsers.delete(userId)
      triggerRef(connectedUsers)
    }
  }

  /**
   * Mettre à jour la liste complète des utilisateurs connectés pour une partie
   */
  function setOnlineUsers(gameId: number, userIds: number[]) {
    connectedUsers.value.set(gameId, new Set(userIds))
    triggerRef(connectedUsers)
  }

  /**
   * Gérer un événement de présence depuis Mercure
   */
  function handlePresenceEvent(data: {
    gameId: number
    userId: number
    type: 'join' | 'leave' | 'heartbeat'
    onlineUsers?: number[]
  }) {
    const { gameId, userId, type, onlineUsers } = data

    if (onlineUsers) {
      setOnlineUsers(gameId, onlineUsers)
    } else {
      if (type === 'join') {
        setUserOnline(gameId, userId)
      } else if (type === 'leave') {
        setUserOffline(gameId, userId)
      }
    }

    lastHeartbeat.value = Date.now()
  }

  /**
   * Nettoyer les données de présence pour une partie
   */
  function clearGamePresence(gameId: number) {
    connectedUsers.value.delete(gameId)
    triggerRef(connectedUsers)
  }

  /**
   * Réinitialiser tout le store
   */
  function $reset() {
    connectedUsers.value.clear()
    triggerRef(connectedUsers)
    lastHeartbeat.value = Date.now()
  }

  return {
    connectedUsers,
    lastHeartbeat,

    isUserOnline,
    getOnlineUsers,
    getOnlineCount,

    setUserOnline,
    setUserOffline,
    setOnlineUsers,
    handlePresenceEvent,
    clearGamePresence,
    $reset,
  }
})
