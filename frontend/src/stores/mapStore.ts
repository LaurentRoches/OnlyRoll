/**
 * Store Pinia pour la gestion des cartes et tokens
 * Gère l'état des cartes, tokens et leur synchronisation temps réel via Mercure
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { mapApi, tokenApi } from '@/services/api'
import type { GameMap, GameToken, CreateTokenDTO, MoveTokenDTO, UpdateTokenDTO } from '@/types/game'
import { TokenType, LayerType } from '@/types/game'
import { logger } from '@/utils/logger'
import i18n from '@/i18n'
import type { MercureTokenEventData, MercureMapEventData } from '@/types/websocket'

export const useMapStore = defineStore('map', () => {
  const activeMap = ref<GameMap | null>(null)
  const allMaps = ref<GameMap[]>([])
  const tokens = ref<GameToken[]>([])
  const currentGameId = ref<number | null>(null)

  const isLoading = ref(false)
  const error = ref<string | null>(null)

  /**
   * Tokens visibles uniquement
   */
  const visibleTokens = computed(() => {
    return tokens.value.filter((token) => token.isVisible)
  })

  /**
   * Tokens par type
   */
  const tokensByType = computed(() => {
    return (type: string) => tokens.value.filter((token) => token.type === type)
  })

  /**
   * Obtenir un token par son ID
   */
  const getTokenById = computed(() => {
    return (id: number) => tokens.value.find((token) => token.id === id)
  })

  /**
   * Vérifier si une carte est chargée
   */
  const hasActiveMap = computed(() => activeMap.value !== null)

  /**
   * Nombre total de tokens
   */
  const tokensCount = computed(() => tokens.value.length)

  /**
   * Dimensions de la carte active
   */
  const mapDimensions = computed(() => {
    if (!activeMap.value) return null
    return {
      width: activeMap.value.width,
      height: activeMap.value.height,
      gridSize: activeMap.value.gridSize,
    }
  })

  /**
   * Charger toutes les cartes d'un jeu
   */
  async function loadGameMaps(gameId: number) {
    isLoading.value = true
    error.value = null

    try {
      allMaps.value = await mapApi.listByGame(gameId)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || i18n.global.t('game.stores.map.loadMapsError')
      } else {
        error.value = i18n.global.t('game.stores.map.loadMapsError')
      }
      logger.error('Erreur loadGameMaps:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger la carte active d'un jeu et ses tokens
   * Ne charge les tokens QUE si la carte existe
   */
  async function loadActiveMap(gameId: number) {
    isLoading.value = true
    error.value = null
    currentGameId.value = gameId

    try {
      const map = await mapApi.getActive(gameId)

      if (map && map.id) {
        activeMap.value = map
        await loadMapTokens(map.id)
      } else {
        activeMap.value = null
        tokens.value = []
        logger.log('ℹAucune carte active pour cette partie')
      }
    } catch (e: unknown) {
      activeMap.value = null
      tokens.value = []

      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || i18n.global.t('game.stores.map.loadActiveError')
      } else {
        error.value = i18n.global.t('game.stores.map.loadActiveError')
      }
      logger.error('Erreur loadActiveMap:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger une carte spécifique par son ID
   */
  async function loadMap(gameId: number, mapId: number) {
    isLoading.value = true
    error.value = null
    currentGameId.value = gameId

    try {
      activeMap.value = await mapApi.getById(mapId)

      if (activeMap.value && activeMap.value.id) {
        await loadMapTokens(activeMap.value.id)
      }
    } catch (e: unknown) {
      activeMap.value = null
      tokens.value = []

      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || i18n.global.t('game.stores.map.loadMapError')
      } else {
        error.value = i18n.global.t('game.stores.map.loadMapError')
      }
      logger.error('Erreur loadMap:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Activer une carte
   */
  async function activateMap(gameId: number, mapId: number) {
    isLoading.value = true
    error.value = null
    currentGameId.value = gameId

    try {
      activeMap.value = await mapApi.activate(gameId, mapId)

      if (activeMap.value && activeMap.value.id) {
        await loadMapTokens(activeMap.value.id)
      }
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || i18n.global.t('game.stores.map.activateError')
      } else {
        error.value = i18n.global.t('game.stores.map.activateError')
      }
      logger.error('Erreur activateMap:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Supprimer une carte
   */
  async function deleteMap(mapId: number) {
    if (!currentGameId.value) {
      throw new Error('GameId not set')
    }

    isLoading.value = true
    error.value = null

    try {
      await mapApi.delete(currentGameId.value, mapId)

      const index = allMaps.value.findIndex((m) => m.id === mapId)
      if (index !== -1) {
        allMaps.value.splice(index, 1)
      }

      if (activeMap.value && activeMap.value.id === mapId) {
        activeMap.value = null
        tokens.value = []
      }
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || i18n.global.t('game.stores.map.deleteError')
      } else {
        error.value = i18n.global.t('game.stores.map.deleteError')
      }
      logger.error('Erreur deleteMap:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Mettre à jour les paramètres d'une carte (ex: grille)
   */
  async function updateMapSettings(
    mapId: number,
    settings: { showGrid?: boolean; gridColor?: string; gridOpacity?: number }
  ) {
    if (!currentGameId.value) {
      throw new Error('GameId not set')
    }

    error.value = null

    try {
      await mapApi.updateSettings(currentGameId.value, mapId, settings)
      logger.log('Paramètres de carte mis à jour:', { mapId, settings })
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message ||
          i18n.global.t('game.stores.map.updateSettingsError')
      } else {
        error.value = i18n.global.t('game.stores.map.updateSettingsError')
      }
      logger.error('Erreur updateMapSettings:', e)
      throw e
    }
  }

  /**
   * Charger les tokens d'une carte
   * Validation stricte des paramètres avant l'appel API
   */
  async function loadMapTokens(mapId: number) {
    if (!currentGameId.value) {
      logger.error('GameId not set. Call loadActiveMap first.')
      throw new Error('GameId not set. Call loadActiveMap first.')
    }

    if (!mapId || typeof mapId !== 'number' || mapId <= 0) {
      logger.error('Invalid mapId:', mapId)
      tokens.value = []
      return
    }

    try {
      logger.log('Chargement des tokens:', { gameId: currentGameId.value, mapId })
      tokens.value = await tokenApi.listVisible(currentGameId.value, mapId)
      logger.log('Tokens chargés:', tokens.value.length)
    } catch (e: unknown) {
      tokens.value = []

      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || i18n.global.t('game.stores.map.loadTokensError')
      } else {
        error.value = i18n.global.t('game.stores.map.loadTokensError')
      }
      logger.error('Erreur loadMapTokens:', e)
    }
  }

  /**
   * HELPER: Construire un DTO de création de token valide
   * S'assure que toutes les propriétés obligatoires sont présentes
   */
  function buildCreateTokenDTO(
    data: Partial<CreateTokenDTO> & { name: string; type: TokenType; x: number; y: number }
  ): CreateTokenDTO {
    const dto: CreateTokenDTO = {
      name: data.name,
      type: data.type,
      x: data.x,
      y: data.y,

      size: data.size ?? 1.0,
      rotation: data.rotation ?? 0,
      isVisible: data.isVisible ?? true,
      isLocked: data.isLocked ?? false,
      layer: data.layer ?? LayerType.TOKENS,
    }

    if (data.imageUrl !== undefined) {
      dto.imageUrl = data.imageUrl
    }

    if (data.settings !== undefined) {
      dto.settings = data.settings
    }

    logger.log('DTO Token construit:', dto)
    return dto
  }

  /**
   * Créer un nouveau token
   * Type correctement les données avec CreateTokenDTO
   */
  async function createToken(
    mapId: number,
    tokenData: Partial<CreateTokenDTO> & { name: string; type: TokenType; x: number; y: number }
  ): Promise<GameToken> {
    if (!currentGameId.value) {
      throw new Error('GameId not set')
    }

    isLoading.value = true
    error.value = null

    try {
      const dto = buildCreateTokenDTO(tokenData)

      logger.log('Envoi de la requête de création de token:', {
        gameId: currentGameId.value,
        mapId,
        dto,
      })

      const newToken = await tokenApi.create(currentGameId.value, mapId, dto)

      logger.log('✅ Token créé avec succès:', newToken)
      tokens.value.push(newToken)
      return newToken
    } catch (e: unknown) {
      logger.error('Erreur lors de la création du token:', e)

      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || i18n.global.t('game.stores.map.createTokenError')
      } else {
        error.value = i18n.global.t('game.stores.map.createTokenError')
      }
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Déplacer un token
   */
  async function moveToken(
    tokenId: number,
    x: number,
    y: number,
    rotation?: number
  ): Promise<GameToken> {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    error.value = null

    try {
      const moveData: MoveTokenDTO = { x, y }
      if (rotation !== undefined) {
        moveData.rotation = rotation
      }

      const updatedToken = await tokenApi.move(
        currentGameId.value,
        activeMap.value.id,
        tokenId,
        moveData
      )
      updateTokenInList(updatedToken)
      return updatedToken
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || i18n.global.t('game.stores.map.moveTokenError')
      } else {
        error.value = i18n.global.t('game.stores.map.moveTokenError')
      }
      logger.error('Erreur moveToken:', e)
      throw e
    }
  }

  /**
   * Mettre à jour un token
   */
  async function updateToken(tokenId: number, updates: Partial<UpdateTokenDTO>) {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    error.value = null

    try {
      const updatedToken = await tokenApi.partialUpdate(
        currentGameId.value,
        activeMap.value.id,
        tokenId,
        updates
      )
      updateTokenInList(updatedToken)
      return updatedToken
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || i18n.global.t('game.stores.map.updateTokenError')
      } else {
        error.value = i18n.global.t('game.stores.map.updateTokenError')
      }
      logger.error('Erreur updateToken:', e)
      throw e
    }
  }

  /**
   * Supprimer un token
   */
  async function deleteToken(tokenId: number) {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    error.value = null

    try {
      await tokenApi.delete(currentGameId.value, activeMap.value.id, tokenId)
      removeTokenFromList(tokenId)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || i18n.global.t('game.stores.map.deleteTokenError')
      } else {
        error.value = i18n.global.t('game.stores.map.deleteTokenError')
      }
      logger.error('Erreur deleteToken:', e)
      throw e
    }
  }

  /**
   * Basculer la visibilité d'un token
   */
  async function toggleTokenVisibility(tokenId: number) {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    const token = getTokenById.value(tokenId)
    if (!token) return

    try {
      let updatedToken: GameToken
      if (token.isVisible) {
        updatedToken = await tokenApi.hide(currentGameId.value, activeMap.value.id, tokenId)
      } else {
        updatedToken = await tokenApi.show(currentGameId.value, activeMap.value.id, tokenId)
      }

      updateTokenInList(updatedToken)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || i18n.global.t('game.stores.map.visibilityError')
      } else {
        error.value = i18n.global.t('game.stores.map.visibilityError')
      }
      logger.error('Erreur toggleTokenVisibility:', e)
      throw e
    }
  }

  /**
   * Verrouiller/Déverrouiller un token
   */
  async function toggleTokenLock(tokenId: number) {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    const token = getTokenById.value(tokenId)
    if (!token) return

    try {
      let updatedToken: GameToken
      if (token.isLocked) {
        updatedToken = await tokenApi.unlock(currentGameId.value, activeMap.value.id, tokenId)
      } else {
        updatedToken = await tokenApi.lock(currentGameId.value, activeMap.value.id, tokenId)
      }

      updateTokenInList(updatedToken)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || i18n.global.t('game.stores.map.lockError')
      } else {
        error.value = i18n.global.t('game.stores.map.lockError')
      }
      logger.error('Erreur toggleTokenLock:', e)
      throw e
    }
  }

  /**
   * Gérer les permissions de contrôle d'un token
   */
  async function manageTokenPermissions(tokenId: number, action: 'add' | 'remove', userId: number) {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    try {
      const updatedToken = await tokenApi.managePermissions(
        currentGameId.value,
        activeMap.value.id,
        tokenId,
        action,
        userId
      )

      updateTokenInList(updatedToken)

      logger.log(`Permission ${action} pour l'utilisateur ${userId} sur le token ${tokenId}`)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || i18n.global.t('game.stores.map.permissionsError')
      } else {
        error.value = i18n.global.t('game.stores.map.permissionsError')
      }
      logger.error('Erreur manageTokenPermissions:', e)
      throw e
    }
  }

  /**
   * Gérer un événement de token reçu via Mercure
   */
  function handleTokenEvent(data: MercureTokenEventData) {
    logger.log('Token event reçu:', data)

    const eventType = data.type || data.action

    switch (eventType) {
      case 'created':
        addTokenToList(data.token)
        break

      case 'updated':
      case 'moved':
        updateTokenInList(data.token)
        break

      case 'deleted':
        removeTokenFromList(data.token.id)
        break

      default:
        logger.warn('Type de token event inconnu:', eventType, data)
    }
  }

  /**
   * Gérer un événement de carte reçu via Mercure
   */
  function handleMapEvent(data: MercureMapEventData) {
    logger.log('Map event reçu:', data)

    switch (data.type) {
      case 'activated':
        if (data.map) {
          activeMap.value = data.map
          if (data.map.id) {
            loadMapTokens(data.map.id).catch((err) => {
              logger.error('Erreur lors du rechargement des tokens:', err)
            })
          }
        }
        break

      case 'updated':
        if (activeMap.value && data.map.id === activeMap.value.id) {
          activeMap.value = data.map
        }
        const mapIndex = allMaps.value.findIndex((m) => m.id === data.map.id)
        if (mapIndex !== -1) {
          allMaps.value[mapIndex] = data.map
        }
        break

      default:
        logger.warn('Type de map event inconnu:', data.type)
    }
  }

  /**
   * Ajouter un token à la liste (évite les doublons)
   */
  function addTokenToList(token: GameToken) {
    const exists = tokens.value.some((t) => t.id === token.id)
    if (!exists) {
      tokens.value.push(token)
    }
  }

  /**
   * Mettre à jour un token dans la liste
   */
  function updateTokenInList(updatedToken: GameToken) {
    const index = tokens.value.findIndex((t) => t.id === updatedToken.id)
    if (index !== -1) {
      tokens.value.splice(index, 1, updatedToken)
    } else {
      tokens.value.push(updatedToken)
    }
  }

  /**
   * Retirer un token de la liste
   */
  function removeTokenFromList(tokenId: number) {
    const index = tokens.value.findIndex((t) => t.id === tokenId)
    if (index !== -1) {
      tokens.value.splice(index, 1)
    }
  }

  /**
   * Réinitialiser le store
   */
  function $reset() {
    activeMap.value = null
    allMaps.value = []
    tokens.value = []
    currentGameId.value = null
    isLoading.value = false
    error.value = null
  }

  return {
    activeMap,
    allMaps,
    tokens,
    currentGameId,
    isLoading,
    error,

    visibleTokens,
    tokensByType,
    getTokenById,
    hasActiveMap,
    tokensCount,
    mapDimensions,

    loadGameMaps,
    loadActiveMap,
    loadMap,
    activateMap,
    deleteMap,
    updateMapSettings,

    loadMapTokens,
    createToken,
    moveToken,
    updateToken,
    deleteToken,
    toggleTokenVisibility,
    toggleTokenLock,
    manageTokenPermissions,

    handleTokenEvent,
    handleMapEvent,

    $reset,
  }
})
