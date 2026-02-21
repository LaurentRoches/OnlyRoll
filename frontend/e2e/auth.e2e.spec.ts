import { test, expect } from '@playwright/test'

/**
 * Test E2E : Inscription et Connexion
 *
 * Scénario : un utilisateur s'inscrit via le formulaire, est redirigé vers la page
 * de succès, puis se connecte avec ses identifiants et accède à la liste des parties.
 *
 * Prérequis : backend opérationnel (comptes auto-vérifiés en env dev).
 */
test.describe('E2E - Inscription et Connexion', () => {
  const ts = Date.now()
  const email = `e2e_auth_${ts}@test.onlyroll.com`
  const pseudo = `Tester${ts}`.slice(0, 20)
  const password = 'E2eTest@1234!'

  test('Inscription : remplit le formulaire et redirige vers la page de succès', async ({
    page,
  }) => {
    await page.goto('/auth/register')
    await expect(page.locator('h2')).toContainText('Inscription')

    await page.locator('#pseudo').fill(pseudo)
    await page.locator('#email').fill(email)
    await page.locator('#password').fill(password)
    await page.locator('#confirmPassword').fill(password)
    await page.locator('#acceptTerms').check()
    await page.locator('button[type="submit"]').click()

    await expect(page).toHaveURL(/\/auth\/register-success/, { timeout: 15_000 })
  })

  test('Connexion : se connecte avec le compte créé et accède aux parties', async ({ page }) => {
    await page.goto('/auth/login')
    await expect(page.locator('h2')).toContainText('Connexion')

    await page.locator('#email').fill(email)
    await page.locator('#password').fill(password)
    await page.locator('button[type="submit"]').click()

    await expect(page).toHaveURL(/\/(games|dashboard)/, { timeout: 15_000 })
  })
})
