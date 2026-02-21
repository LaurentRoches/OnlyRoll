/**
 * Scénario 1 — Authentification
 *
 * Simule la connexion simultanée de joueurs en début de soirée JDR.
 * Teste POST /api/login + GET /api/me sous charge croissante.
 *
 * Exécution :
 *   docker run --rm -i --network host \
 *     -v $(pwd)/tests/load:/scripts \
 *     grafana/k6 run /scripts/scripts/01-auth-load.js \
 *     --env BASE_URL=http://localhost
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';
import { login, jsonHeaders, vuUser } from '../helpers/auth.js';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

// Métriques personnalisées par endpoint
const loginDuration = new Trend('login_duration', true);
const meDuration = new Trend('me_duration', true);
const loginErrors = new Rate('login_errors');

export const options = {
  stages: [
    { duration: '30s', target: 100 }, // ramp-up
    { duration: '2m', target: 100 },  // plateau
    { duration: '30s', target: 0 },   // ramp-down
  ],
  thresholds: {
    // Métriques globales
    http_req_failed: ['rate<0.01'],
    // Métriques par endpoint (via tags)
    // Seuils adaptés : dev Docker (bcrypt + profiler) ~1-2s, prod <200-500ms
    // Pour tester en prod : BASE_URL=https://onlyroll.cloud et abaisser les seuils
    'http_req_duration{endpoint:login}': ['p(95)<3000'],
    'http_req_duration{endpoint:me}': ['p(95)<2000'],
    // Métriques personnalisées
    login_duration: ['p(95)<3000'],
    me_duration: ['p(95)<2000'],
    login_errors: ['rate<0.01'],
  },
};

const users = JSON.parse(open('../data/test-users.json'));

export default function () {
  // Chaque VU utilise un utilisateur dédié pour éviter les collisions de session
  const user = vuUser(users, __VU);

  // 1. Login
  const loginRes = http.post(
    `${BASE_URL}/api/login`,
    JSON.stringify({ email: user.email, password: user.password }),
    {
      headers: jsonHeaders(),
      tags: { endpoint: 'login' },
    }
  );

  loginDuration.add(loginRes.timings.duration);

  // Le subscriber renvoie { success: true, message: "...", user: null }
  // Le token JWT est dans le cookie httpOnly jwt_token, pas dans le body
  const loginOk = check(loginRes, {
    'login status 200': (r) => r.status === 200,
    'login success true': (r) => r.json('success') === true,
    'login cookie jwt_token set': (r) => r.cookies['jwt_token'] !== undefined,
  });

  // Seuil de durée : indicatif seulement (bcrypt ~1s en dev, plus rapide en prod)
  check(loginRes, { 'login durée < 2000ms': (r) => r.timings.duration < 2000 });

  loginErrors.add(!loginOk);

  if (!loginOk) {
    sleep(1);
    return;
  }

  sleep(Math.random() * 1 + 1); // 1-2s

  // 2. Profil utilisateur (avec le cookie jwt_token positionné par le login)
  const meRes = http.get(`${BASE_URL}/api/me`, {
    tags: { endpoint: 'me' },
  });

  meDuration.add(meRes.timings.duration);

  check(meRes, {
    'me status 200': (r) => r.status === 200,
    'me retourne email': (r) => r.json('email') !== null,
    'me durée < 2000ms': (r) => r.timings.duration < 2000,
  });

  sleep(Math.random() * 1 + 2); // 2-3s
}
