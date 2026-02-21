import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

/**
 * Connecte un utilisateur et retourne le jar de cookies contenant le jwt_token.
 *
 * Le JWT est stocké dans un cookie httpOnly `jwt_token`. K6 l'envoie
 * automatiquement sur toutes les requêtes suivantes si on réutilise le même jar.
 *
 * @param {string} email
 * @param {string} password
 * @returns {{ jar: CookieJar, userId: number, pseudo: string } | null}
 */
export function login(email, password) {
  const jar = http.cookieJar();

  const res = http.post(
    `${BASE_URL}/api/login`,
    JSON.stringify({ email, password }),
    {
      headers: { 'Content-Type': 'application/json' },
      jar,
    }
  );

  // Le subscriber renvoie { success: true, message: "...", user: null }
  // Le token JWT est dans le cookie httpOnly jwt_token
  const ok = check(res, {
    'login status 200': (r) => r.status === 200,
    'login jwt cookie set': (r) => r.cookies['jwt_token'] !== undefined,
    'login success true': (r) => r.json('success') === true,
  });

  if (!ok) {
    return null;
  }

  const body = res.json();
  return {
    jar,
    userId: body.user?.id,
    pseudo: body.user?.pseudo,
  };
}

/**
 * Retourne les headers JSON de base.
 * Le cookie jwt_token est géré par le jar, pas par les headers.
 */
export function jsonHeaders() {
  return { 'Content-Type': 'application/json' };
}

/**
 * Tire un utilisateur aléatoire depuis la liste de test.
 *
 * @param {Array<{email: string, password: string}>} users
 * @returns {{email: string, password: string}}
 */
export function randomUser(users) {
  return users[Math.floor(Math.random() * users.length)];
}

/**
 * Tire un utilisateur dédié à un VU (répartition déterministe).
 * Évite les collisions sur des ressources partagées (ex. join de partie).
 *
 * @param {Array<{email: string, password: string}>} users
 * @param {number} vuId  (__VU dans K6)
 * @returns {{email: string, password: string}}
 */
export function vuUser(users, vuId) {
  return users[(vuId - 1) % users.length];
}
