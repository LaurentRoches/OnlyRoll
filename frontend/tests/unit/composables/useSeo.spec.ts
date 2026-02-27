import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, nextTick } from 'vue'
import {
  useSeo,
  usePublicSeo,
  usePrivateSeo,
  getOrganizationStructuredData,
  getGameStructuredData,
  type SeoOptions,
} from '@/composables/useSeo'

const createTestComponent = (options: SeoOptions) => {
  return defineComponent({
    setup() {
      return useSeo(options)
    },
    template: '<div></div>',
  })
}

describe('useSeo', () => {
  beforeEach(() => {
    document.head.innerHTML = ''
    document.title = ''
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  describe('title', () => {
    it('should set page title with suffix', () => {
      mount(createTestComponent({
        title: 'Mon titre',
        description: 'Ma description',
      }))

      expect(document.title).toBe('Mon titre | OnlyRoll - VTT D&D 5e')
    })

    it('should use base title when no title provided', () => {
      mount(createTestComponent({
        title: '',
        description: 'Ma description',
      }))

      expect(document.title).toBe('OnlyRoll - VTT D&D 5e')
    })
  })

  describe('meta tags', () => {
    it('should set description meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Ma description test',
      }))

      const descMeta = document.querySelector('meta[name="description"]')
      expect(descMeta?.getAttribute('content')).toBe('Ma description test')
    })

    it('should use default description when none provided', () => {
      mount(createTestComponent({
        title: 'Test',
        description: '',
      }))

      const descMeta = document.querySelector('meta[name="description"]')
      expect(descMeta?.getAttribute('content')).toContain('OnlyRoll est une table de jeu virtuelle')
    })

    it('should set robots meta tag to index for normal pages', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        noindex: false,
      }))

      const robotsMeta = document.querySelector('meta[name="robots"]')
      expect(robotsMeta?.getAttribute('content')).toBe('index, follow')
    })

    it('should set robots meta tag to noindex when specified', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        noindex: true,
      }))

      const robotsMeta = document.querySelector('meta[name="robots"]')
      expect(robotsMeta?.getAttribute('content')).toBe('noindex, nofollow')
    })

    it('should set keywords meta tag when provided', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        keywords: ['jdr', 'dnd', 'rpg'],
      }))

      const keywordsMeta = document.querySelector('meta[name="keywords"]')
      expect(keywordsMeta?.getAttribute('content')).toBe('jdr, dnd, rpg')
    })

    it('should not set keywords meta tag when empty', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        keywords: [],
      }))

      const keywordsMeta = document.querySelector('meta[name="keywords"]')
      expect(keywordsMeta).toBeNull()
    })
  })

  describe('canonical link', () => {
    it('should set canonical link with provided URL', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        canonical: 'https://onlyroll.fr/custom-page',
      }))

      const canonicalLink = document.querySelector('link[rel="canonical"]')
      expect(canonicalLink?.getAttribute('href')).toBe('https://onlyroll.fr/custom-page')
    })

    it('should set canonical link from current pathname when not provided', () => {
      Object.defineProperty(window, 'location', {
        value: { pathname: '/test-page' },
        writable: true,
      })

      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
      }))

      const canonicalLink = document.querySelector('link[rel="canonical"]')
      expect(canonicalLink?.getAttribute('href')).toBe('https://onlyroll.fr/test-page')
    })
  })

  describe('Open Graph meta tags', () => {
    it('should set og:title meta tag', () => {
      mount(createTestComponent({
        title: 'Mon titre OG',
        description: 'Description',
      }))

      const ogTitle = document.querySelector('meta[property="og:title"]')
      expect(ogTitle?.getAttribute('content')).toBe('Mon titre OG')
    })

    it('should use siteName for og:title when no title provided', () => {
      mount(createTestComponent({
        title: '',
        description: 'Description',
      }))

      const ogTitle = document.querySelector('meta[property="og:title"]')
      expect(ogTitle?.getAttribute('content')).toBe('OnlyRoll')
    })

    it('should set og:description meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Ma description OG',
      }))

      const ogDesc = document.querySelector('meta[property="og:description"]')
      expect(ogDesc?.getAttribute('content')).toBe('Ma description OG')
    })

    it('should set og:type meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        ogType: 'article',
      }))

      const ogType = document.querySelector('meta[property="og:type"]')
      expect(ogType?.getAttribute('content')).toBe('article')
    })

    it('should use website as default og:type', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
      }))

      const ogType = document.querySelector('meta[property="og:type"]')
      expect(ogType?.getAttribute('content')).toBe('website')
    })

    it('should set og:url meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        canonical: 'https://onlyroll.fr/my-page',
      }))

      const ogUrl = document.querySelector('meta[property="og:url"]')
      expect(ogUrl?.getAttribute('content')).toBe('https://onlyroll.fr/my-page')
    })

    it('should set og:image meta tag with provided URL', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        ogImage: 'https://example.com/image.png',
      }))

      const ogImage = document.querySelector('meta[property="og:image"]')
      expect(ogImage?.getAttribute('content')).toBe('https://example.com/image.png')
    })

    it('should use default og:image when not provided', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
      }))

      const ogImage = document.querySelector('meta[property="og:image"]')
      expect(ogImage?.getAttribute('content')).toBe('https://onlyroll.fr/og-image.png')
    })

    it('should set og:site_name meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
      }))

      const ogSiteName = document.querySelector('meta[property="og:site_name"]')
      expect(ogSiteName?.getAttribute('content')).toBe('OnlyRoll')
    })

    it('should set og:locale meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
      }))

      const ogLocale = document.querySelector('meta[property="og:locale"]')
      expect(ogLocale?.getAttribute('content')).toBe('fr_FR')
    })
  })

  describe('Twitter Card meta tags', () => {
    it('should set twitter:card meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
      }))

      const twitterCard = document.querySelector('meta[name="twitter:card"]')
      expect(twitterCard?.getAttribute('content')).toBe('summary_large_image')
    })

    it('should set twitter:site meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
      }))

      const twitterSite = document.querySelector('meta[name="twitter:site"]')
      expect(twitterSite?.getAttribute('content')).toBe('@onlyroll')
    })

    it('should set twitter:title meta tag', () => {
      mount(createTestComponent({
        title: 'Mon titre Twitter',
        description: 'Description',
      }))

      const twitterTitle = document.querySelector('meta[name="twitter:title"]')
      expect(twitterTitle?.getAttribute('content')).toBe('Mon titre Twitter')
    })

    it('should set twitter:description meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Ma description Twitter',
      }))

      const twitterDesc = document.querySelector('meta[name="twitter:description"]')
      expect(twitterDesc?.getAttribute('content')).toBe('Ma description Twitter')
    })

    it('should set twitter:image meta tag', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        ogImage: 'https://example.com/twitter-image.png',
      }))

      const twitterImage = document.querySelector('meta[name="twitter:image"]')
      expect(twitterImage?.getAttribute('content')).toBe('https://example.com/twitter-image.png')
    })
  })

  describe('structured data', () => {
    it('should add JSON-LD script when structuredData is provided', () => {
      const structuredData = {
        '@type': 'WebPage',
        name: 'Test Page',
      }

      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        structuredData,
      }))

      const script = document.querySelector('script[type="application/ld+json"]')
      expect(script).not.toBeNull()

      const content = JSON.parse(script?.textContent || '{}')
      expect(content['@context']).toBe('https://schema.org')
      expect(content['@type']).toBe('WebPage')
      expect(content.name).toBe('Test Page')
    })

    it('should remove old JSON-LD script when adding new one', () => {
      const oldScript = document.createElement('script')
      oldScript.type = 'application/ld+json'
      oldScript.textContent = JSON.stringify({ old: 'data' })
      document.head.appendChild(oldScript)

      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        structuredData: { '@type': 'WebPage', name: 'New' },
      }))

      const scripts = document.querySelectorAll('script[type="application/ld+json"]')
      expect(scripts.length).toBe(1)

      const content = JSON.parse(scripts[0]?.textContent || '{}')
      expect(content.name).toBe('New')
    })

    it('should not add JSON-LD script when structuredData is not provided', () => {
      mount(createTestComponent({
        title: 'Test',
        description: 'Description',
      }))

      const script = document.querySelector('script[type="application/ld+json"]')
      expect(script).toBeNull()
    })
  })

  describe('updateTitle', () => {
    it('should update title dynamically', async () => {
      const wrapper = mount(createTestComponent({
        title: 'Initial',
        description: 'Description',
      }))

      const { updateTitle } = wrapper.vm

      updateTitle('Updated Title')
      await nextTick()

      expect(document.title).toBe('Updated Title | OnlyRoll - VTT D&D 5e')

      const ogTitle = document.querySelector('meta[property="og:title"]')
      expect(ogTitle?.getAttribute('content')).toBe('Updated Title')

      const twitterTitle = document.querySelector('meta[name="twitter:title"]')
      expect(twitterTitle?.getAttribute('content')).toBe('Updated Title')
    })

    it('should use siteName when updating with empty title', async () => {
      const wrapper = mount(createTestComponent({
        title: 'Initial',
        description: 'Description',
      }))

      const { updateTitle } = wrapper.vm

      updateTitle('')
      await nextTick()

      expect(document.title).toBe('OnlyRoll - VTT D&D 5e')

      const ogTitle = document.querySelector('meta[property="og:title"]')
      expect(ogTitle?.getAttribute('content')).toBe('OnlyRoll')
    })
  })

  describe('updateDescription', () => {
    it('should update description dynamically', async () => {
      const wrapper = mount(createTestComponent({
        title: 'Test',
        description: 'Initial description',
      }))

      const { updateDescription } = wrapper.vm

      updateDescription('Updated description')
      await nextTick()

      const descMeta = document.querySelector('meta[name="description"]')
      expect(descMeta?.getAttribute('content')).toBe('Updated description')

      const ogDesc = document.querySelector('meta[property="og:description"]')
      expect(ogDesc?.getAttribute('content')).toBe('Updated description')

      const twitterDesc = document.querySelector('meta[name="twitter:description"]')
      expect(twitterDesc?.getAttribute('content')).toBe('Updated description')
    })
  })

  describe('cleanup on unmount', () => {
    it('should remove JSON-LD script on unmount', async () => {
      const wrapper = mount(createTestComponent({
        title: 'Test',
        description: 'Description',
        structuredData: { '@type': 'WebPage', name: 'Test' },
      }))

      let script = document.querySelector('script[type="application/ld+json"]')
      expect(script).not.toBeNull()

      wrapper.unmount()
      await nextTick()

      script = document.querySelector('script[type="application/ld+json"]')
      expect(script).toBeNull()
    })
  })

  describe('meta tag updates', () => {
    it('should update existing meta tags instead of creating duplicates', () => {
      const wrapper1 = mount(createTestComponent({
        title: 'First',
        description: 'First description',
      }))

      mount(createTestComponent({
        title: 'Second',
        description: 'Second description',
      }))

      const descMetas = document.querySelectorAll('meta[name="description"]')
      expect(descMetas.length).toBe(1)
      expect(descMetas[0]?.getAttribute('content')).toBe('Second description')

      wrapper1.unmount()
    })
  })
})

