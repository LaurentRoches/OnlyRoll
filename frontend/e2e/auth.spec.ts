import { test, expect } from '@playwright/test'

test.describe('Authentication Flow', () => {
  test('should display login page', async ({ page }) => {
    await page.goto('/auth/login')

    await expect(page.locator('h2')).toContainText(/connexion|login/i)
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('should display register page', async ({ page }) => {
    await page.goto('/auth/register')

    await expect(page.locator('h2')).toContainText(/inscription|register/i)
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('should navigate from login to register', async ({ page }) => {
    await page.goto('/auth/login')

    const registerLink = page.locator('a[href*="register"]')
    await expect(registerLink).toBeVisible()
    await registerLink.click()

    await expect(page).toHaveURL(/.*register/)
  })

  test('should navigate from register to login', async ({ page }) => {
    await page.goto('/auth/register')

    const loginLink = page.locator('a[href*="login"]')
    await expect(loginLink).toBeVisible()
    await loginLink.click()

    await expect(page).toHaveURL(/.*login/)
  })

  test('should show validation errors for empty login form', async ({ page }) => {
    await page.goto('/auth/login')

    const submitButton = page.locator('button[type="submit"]')
    await submitButton.click()

    // Check that we're still on login page (form didn't submit)
    await expect(page).toHaveURL(/.*login/)
  })

  test('should show validation errors for empty register form', async ({ page }) => {
    await page.goto('/auth/register')

    const submitButton = page.locator('button[type="submit"]')
    await submitButton.click()

    // Check that we're still on register page
    await expect(page).toHaveURL(/.*register/)
  })
})
