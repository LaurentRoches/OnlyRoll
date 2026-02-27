import { ref, watchEffect, onUnmounted } from 'vue'

/**
 * Options pour la configuration SEO
 */
export interface SeoOptions {
  /** Titre de la page (sera suffixé avec le nom du site) */
  title: string
  /** Description de la page pour les meta tags */
  description: string
  /** URL canonique de la page */
  canonical?: string
  /** Image pour Open Graph (URL absolue) */
  ogImage?: string
  /** Type de contenu Open Graph */
  ogType?: 'website' | 'article' | 'profile'
  /** Ne pas indexer cette page */
  noindex?: boolean
  /** Mots-clés pour la page (optionnel) */
  keywords?: string[]
  /** Données structurées JSON-LD */
  structuredData?: Record<string, unknown>
}

/**
 * Configuration par défaut
 */
const defaults = {
  siteName: 'OnlyRoll',
  baseTitle: 'OnlyRoll - VTT D&D 5e',
  baseUrl: 'https://onlyroll.fr',
  defaultDescription:
    'OnlyRoll est une table de jeu virtuelle (VTT) gratuite pour jouer à Dungeons & Dragons 5e en ligne avec vos amis.',
  defaultOgImage: '/og-image.png',
  locale: 'fr_FR',
  twitterSite: '@onlyroll',
}

/**
 * Composable pour la gestion du SEO (meta tags, Open Graph, Twitter Cards)
 *
 * @example
 * ```ts
 * // Dans un composant Vue
 * import { useSeo } from '@/composables/useSeo'
 *
 * useSeo({
 *   title: 'Tableau de bord',
 *   description: 'Gérez vos parties et personnages',
 *   noindex: true, // Pour les pages privées
 * })
 * ```
 */
export function useSeo(options: SeoOptions) {
  const metaTags = ref<HTMLMetaElement[]>([])
  const linkTags = ref<HTMLLinkElement[]>([])
  const scriptTags = ref<HTMLScriptElement[]>([])

  /**
   * Crée ou met à jour une meta tag
   */
  const setMetaTag = (name: string, content: string, isProperty = false) => {
    const attribute = isProperty ? 'property' : 'name'
    let tag = document.querySelector<HTMLMetaElement>(`meta[${attribute}="${name}"]`)

    if (!tag) {
      tag = document.createElement('meta')
      tag.setAttribute(attribute, name)
      document.head.appendChild(tag)
      metaTags.value.push(tag)
    }

    tag.setAttribute('content', content)
  }

  /**
   * Crée ou met à jour une link tag
   */
  const setLinkTag = (rel: string, href: string) => {
    let tag = document.querySelector<HTMLLinkElement>(`link[rel="${rel}"]`)

    if (!tag) {
      tag = document.createElement('link')
      tag.setAttribute('rel', rel)
      document.head.appendChild(tag)
      linkTags.value.push(tag)
    }

    tag.setAttribute('href', href)
  }

  /**
   * Ajoute des données structurées JSON-LD
   */
  const setStructuredData = (data: Record<string, unknown>) => {
    const existingScript = document.querySelector('script[type="application/ld+json"]')
    if (existingScript) {
      existingScript.remove()
    }

    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.textContent = JSON.stringify({
      '@context': 'https://schema.org',
      ...data,
    })
    document.head.appendChild(script)
    scriptTags.value.push(script)
  }

  /**
   * Applique les options SEO
   */
  const applySeo = () => {
    const fullTitle = options.title
      ? `${options.title} | ${defaults.baseTitle}`
      : defaults.baseTitle
    document.title = fullTitle

    const description = options.description || defaults.defaultDescription
    setMetaTag('description', description)

    const robotsContent = options.noindex ? 'noindex, nofollow' : 'index, follow'
    setMetaTag('robots', robotsContent)

    if (options.keywords && options.keywords.length > 0) {
      setMetaTag('keywords', options.keywords.join(', '))
    }

    const canonicalUrl = options.canonical || `${defaults.baseUrl}${window.location.pathname}`
    setLinkTag('canonical', canonicalUrl)

    setMetaTag('og:title', options.title || defaults.siteName, true)
    setMetaTag('og:description', description, true)
    setMetaTag('og:type', options.ogType || 'website', true)
    setMetaTag('og:url', canonicalUrl, true)
    setMetaTag('og:image', options.ogImage || `${defaults.baseUrl}${defaults.defaultOgImage}`, true)
    setMetaTag('og:site_name', defaults.siteName, true)
    setMetaTag('og:locale', defaults.locale, true)

    setMetaTag('twitter:card', 'summary_large_image')
    setMetaTag('twitter:site', defaults.twitterSite)
    setMetaTag('twitter:title', options.title || defaults.siteName)
    setMetaTag('twitter:description', description)
    setMetaTag('twitter:image', options.ogImage || `${defaults.baseUrl}${defaults.defaultOgImage}`)

    if (options.structuredData) {
      setStructuredData(options.structuredData)
    }
  }

  watchEffect(() => {
    applySeo()
  })

  onUnmounted(() => {
    scriptTags.value.forEach((script) => {
      script.remove()
    })
  })

  return {
    /**
     * Met à jour le titre dynamiquement
     */
    updateTitle: (newTitle: string) => {
      options.title = newTitle
      document.title = newTitle ? `${newTitle} | ${defaults.baseTitle}` : defaults.baseTitle
      setMetaTag('og:title', newTitle || defaults.siteName, true)
      setMetaTag('twitter:title', newTitle || defaults.siteName)
    },

    /**
     * Met à jour la description dynamiquement
     */
    updateDescription: (newDescription: string) => {
      options.description = newDescription
      setMetaTag('description', newDescription)
      setMetaTag('og:description', newDescription, true)
      setMetaTag('twitter:description', newDescription)
    },
  }
}

/**
 * Hook pour les pages publiques (indexables)
 */
export function usePublicSeo(options: Omit<SeoOptions, 'noindex'>) {
  return useSeo({ ...options, noindex: false })
}

/**
 * Hook pour les pages privées (non indexables)
 */
export function usePrivateSeo(options: Omit<SeoOptions, 'noindex'>) {
  return useSeo({ ...options, noindex: true })
}

/**
 * Données structurées pour une organisation
 */
export function getOrganizationStructuredData() {
  return {
    '@type': 'Organization',
    name: 'OnlyRoll',
    url: defaults.baseUrl,
    logo: `${defaults.baseUrl}/logo.png`,
    sameAs: ['https://twitter.com/onlyroll', 'https://discord.gg/onlyroll'],
  }
}

/**
 * Données structurées pour une page de jeu
 */
export function getGameStructuredData(game: {
  name: string
  description: string
  gameMaster: string
  playerCount: number
  maxPlayers: number
}) {
  return {
    '@type': 'Game',
    name: game.name,
    description: game.description,
    author: {
      '@type': 'Person',
      name: game.gameMaster,
    },
    numberOfPlayers: {
      '@type': 'QuantitativeValue',
      minValue: 1,
      maxValue: game.maxPlayers,
    },
    gameItem: {
      '@type': 'Thing',
      name: 'Dungeons & Dragons 5th Edition',
    },
  }
}
