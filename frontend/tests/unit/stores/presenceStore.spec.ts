import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePresenceStore } from '@/stores/presenceStore'

describe('presenceStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('Initial State', () => {
    it('should initialize with empty connected users', () => {
      const store = usePresenceStore()
      expect(store.connectedUsers.size).toBe(0)
    })

    it('should have a lastHeartbeat timestamp', () => {
      const store = usePresenceStore()
      expect(store.lastHeartbeat).toBeGreaterThan(0)
    })
  })

  describe('isUserOnline', () => {
    it('should return false for user not in game', () => {
      const store = usePresenceStore()
      expect(store.isUserOnline(1, 1)).toBe(false)
    })

    it('should return false for game that does not exist', () => {
      const store = usePresenceStore()
      expect(store.isUserOnline(999, 1)).toBe(false)
    })

    it('should return true for user that is online', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      expect(store.isUserOnline(1, 1)).toBe(true)
    })

    it('should return false for user that is not in the game', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      expect(store.isUserOnline(1, 2)).toBe(false)
    })
  })

  describe('getOnlineUsers', () => {
    it('should return empty array for game with no users', () => {
      const store = usePresenceStore()
      expect(store.getOnlineUsers(1)).toEqual([])
    })

    it('should return array of online user IDs', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      store.setUserOnline(1, 2)
      store.setUserOnline(1, 3)

      const users = store.getOnlineUsers(1)
      expect(users).toHaveLength(3)
      expect(users).toContain(1)
      expect(users).toContain(2)
      expect(users).toContain(3)
    })

    it('should return users only for specific game', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      store.setUserOnline(2, 2)

      expect(store.getOnlineUsers(1)).toEqual([1])
      expect(store.getOnlineUsers(2)).toEqual([2])
    })
  })

  describe('getOnlineCount', () => {
    it('should return 0 for game with no users', () => {
      const store = usePresenceStore()
      expect(store.getOnlineCount(1)).toBe(0)
    })

    it('should return correct count of online users', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      store.setUserOnline(1, 2)
      expect(store.getOnlineCount(1)).toBe(2)
    })

    it('should count users per game separately', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      store.setUserOnline(1, 2)
      store.setUserOnline(2, 3)

      expect(store.getOnlineCount(1)).toBe(2)
      expect(store.getOnlineCount(2)).toBe(1)
    })
  })

  describe('setUserOnline', () => {
    it('should add user to game', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)

      expect(store.isUserOnline(1, 1)).toBe(true)
      expect(store.getOnlineCount(1)).toBe(1)
    })

    it('should create new game entry if it does not exist', () => {
      const store = usePresenceStore()
      expect(store.connectedUsers.has(1)).toBe(false)

      store.setUserOnline(1, 1)

      expect(store.connectedUsers.has(1)).toBe(true)
    })

    it('should not duplicate users', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      store.setUserOnline(1, 1)

      expect(store.getOnlineCount(1)).toBe(1)
    })

    it('should handle multiple users in same game', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      store.setUserOnline(1, 2)
      store.setUserOnline(1, 3)

      expect(store.getOnlineCount(1)).toBe(3)
    })
  })

  describe('setUserOffline', () => {
    it('should remove user from game', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      expect(store.isUserOnline(1, 1)).toBe(true)

      store.setUserOffline(1, 1)
      expect(store.isUserOnline(1, 1)).toBe(false)
    })

    it('should handle removing non-existent user gracefully', () => {
      const store = usePresenceStore()
      expect(() => store.setUserOffline(1, 999)).not.toThrow()
    })

    it('should handle removing user from non-existent game gracefully', () => {
      const store = usePresenceStore()
      expect(() => store.setUserOffline(999, 1)).not.toThrow()
    })

    it('should only remove specified user', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)
      store.setUserOnline(1, 2)
      store.setUserOnline(1, 3)

      store.setUserOffline(1, 2)

      expect(store.isUserOnline(1, 1)).toBe(true)
      expect(store.isUserOnline(1, 2)).toBe(false)
      expect(store.isUserOnline(1, 3)).toBe(true)
      expect(store.getOnlineCount(1)).toBe(2)
    })
  })

  describe('setOnlineUsers', () => {
    it('should set complete list of users for game', () => {
      const store = usePresenceStore()
      store.setOnlineUsers(1, [1, 2, 3])

      expect(store.getOnlineCount(1)).toBe(3)
      expect(store.getOnlineUsers(1)).toEqual(expect.arrayContaining([1, 2, 3]))
    })

    it('should replace existing users', () => {
      const store = usePresenceStore()
      store.setOnlineUsers(1, [1, 2, 3])
      store.setOnlineUsers(1, [4, 5])

      expect(store.getOnlineCount(1)).toBe(2)
      expect(store.getOnlineUsers(1)).toEqual(expect.arrayContaining([4, 5]))
      expect(store.isUserOnline(1, 1)).toBe(false)
    })

    it('should handle empty array', () => {
      const store = usePresenceStore()
      store.setOnlineUsers(1, [1, 2])
      store.setOnlineUsers(1, [])

      expect(store.getOnlineCount(1)).toBe(0)
    })
  })

  describe('handlePresenceEvent', () => {
    it('should handle join event', () => {
      const store = usePresenceStore()
      store.handlePresenceEvent({
        gameId: 1,
        userId: 1,
        type: 'join'
      })

      expect(store.isUserOnline(1, 1)).toBe(true)
    })

    it('should handle leave event', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)

      store.handlePresenceEvent({
        gameId: 1,
        userId: 1,
        type: 'leave'
      })

      expect(store.isUserOnline(1, 1)).toBe(false)
    })

    it('should handle heartbeat event', () => {
      const store = usePresenceStore()
      const before = store.lastHeartbeat

      store.handlePresenceEvent({
        gameId: 1,
        userId: 1,
        type: 'heartbeat'
      })

      expect(store.lastHeartbeat).toBeGreaterThanOrEqual(before)
    })

    it('should prioritize onlineUsers list over individual events', () => {
      const store = usePresenceStore()
      store.setUserOnline(1, 1)

      store.handlePresenceEvent({
        gameId: 1,
        userId: 2,
        type: 'join',
        onlineUsers: [3, 4, 5]
      })

      expect(store.getOnlineUsers(1)).toEqual(expect.arrayContaining([3, 4, 5]))
      expect(store.isUserOnline(1, 1)).toBe(false)
      expect(store.isUserOnline(1, 2)).toBe(false)
    })

    it('should update lastHeartbeat on event', () => {
      const store = usePresenceStore()
      const before = store.lastHeartbeat

      setTimeout(() => {
        store.handlePresenceEvent({
          gameId: 1,
          userId: 1,
          type: 'join'
        })

        expect(store.lastHeartbeat).toBeGreaterThanOrEqual(before)
      }, 10)
    })

    it('should handle event with onlineUsers list', () => {
      const store = usePresenceStore()

      store.handlePresenceEvent({
        gameId: 1,
        userId: 1,
        type: 'join',
        onlineUsers: [1, 2, 3, 4]
      })

      expect(store.getOnlineCount(1)).toBe(4)
      expect(store.getOnlineUsers(1)).toEqual(expect.arrayContaining([1, 2, 3, 4]))
    })
  })

  describe('clearGamePresence', () => {
    it('should remove all users from game', () => {
      const store = usePresenceStore()
      store.setOnlineUsers(1, [1, 2, 3])

      store.clearGamePresence(1)

      expect(store.getOnlineCount(1)).toBe(0)
      expect(store.connectedUsers.has(1)).toBe(false)
    })

    it('should only clear specified game', () => {
      const store = usePresenceStore()
      store.setOnlineUsers(1, [1, 2])
      store.setOnlineUsers(2, [3, 4])

      store.clearGamePresence(1)

      expect(store.getOnlineCount(1)).toBe(0)
      expect(store.getOnlineCount(2)).toBe(2)
    })

    it('should handle clearing non-existent game gracefully', () => {
      const store = usePresenceStore()
      expect(() => store.clearGamePresence(999)).not.toThrow()
    })
  })

  describe('$reset', () => {
    it('should clear all games and users', () => {
      const store = usePresenceStore()
      store.setOnlineUsers(1, [1, 2, 3])
      store.setOnlineUsers(2, [4, 5])

      store.$reset()

      expect(store.connectedUsers.size).toBe(0)
      expect(store.getOnlineCount(1)).toBe(0)
      expect(store.getOnlineCount(2)).toBe(0)
    })

    it('should reset lastHeartbeat', () => {
      const store = usePresenceStore()
      const before = store.lastHeartbeat

      store.$reset()

      expect(store.lastHeartbeat).toBeGreaterThanOrEqual(before)
    })
  })

  describe('Multiple Games', () => {
    it('should handle multiple games independently', () => {
      const store = usePresenceStore()

      store.setUserOnline(1, 1)
      store.setUserOnline(1, 2)
      store.setUserOnline(2, 3)
      store.setUserOnline(2, 4)
      store.setUserOnline(3, 5)

      expect(store.getOnlineCount(1)).toBe(2)
      expect(store.getOnlineCount(2)).toBe(2)
      expect(store.getOnlineCount(3)).toBe(1)
    })

    it('should allow same user in multiple games', () => {
      const store = usePresenceStore()

      store.setUserOnline(1, 1)
      store.setUserOnline(2, 1)
      store.setUserOnline(3, 1)

      expect(store.isUserOnline(1, 1)).toBe(true)
      expect(store.isUserOnline(2, 1)).toBe(true)
      expect(store.isUserOnline(3, 1)).toBe(true)
    })
  })
})
