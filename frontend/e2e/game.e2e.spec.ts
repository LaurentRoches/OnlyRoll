import { test, expect, type Page } from '@playwright/test'

/**
 * Test E2E : Création d'une partie, la rejoindre et poster un message
 *
 * Scénario en 2 tests sérialisés (test.describe.serial) :
 *  1. Le MJ s'inscrit, se connecte, crée une partie publique → capturé via waitForResponse
 *  2. Un joueur s'inscrit, se connecte, rejoint la partie depuis la liste publique,
 *     navigue vers la vue de jeu et poste un message dans le chat.
 *
 * Prérequis : backend + frontend opérationnels (Docker Compose ou stack locale).
 */

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

async function registerAndLogin(page: Page, user: typeof GM): Promise<void> {
  await page.goto('/auth/register')
  await page.locator('#pseudo').fill(user.pseudo)
  await page.locator('#email').fill(user.email)
  await page.locator('#password').fill(user.password)
  await page.locator('#confirmPassword').fill(user.password)
  await page.locator('#acceptTerms').check()
  await page.locator('button[type="submit"]').click()
  await expect(page).toHaveURL(/\/auth\/register-success/, { timeout: 15_000 })

  await page.goto('/auth/login')
  await page.locator('#email').fill(user.email)
  await page.locator('#password').fill(user.password)
  await page.locator('button[type="submit"]').click()
  await expect(page).toHaveURL(/\/(games|dashboard)/, { timeout: 15_000 })
}

test.describe.serial('E2E - Création, rejoindre une partie et poster un message', () => {
  test('MJ : crée une partie publique', async ({ page }) => {
    await registerAndLogin(page, GM)

    // Naviguer vers la liste des parties (login redirige vers /dashboard)
    await page.goto('/games')
    await expect(page).toHaveURL(/\/games/, { timeout: 10_000 })

    // Intercepter la réponse de création pour capturer l'ID de la partie
    const createPromise = page.waitForResponse(
      (res) =>
        res.url().includes('/api/games') &&
        res.request().method() === 'POST' &&
        res.status() === 201,
    )

    // Ouvrir le modal de création
    await page.getByRole('button', { name: 'Nouvelle' }).click()
    await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5_000 })

    // Remplir le nom de la partie
    await page.getByRole('dialog').getByPlaceholder(/Dragons Oubliés/i).fill(GAME_NAME)

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

  test('Joueur : rejoint la partie et poste un message dans le chat', async ({ page }) => {
    await registerAndLogin(page, PLAYER)

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
    await expect(
      page.locator('p.text-secondary-100').filter({ hasText: CHAT_MSG }),
    ).toBeVisible({ timeout: 15_000 })
  })
})
