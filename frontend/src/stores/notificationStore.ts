import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { GameInvitation } from '@/types/game'
import { userApi } from '@/services/api/userApi'
import { gameApi } from '@/services/api/gameApi'
import { useAuthStore } from './auth'

const MERCURE_URL = import.meta.env.VITE_MERCURE_URL || 'http://localhost:3000/.well-known/mercure'

export const useNotificationStore = defineStore('notifications', () => {
  const invitations = ref<GameInvitation[]>([])
  const isLoading = ref(false)
  let notificationEventSource: EventSource | null = null

  const invitationCount = computed(() => invitations.value.length)

  async function fetchInvitations() {
    isLoading.value = true
    try {
      invitations.value = await userApi.getInvitations()
    } catch (e) {
      console.error('Erreur chargement invitations:', e)
    } finally {
      isLoading.value = false
    }
  }

  async function acceptInvitation(gameId: number) {
    try {
      await gameApi.acceptInvitation(gameId)
      invitations.value = invitations.value.filter((inv) => inv.game.id !== gameId)
    } catch (e) {
      console.error("Erreur lors de l'acceptation de l'invitation:", e)
      throw e
    }
  }

  async function declineInvitation(gameId: number) {
    try {
      await gameApi.declineInvitation(gameId)
      invitations.value = invitations.value.filter((inv) => inv.game.id !== gameId)
    } catch (e) {
      console.error("Erreur lors du refus de l'invitation:", e)
      throw e
    }
  }

  function handleInvitationEvent(data: unknown) {
    const event = data as {
      data: {
        action: string
        game: { id: number; name: string }
        gameMaster: { id: number; pseudo: string }
        invitationId: number
      }
      timestamp: string
    }

    if (event.data?.action === 'invitation_received') {
      const newInvitation: GameInvitation = {
        id: event.data.invitationId,
        game: event.data.game,
        gameMaster: event.data.gameMaster,
        invitedAt: event.timestamp,
      }
      const exists = invitations.value.some((inv) => inv.id === newInvitation.id)
      if (!exists) {
        invitations.value.unshift(newInvitation)
      }
    }
  }

  async function connectToNotifications() {
    if (notificationEventSource) return

    const authStore = useAuthStore()
    const userId = authStore.user?.id
    if (!userId) return

    try {
      await userApi.getNotificationToken()

      const url = new URL(MERCURE_URL)
      url.searchParams.append('topic', `user/${userId}/notification`)

      notificationEventSource = new EventSource(url.toString(), { withCredentials: true })

      notificationEventSource.onmessage = (event: MessageEvent) => {
        try {
          const parsed = JSON.parse(event.data)
          handleInvitationEvent(parsed)
        } catch (e) {
          console.error('Erreur parsing notification Mercure:', e)
        }
      }

      notificationEventSource.onerror = (error) => {
        console.error('Erreur connexion Mercure notifications:', error)
      }
    } catch (e) {
      console.error('Erreur connexion aux notifications Mercure:', e)
    }
  }

  function disconnectFromNotifications() {
    if (notificationEventSource) {
      notificationEventSource.close()
      notificationEventSource = null
    }
  }

  return {
    invitations,
    isLoading,
    invitationCount,
    fetchInvitations,
    acceptInvitation,
    declineInvitation,
    handleInvitationEvent,
    connectToNotifications,
    disconnectFromNotifications,
  }
})