describe('usePublicSeo', () => {
  beforeEach(() => {
    document.head.innerHTML = ''
    document.title = ''
  })

  it('should set noindex to false', () => {
    const TestComponent = defineComponent({
      setup() {
        return usePublicSeo({
          title: 'Public Page',
          description: 'Public description',
        })
      },
      template: '<div></div>',
    })

    mount(TestComponent)

    const robotsMeta = document.querySelector('meta[name="robots"]')
    expect(robotsMeta?.getAttribute('content')).toBe('index, follow')
  })
})

describe('usePrivateSeo', () => {
  beforeEach(() => {
    document.head.innerHTML = ''
    document.title = ''
  })

  it('should set noindex to true', () => {
    const TestComponent = defineComponent({
      setup() {
        return usePrivateSeo({
          title: 'Private Page',
          description: 'Private description',
        })
      },
      template: '<div></div>',
    })

    mount(TestComponent)

    const robotsMeta = document.querySelector('meta[name="robots"]')
    expect(robotsMeta?.getAttribute('content')).toBe('noindex, nofollow')
  })
})

describe('getOrganizationStructuredData', () => {
  it('should return organization schema data', () => {
    const data = getOrganizationStructuredData()

    expect(data['@type']).toBe('Organization')
    expect(data.name).toBe('OnlyRoll')
    expect(data.url).toBe('https://onlyroll.fr')
    expect(data.logo).toBe('https://onlyroll.fr/logo.png')
    expect(data.sameAs).toContain('https://twitter.com/onlyroll')
    expect(data.sameAs).toContain('https://discord.gg/onlyroll')
  })
})

describe('getGameStructuredData', () => {
  it('should return game schema data', () => {
    const game = {
      name: 'Ma Campagne',
      description: 'Une aventure épique',
      gameMaster: 'DungeonMaster',
      playerCount: 3,
      maxPlayers: 5,
    }

    const data = getGameStructuredData(game)

    expect(data['@type']).toBe('Game')
    expect(data.name).toBe('Ma Campagne')
    expect(data.description).toBe('Une aventure épique')
    expect(data.author).toEqual({
      '@type': 'Person',
      name: 'DungeonMaster',
    })
    expect(data.numberOfPlayers).toEqual({
      '@type': 'QuantitativeValue',
      minValue: 1,
      maxValue: 5,
    })
    expect(data.gameItem).toEqual({
      '@type': 'Thing',
      name: 'Dungeons & Dragons 5th Edition',
    })
  })
})
