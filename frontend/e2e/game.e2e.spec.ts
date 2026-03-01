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
      const messages = data.items ?? []
      if (messages.length > 0) {
        const msg = messages[0]
        // Extract token from the email body (HTML part contains the verification URL)
        const bodyParts = msg.MIME?.Parts ?? []
        let body = ''
        for (const part of bodyParts) {
          const contentType = part.Headers?.['Content-Type']?.[0] ?? ''
          if (contentType.includes('text/html') || contentType.includes('text/plain')) {
            body = part.Body ?? ''
            break
          }
        }
        // Fallback to raw body
        if (!body) {
          body = msg.Content?.Body ?? ''
        }

        // Remove quoted-printable soft line breaks before matching
        const decodedBody = body.replace(/=\r?\n/g, '')
        const match = decodedBody.match(/token=([a-f0-9]+)/)
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

  // Verify email via mailhog before logging in
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

    // Naviguer vers la liste des parties (login redirige vers /dashboard)
    await page.goto('/games')
    await expect(page).toHaveURL(/\/games/, { timeout: 10_000 })

    // Intercepter la réponse de création pour capturer l'ID de la partie
    const createPromise = page.waitForResponse(
      (res) =>
        res.url().includes('/api/games') &&
        res.request().method() === 'POST' &&
        res.status() === 201
    )

    // Ouvrir le modal de création
    await page.getByRole('button', { name: 'Nouvelle' }).click()
    await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5_000 })

    // Remplir le nom de la partie
    await page
      .getByRole('dialog')
      .getByPlaceholder(/Dragons Oubliés/i)
      .fill(GAME_NAME)

    // Soumettre le formulaire
    await page.getByRole('dialog').getByRole('button', { name: 'Créer la partie' }).click()

    // Récupérer l'ID depuis la réponse API
    const createRes = await createPromise
    const gameData = await createRes.json()
    gameId = gameData.id
    expect(gameId).toBeTruthy()

    // Vérifier la redirection vers la vue de jeu
    await expect(page).toHaveURL(/\/games\/\d+\/play/, { timeout: 15_000 })
  })

  test('Joueur : rejoint la partie et poste un message dans le chat', async ({ page, request }) => {
    await registerAndLogin(page, request, PLAYER)

    // Naviguer vers la liste publique
    await page.goto('/games')

    // Trouver la carte de la partie par son nom
    const gameCard = page.locator('article').filter({ hasText: GAME_NAME })
    await expect(gameCard).toBeVisible({ timeout: 10_000 })

    // Cliquer sur "Jouer" pour ouvrir le modal de participation
    await gameCard.getByRole('button', { name: 'Jouer' }).click()
    await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5_000 })

    // Rejoindre la partie (publique → pas de mot de passe requis)
    await page.getByRole('dialog').getByRole('button', { name: 'Rejoindre' }).click()

    // Attendre la fermeture du modal (succès)
    await expect(page.getByRole('dialog')).not.toBeVisible({ timeout: 10_000 })

    // Naviguer vers la vue de jeu (pas d'auto-navigation après join dans GameListView)
    await page.goto(`/games/${gameId}/play`)

    // Attendre que la zone de saisie du chat soit disponible
    const chatInput = page.locator('textarea[placeholder*="Enter text"]')
    await expect(chatInput).toBeVisible({ timeout: 15_000 })

    // Poster un message
    await chatInput.fill(CHAT_MSG)
    await chatInput.press('Enter')

    // Vérifier que le message apparaît dans le chat
    await expect(page.locator('p.text-secondary-100').filter({ hasText: CHAT_MSG })).toBeVisible({
      timeout: 15_000,
    })
  })
})
