import { test, expect, type APIRequestContext } from '@playwright/test'

/**
 * Test E2E : Inscription et Connexion
 *
 * Scénario : un utilisateur s'inscrit via le formulaire, est redirigé vers la page
 * de succès, son email est vérifié via l'API mailhog, puis il se connecte et accède
 * à la liste des parties.
 */

const MAILHOG_API = 'http://localhost:8025'
const BACKEND_API = 'http://localhost/api'

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

test.describe.serial('E2E - Inscription et Connexion', () => {
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

  test('Connexion : se connecte avec le compte créé et accède aux parties', async ({
    page,
    request,
  }) => {
    await verifyEmailViaMailhog(request, email)

    await page.goto('/auth/login')
    await expect(page.locator('h2')).toContainText('Connexion')

    await page.locator('#email').fill(email)
    await page.locator('#password').fill(password)
    await page.locator('button[type="submit"]').click()

    await expect(page).toHaveURL(/\/(games|dashboard)/, { timeout: 15_000 })
  })
})
