import { test, expect } from '@playwright/test'

test.describe('Navigation', () => {
  test('should display home page', async ({ page }) => {
    await page.goto('/')

    await expect(page.locator('h1')).toContainText('OnlyRoll')
    await expect(page.locator('#app')).toBeAttached()
  })

  test('should have navigation links on home page', async ({ page }) => {
    await page.goto('/')

    // Check for auth links
    const loginLink = page.locator('a[href*="login"]').first()
    await expect(loginLink).toBeVisible()
  })

  test('should navigate to login page from home', async ({ page }) => {
    await page.goto('/')

    const loginLink = page.locator('a[href*="login"]').first()
    await loginLink.click()

    await expect(page).toHaveURL(/.*login/)
  })

  test('should handle 404 for non-existent routes', async ({ page }) => {
    await page.goto('/non-existent-page')

    // Should show 404 page or redirect
    await page.waitForLoadState('load')

    // Check that app is loaded (either 404 page or redirected to home)
    await expect(page.locator('#app')).toBeAttached()
  })

  test('should have responsive layout', async ({ page }) => {
    await page.goto('/')

    // Check desktop
    await page.setViewportSize({ width: 1280, height: 720 })
    await expect(page.locator('#app')).toBeVisible()

    // Check mobile
    await page.setViewportSize({ width: 375, height: 667 })
    await expect(page.locator('#app')).toBeVisible()
  })
})
