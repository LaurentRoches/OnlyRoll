import { describe, it, expect, beforeEach, vi } from 'vitest'
import { chatApi } from '@/services/api/chatApi'
import * as apiClient from '@/services/api/apiClient'
import { MessageType } from '@/types/game'

vi.mock('@/services/api/apiClient')

describe('chatApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('list', () => {
    it('should get messages without options', async () => {
      const mockMessages = [{ id: 1, content: 'Hello' }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      const result = await chatApi.list(1)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages')
      expect(result).toEqual(mockMessages)
    })

    it('should get messages with limit', async () => {
      const mockMessages = [{ id: 1 }, { id: 2 }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      await chatApi.list(1, { limit: 10 })

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages?limit=10')
    })

    it('should get messages with before option', async () => {
      const mockMessages = [{ id: 1 }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      await chatApi.list(1, { before: '2024-01-01' })

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages?before=2024-01-01')
    })

    it('should get messages with after option', async () => {
      const mockMessages = [{ id: 1 }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      await chatApi.list(1, { after: '2024-01-01' })

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages?after=2024-01-01')
    })

    it('should get messages with types filter', async () => {
      const mockMessages = [{ id: 1 }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      await chatApi.list(1, { types: [MessageType.CHAT, MessageType.EMOTE] })

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages?types=chat%2Cemote')
    })

    it('should get messages with all options', async () => {
      const mockMessages = [{ id: 1 }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      await chatApi.list(1, { limit: 10, before: '2024-01-01', after: '2023-01-01', types: [MessageType.CHAT] })

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages?limit=10&before=2024-01-01&after=2023-01-01&types=chat')
    })
  })

  describe('listRecent', () => {
    it('should get recent messages', async () => {
      const mockMessages = [{ id: 1 }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      await chatApi.listRecent(1, 20)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages?limit=20')
    })

    it('should use default limit of 50', async () => {
      const mockMessages = [{ id: 1 }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      await chatApi.listRecent(1)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages?limit=50')
    })
  })

  describe('listSince', () => {
    it('should get messages since timestamp', async () => {
      const mockMessages = [{ id: 1 }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      await chatApi.listSince(1, '2024-01-01T00:00:00Z')

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages/since?since=2024-01-01T00%3A00%3A00Z')
    })
  })

  describe('send', () => {
    it('should send chat message', async () => {
      const mockMessage = { id: 1, type: 'chat', content: 'Test' }
      vi.mocked(apiClient.post).mockResolvedValue(mockMessage)

      const result = await chatApi.send(1, { type: MessageType.CHAT, content: 'Test', isInCharacter: false })

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/chat/messages', { type: MessageType.CHAT, content: 'Test', isInCharacter: false })
      expect(result).toEqual(mockMessage)
    })
  })

  describe('sendChat', () => {
    it('should send simple chat', async () => {
      const mockMessage = { id: 1, type: 'chat' }
      vi.mocked(apiClient.post).mockResolvedValue(mockMessage)

      await chatApi.sendChat(1, 'Hello', false)

      expect(apiClient.post).toHaveBeenCalled()
    })

    it('should send chat with default isInCharacter false', async () => {
      const mockMessage = { id: 1, type: 'chat' }
      vi.mocked(apiClient.post).mockResolvedValue(mockMessage)

      await chatApi.sendChat(1, 'Hello')

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/chat/messages', {
        type: MessageType.CHAT,
        content: 'Hello',
        isInCharacter: false
      })
    })
  })

  describe('sendEmote', () => {
    it('should send emote message', async () => {
      const mockMessage = { id: 1, type: 'emote', content: 'smiles' }
      vi.mocked(apiClient.post).mockResolvedValue(mockMessage)

      const result = await chatApi.sendEmote(1, 'smiles')

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/chat/messages', {
        type: MessageType.EMOTE,
        content: 'smiles',
        isInCharacter: true
      })
      expect(result).toEqual(mockMessage)
    })
  })

  describe('sendWhisper', () => {
    it('should send whisper message', async () => {
      const mockMessage = { id: 1, type: 'whisper', content: 'Secret' }
      vi.mocked(apiClient.post).mockResolvedValue(mockMessage)

      const result = await chatApi.sendWhisper(1, 5, 'Secret')

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/chat/messages', {
        type: MessageType.WHISPER,
        content: 'Secret',
        recipientId: 5
      })
      expect(result).toEqual(mockMessage)
    })
  })

  describe('sendSystem', () => {
    it('should send system message', async () => {
      const mockMessage = { id: 1, type: 'system', content: 'Game started' }
      vi.mocked(apiClient.post).mockResolvedValue(mockMessage)

      const result = await chatApi.sendSystem(1, 'Game started')

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/chat/messages', {
        type: MessageType.SYSTEM,
        content: 'Game started'
      })
      expect(result).toEqual(mockMessage)
    })
  })

  describe('rollDice', () => {
    it('should roll dice', async () => {
      const mockRoll = { id: 1, type: 'dice_roll', formula: '2d6' }
      vi.mocked(apiClient.post).mockResolvedValue(mockRoll)

      const result = await chatApi.rollDice(1, { formula: '2d6', isInCharacter: false })

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/chat/roll-dice', { formula: '2d6', isInCharacter: false, reason: undefined, isVisible: undefined, recipientId: undefined })
      expect(result).toEqual(mockRoll)
    })
  })

  describe('delete', () => {
    it('should delete message', async () => {
      vi.mocked(apiClient.delete).mockResolvedValue(undefined)

      await chatApi.delete(123)

      expect(apiClient.delete).toHaveBeenCalledWith('/messages/123')
    })
  })

  describe('listWhispers', () => {
    it('should get whispers without userId', async () => {
      const mockMessages = [{ id: 1, type: 'whisper' }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      const result = await chatApi.listWhispers(1)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages/type/whisper?limit=50')
      expect(result).toEqual(mockMessages)
    })

    it('should get whispers with userId', async () => {
      const mockMessages = [{ id: 1, type: 'whisper' }]
      vi.mocked(apiClient.get).mockResolvedValue(mockMessages)

      const result = await chatApi.listWhispers(1, 5, 20)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/messages/type/whisper?limit=20&userId=5')
      expect(result).toEqual(mockMessages)
    })
  })

  describe('markAsRead', () => {
    it('should mark messages as read', async () => {
      vi.mocked(apiClient.post).mockResolvedValue(undefined)

      await chatApi.markAsRead(1, [1, 2, 3])

      expect(apiClient.post).toHaveBeenCalledWith('/games/1/chat/messages/read', {
        messageIds: [1, 2, 3]
      })
    })
  })

  describe('getStats', () => {
    it('should get chat stats', async () => {
      const mockStats = { totalMessages: 100, byType: {} }
      vi.mocked(apiClient.get).mockResolvedValue(mockStats)

      const result = await chatApi.getStats(1)

      expect(apiClient.get).toHaveBeenCalledWith('/games/1/chat/stats')
      expect(result).toEqual(mockStats)
    })
  })
})
