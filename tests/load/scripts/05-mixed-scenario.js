/**
 * Scénario 5 — Trafic mixte réaliste
 *
 * Simule un usage représentatif de la production avec 3 profils d'utilisateurs :
 *
 * | Groupe          | VUs | Comportement                             |
 * |-----------------|-----|------------------------------------------|
 * | Visiteurs       |  30 | Login + navigation liste des parties     |
 * | Joueurs actifs  |  20 | Login + créer/rejoindre partie + chat    |
 * | Lecteurs        |  10 | Login + consultation détails d'une partie|
 *
 * Ce scénario est le plus représentatif de la production.
 * Lancer après avoir validé les scénarios 1 à 4 individuellement.
 *
 * Exécution :
 *   docker run --rm -i --network host \
 *     -v $(pwd)/tests/load:/scripts \
 *     grafana/k6 run /scripts/scripts/05-mixed-scenario.js \
 *     --env BASE_URL=http://localhost
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';
import { jsonHeaders, vuUser } from '../helpers/auth.js';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

const apiDuration = new Trend('mixed_api_duration', true);
const errorRate = new Rate('mixed_error_rate');

export const options = {
  scenarios: {
    // Groupe 1 : visiteurs — naviguent sur la liste des parties
    visitors: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 30 },
        { duration: '3m', target: 30 },
        { duration: '30s', target: 0 },
      ],
      exec: 'visitorScenario',
    },
    // Groupe 2 : joueurs actifs — créent/rejoignent des parties et chattent
    active_players: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 20 },
        { duration: '3m', target: 20 },
        { duration: '30s', target: 0 },
      ],
      exec: 'activePlayerScenario',
    },
    // Groupe 3 : lecteurs — consultent les détails d'une partie sans interagir
    readers: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 10 },
        { duration: '3m', target: 10 },
        { duration: '30s', target: 0 },
      ],
      exec: 'readerScenario',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<300'],
    mixed_api_duration: ['p(95)<300'],
    mixed_error_rate: ['rate<0.01'],
  },
};

const users = JSON.parse(open('../data/test-users.json'));

// Mots-clés de recherche
const searchTerms = ['Dragon', 'Rune', 'Ombre', 'Tour', 'Forges'];

// Messages de chat
const chatMessages = [
  'Je prépare mon attaque.',
  'Attention au piège !',
  'Mon personnage avance prudemment.',
  'Je lance un sort de soin.',
  'Quelqu\'un surveille l\'arrière ?',
];

function loginUser(vuId) {
  const user = vuUser(users, vuId);
  const loginRes = http.post(
    `${BASE_URL}/api/login`,
    JSON.stringify({ email: user.email, password: user.password }),
    { headers: jsonHeaders() }
  );
  return loginRes.status === 200 ? loginRes : null;
}

// ─── Scénario Visiteur ────────────────────────────────────────────────────────

export function visitorScenario() {
  const loginRes = loginUser(__VU);
  if (!loginRes) { sleep(1); return; }

  // Liste des parties
  const listRes = http.get(`${BASE_URL}/api/games?page=1&limit=12`);
  apiDuration.add(listRes.timings.duration);
  errorRate.add(listRes.status !== 200);
  check(listRes, { 'visitor: list 200': (r) => r.status === 200 });

  sleep(Math.random() * 2 + 1);

  // Recherche
  const term = searchTerms[Math.floor(Math.random() * searchTerms.length)];
  const searchRes = http.get(`${BASE_URL}/api/games?search=${encodeURIComponent(term)}`);
  apiDuration.add(searchRes.timings.duration);
  errorRate.add(searchRes.status !== 200);
  check(searchRes, { 'visitor: search 200': (r) => r.status === 200 });

  sleep(Math.random() * 2 + 2);

  // Mes parties
  const myRes = http.get(`${BASE_URL}/api/games/my-games`);
  apiDuration.add(myRes.timings.duration);
  errorRate.add(myRes.status !== 200);

  sleep(Math.random() * 1 + 1);
}

// ─── Scénario Joueur actif ────────────────────────────────────────────────────

export function activePlayerScenario() {
  const loginRes = loginUser(__VU + 50); // décalage pour éviter collision avec visiteurs
  if (!loginRes) { sleep(1); return; }

  // Créer une partie
  const gameRes = http.post(
    `${BASE_URL}/api/games`,
    JSON.stringify({
      name: `Partie Mixte VU-${__VU}`,
      maxPlayers: 6,
      isPublic: true,
    }),
    { headers: jsonHeaders() }
  );

  apiDuration.add(gameRes.timings.duration);

  if (!check(gameRes, { 'active: create game 201': (r) => r.status === 201 })) {
    sleep(1);
    return;
  }

  const gameId = gameRes.json('id');

  sleep(0.5);

  // Envoyer 2-3 messages
  const msgCount = Math.floor(Math.random() * 2) + 2;
  for (let i = 0; i < msgCount; i++) {
    const content = chatMessages[Math.floor(Math.random() * chatMessages.length)];
    const msgRes = http.post(
      `${BASE_URL}/api/games/${gameId}/chat/messages`,
      JSON.stringify({ type: 'chat', content }),
      { headers: jsonHeaders() }
    );

    apiDuration.add(msgRes.timings.duration);
    errorRate.add(msgRes.status !== 201);
    check(msgRes, { 'active: message 201': (r) => r.status === 201 });

    sleep(Math.random() * 2 + 3);
  }
}

// ─── Scénario Lecteur ─────────────────────────────────────────────────────────

export function readerScenario() {
  const loginRes = loginUser(__VU + 80); // décalage pour éviter collision
  if (!loginRes) { sleep(1); return; }

  // Liste des parties publiques
  const listRes = http.get(`${BASE_URL}/api/games?page=1&limit=12&status=in_progress`);
  apiDuration.add(listRes.timings.duration);
  errorRate.add(listRes.status !== 200);

  if (!check(listRes, { 'reader: list 200': (r) => r.status === 200 })) {
    sleep(2);
    return;
  }

  const games = listRes.json('data') || [];

  // Consulter le détail d'une partie (si disponible)
  if (games.length > 0) {
    const gameId = games[Math.floor(Math.random() * games.length)].id;

    const detailRes = http.get(`${BASE_URL}/api/games/${gameId}`);
    apiDuration.add(detailRes.timings.duration);
    // 403 est normal si l'utilisateur n'est pas membre → pas une erreur K6
    const isOk = detailRes.status === 200 || detailRes.status === 403;
    errorRate.add(!isOk);
    check(detailRes, {
      'reader: detail ok': (r) => r.status === 200 || r.status === 403,
    });
  }

  sleep(Math.random() * 3 + 3);
}
