import { test, expect, type Page, type APIRequestContext } from '@playwright/test'

/**
 * Test E2E : Création d'une partie, la rejoindre et poster un message
 *
 * Scénario en 2 tests sérialisés (test.describe.serial) :
 *  1. Le MJ s'inscrit, vérifie son email via mailhog, se connecte, crée une partie publique → capturé via waitForResponse
 *  2. Un joueur s'inscrit, vérifie son email via mailhog, se connecte, rejoint la partie depuis la liste publique,
 *     navigue vers la vue de jeu et poste un message dans le chat.
 *
 * Prérequis : backend + frontend opérationnels (Docker Compose ou stack locale).
 */

const MAILHOG_API = 'http://localhost:8025'
const BACKEND_API = 'http://localhost/api'

const ts = Date.now()

const GM = {
  email: `gm_${ts}@test.onlyroll.com`,
  pseudo: `MJ${ts}`.slice(0, 15),
  password: 'GmTest@12345!',
}

const PLAYER = {
  email: `player_${ts}@test.onlyroll.com`,
  pseudo: `Player${ts}`.slice(0, 15),
  password: 'PlTest@12345!',
}

const GAME_NAME = `Aventure_${ts}`
const CHAT_MSG = `Bonjour monde ${ts}`

let gameId: number

/**
 * Vérifie l'email d'un utilisateur en récupérant le token depuis mailhog
 * et en appelant l'API de vérification du backend.
 */
async function verifyEmailViaMailhog(request: APIRequestContext, userEmail: string): Promise<void> {
  for (let attempt = 0; attempt < 15; attempt++) {
    const response = await request.get(
      `${MAILHOG_API}/api/v2/search?kind=to&query=${encodeURIComponent(userEmail)}&limit=5`
    )
    if (response.ok()) {
      const data = await response.json()
      interface MailhogMimePart {
          MIME?: { Parts?: MailhogMimePart[] }
          Headers?: Record<string, string[]>
          Body?: string
        }
        interface MailhogMessage {
          MIME?: { Parts?: MailhogMimePart[] }
          Content?: { Body?: string }
        }
        const messages = data.items ?? []
      if (messages.length > 0) {
        const msg = messages[messages.length - 1] as MailhogMessage

        const topParts: MailhogMimePart[] = msg.MIME?.Parts ?? []
        const allParts: MailhogMimePart[] = [...topParts, ...topParts.flatMap((p: MailhogMimePart) => p.MIME?.Parts ?? [])]

        let body = ''
        for (const part of allParts) {
          const ct: string = part.Headers?.['Content-Type']?.[0] ?? ''
          if (ct.includes('text/plain') || ct.includes('text/html')) {
            body = part.Body ?? ''
            break
          }
        }
        if (!body) {
          body = msg.Content?.Body ?? ''
        }

        const decodedBody = body.replace(/=\r?\n/g, '').replace(/=3D/g, '=')

        const match = decodedBody.match(/[?&]token=([a-f0-9]{100})/)
        if (match) {
          const verifyResponse = await request.post(`${BACKEND_API}/auth/verify-email`, {
            data: { token: match[1] },
          })
          if (!verifyResponse.ok()) {
            const errorText = await verifyResponse.text()
            throw new Error(
              `Email verification API failed (${verifyResponse.status()}): ${errorText}`
            )
          }
          return
        }
      }
    }
    await new Promise((r) => setTimeout(r, 1000))
  }
  throw new Error(`No verification email found for ${userEmail} after 15 attempts`)
}

async function registerAndLogin(
  page: Page,
  request: APIRequestContext,
  user: typeof GM
): Promise<void> {
  await page.goto('/auth/register')
  await page.locator('#pseudo').fill(user.pseudo)
  await page.locator('#email').fill(user.email)
  await page.locator('#password').fill(user.password)
  await page.locator('#confirmPassword').fill(user.password)
  await page.locator('#acceptTerms').check()
  await page.locator('button[type="submit"]').click()
  await expect(page).toHaveURL(/\/auth\/register-success/, { timeout: 15_000 })

  await verifyEmailViaMailhog(request, user.email)

  await page.goto('/auth/login')
  await page.locator('#email').fill(user.email)
  await page.locator('#password').fill(user.password)
  await page.locator('button[type="submit"]').click()
  await expect(page).toHaveURL(/\/(games|dashboard)/, { timeout: 15_000 })
}

test.describe.serial('E2E - Création, rejoindre une partie et poster un message', () => {
  test('MJ : crée une partie publique', async ({ page, request }) => {
    await registerAndLogin(page, request, GM)

    await page.goto('/games')
    await expect(page).toHaveURL(/\/games/, { timeout: 10_000 })

    const createPromise = page.waitForResponse(
      (res) =>
        res.url().includes('/api/games') &&
        res.request().method() === 'POST' &&
        res.status() === 201
    )

    await page.getByRole('button', { name: 'Nouvelle' }).click()
    await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5_000 })

    await page
      .getByRole('dialog')
      .getByPlaceholder(/Dragons Oubliés/i)
      .fill(GAME_NAME)

    await page.getByRole('dialog').getByRole('button', { name: 'Créer la partie' }).click()

    const createRes = await createPromise
    const gameData = await createRes.json()
    gameId = gameData.id
    expect(gameId).toBeTruthy()

    await expect(page).toHaveURL(/\/games\/\d+\/play/, { timeout: 15_000 })
  })

  test('Joueur : rejoint la partie et poste un message dans le chat', async ({ page, request }) => {
    await registerAndLogin(page, request, PLAYER)

    await page.goto('/games')

    const gameCard = page.locator('article').filter({ hasText: GAME_NAME })
    await expect(gameCard).toBeVisible({ timeout: 10_000 })

    await gameCard.getByRole('button', { name: 'Rejoindre' }).click()
    await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5_000 })

    await page.getByRole('dialog').getByRole('button', { name: 'Rejoindre' }).click()

    await expect(page.getByRole('dialog')).not.toBeVisible({ timeout: 10_000 })

    await page.goto(`/games/${gameId}/play`)

    const chatInput = page.locator('textarea[placeholder*="Enter text"]')
    await expect(chatInput).toBeVisible({ timeout: 15_000 })

    await chatInput.fill(CHAT_MSG)
    await chatInput.press('Enter')

    await expect(page.locator('p.text-secondary-100').filter({ hasText: CHAT_MSG })).toBeVisible({
      timeout: 15_000,
    })
  })
})
