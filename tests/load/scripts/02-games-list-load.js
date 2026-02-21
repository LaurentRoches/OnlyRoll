/**
 * Scénario 2 — Liste des parties
 *
 * Simule la navigation sur la page /games : liste paginée, recherche, mes parties.
 * Teste GET /api/games (+ filtres) + GET /api/games/my-games.
 *
 * Exécution :
 *   docker run --rm -i --network host \
 *     -v $(pwd)/tests/load:/scripts \
 *     grafana/k6 run /scripts/scripts/02-games-list-load.js \
 *     --env BASE_URL=http://localhost
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend } from 'k6/metrics';
import { login, jsonHeaders, vuUser } from '../helpers/auth.js';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

const gamesListDuration = new Trend('games_list_duration', true);
const gamesSearchDuration = new Trend('games_search_duration', true);
const myGamesDuration = new Trend('my_games_duration', true);

export const options = {
  stages: [
    { duration: '20s', target: 50 }, // ramp-up
    { duration: '2m', target: 50 },  // plateau
    { duration: '20s', target: 0 },  // ramp-down
  ],
  thresholds: {
    // Seuils adaptés au dev Docker (profiler Symfony) — en prod : <200ms
    http_req_failed: ['rate<0.01'],
    'http_req_duration{endpoint:games-list}': ['p(95)<2000'],
    'http_req_duration{endpoint:games-search}': ['p(95)<2000'],
    'http_req_duration{endpoint:my-games}': ['p(95)<2000'],
    games_list_duration: ['p(95)<2000'],
    games_search_duration: ['p(95)<2000'],
    my_games_duration: ['p(95)<2000'],
  },
};

const users = JSON.parse(open('../data/test-users.json'));

// Mots-clés de recherche représentatifs des noms de parties créées en fixture
const searchTerms = ['Dragon', 'Rune', 'Ombre', 'Quête', 'Chronique', 'Tour', 'Forges', 'Brume'];

export function setup() {
  // Login une fois au setup pour vérifier que l'API est accessible
  const user = users[0];
  const loginRes = http.post(
    `${BASE_URL}/api/login`,
    JSON.stringify({ email: user.email, password: user.password }),
    { headers: jsonHeaders() }
  );
  if (loginRes.status !== 200) {
    throw new Error(`Setup failed: login returned ${loginRes.status}`);
  }
}

export default function () {
  const user = vuUser(users, __VU);

  // 1. Login
  const loginRes = http.post(
    `${BASE_URL}/api/login`,
    JSON.stringify({ email: user.email, password: user.password }),
    { headers: jsonHeaders() }
  );

  if (!check(loginRes, { 'login 200': (r) => r.status === 200 })) {
    sleep(1);
    return;
  }

  sleep(0.5);

  // 2. Page 1 de la liste des parties
  const listRes = http.get(`${BASE_URL}/api/games?page=1&limit=12`, {
    tags: { endpoint: 'games-list' },
  });

  gamesListDuration.add(listRes.timings.duration);

  check(listRes, {
    'games list status 200': (r) => r.status === 200,
    'games list a data': (r) => Array.isArray(r.json('data')),
    'games list a meta': (r) => r.json('meta.total') !== undefined,
    'games list durée < 2000ms': (r) => r.timings.duration < 2000,
  });

  sleep(Math.random() * 2 + 1); // 1-3s

  // 3. Page 2
  const list2Res = http.get(`${BASE_URL}/api/games?page=2&limit=12`, {
    tags: { endpoint: 'games-list' },
  });

  gamesListDuration.add(list2Res.timings.duration);

  check(list2Res, {
    'games list p2 status 200': (r) => r.status === 200,
    'games list p2 durée < 2000ms': (r) => r.timings.duration < 2000,
  });

  sleep(Math.random() * 1 + 1); // 1-2s

  // 4. Recherche par mot-clé
  const term = searchTerms[Math.floor(Math.random() * searchTerms.length)];
  const searchRes = http.get(`${BASE_URL}/api/games?search=${encodeURIComponent(term)}`, {
    tags: { endpoint: 'games-search' },
  });

  gamesSearchDuration.add(searchRes.timings.duration);

  check(searchRes, {
    'games search status 200': (r) => r.status === 200,
    'games search a data': (r) => Array.isArray(r.json('data')),
    'games search durée < 2000ms': (r) => r.timings.duration < 2000,
  });

  sleep(Math.random() * 1 + 2); // 2-3s

  // 5. Mes parties
  const myGamesRes = http.get(`${BASE_URL}/api/games/my-games`, {
    tags: { endpoint: 'my-games' },
  });

  myGamesDuration.add(myGamesRes.timings.duration);

  check(myGamesRes, {
    'my-games status 200': (r) => r.status === 200,
    'my-games durée < 2000ms': (r) => r.timings.duration < 2000,
  });

  sleep(Math.random() * 1 + 1); // 1-2s
}
