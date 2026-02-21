/**
 * Scénario 3 — Chat en temps réel
 *
 * Simule 30 joueurs actifs dans 5 tables de 6 joueurs : chacun crée une partie,
 * obtient son token Mercure (SSE) et envoie des messages en continu.
 *
 * Chaque VU :
 * 1. Login
 * 2. Crée une partie (ou utilise un gameId passé en env)
 * 3. Récupère le token Mercure → ouvre une connexion SSE (si K6 >= 0.50)
 * 4. Envoie des messages POST en boucle
 *
 * Mercure / SSE :
 *   K6 >= 0.50 : décommentez le bloc SSE ci-dessous (k6/experimental/streams).
 *   K6 < 0.50  : seul le POST est testé (option B du plan).
 *
 * Exécution :
 *   docker run --rm -i --network host \
 *     -v $(pwd)/tests/load:/scripts \
 *     grafana/k6 run /scripts/scripts/03-chat-load.js \
 *     --env BASE_URL=http://localhost \
 *     --env MERCURE_URL=http://localhost:3000
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';
import { jsonHeaders, vuUser } from '../helpers/auth.js';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

const sendMessageDuration = new Trend('chat_send_duration', true);
const mercureTokenDuration = new Trend('mercure_token_duration', true);
const messageErrors = new Rate('chat_message_errors');

export const options = {
  stages: [
    { duration: '15s', target: 30 }, // ramp-up (simule les joueurs qui rejoignent)
    { duration: '3m', target: 30 },  // plateau (5 tables actives)
    { duration: '15s', target: 0 },  // ramp-down
  ],
  thresholds: {
    // Seuils adaptés au dev Docker — en prod : <200ms
    http_req_failed: ['rate<0.01'],
    'http_req_duration{endpoint:send-message}': ['p(95)<2000'],
    'http_req_duration{endpoint:mercure-token}': ['p(95)<2000'],
    chat_send_duration: ['p(95)<2000'],
    chat_message_errors: ['rate<0.01'],
  },
};

const users = JSON.parse(open('../data/test-users.json'));

const messageContents = [
  'Je lance les dés pour attaquer !',
  'Attention, il y a un piège devant vous.',
  'Mon personnage examine la salle avec attention.',
  'Je lance un sort de protection sur le groupe.',
  'On avance prudemment vers la porte nord.',
  'Je reste en arrière pour soigner les blessés.',
  'Quelqu\'un a une torche ? Il fait noir ici.',
  'Je tente de crocheter la serrure.',
  'Le monstre semble affaibli, continuez !',
  'Je me cache derrière la colonne.',
];

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

  // 2. Créer une partie (chaque VU a sa propre table)
  const gameRes = http.post(
    `${BASE_URL}/api/games`,
    JSON.stringify({
      name: `Table VU-${__VU} — Charge Test`,
      description: 'Partie créée par le test de charge K6',
      maxPlayers: 6,
      isPublic: true,
    }),
    { headers: jsonHeaders(), tags: { endpoint: 'create-game' } }
  );

  if (!check(gameRes, { 'create game 201': (r) => r.status === 201 })) {
    sleep(1);
    return;
  }

  const gameId = gameRes.json('id');

  // 3. Récupérer le token Mercure
  const mercureTokenRes = http.get(
    `${BASE_URL}/api/games/${gameId}/mercure-token`,
    { tags: { endpoint: 'mercure-token' } }
  );

  mercureTokenDuration.add(mercureTokenRes.timings.duration);

  check(mercureTokenRes, {
    'mercure token status 200': (r) => r.status === 200,
    'mercure token présent': (r) => r.json('token') !== null,
    'mercure token durée < 2000ms': (r) => r.timings.duration < 2000,
  });

  // NOTE SSE — K6 >= 0.50 :
  // Si vous utilisez grafana/k6 >= 0.50, vous pouvez décommenter le bloc ci-dessous
  // pour ouvrir une connexion SSE réelle vers Mercure et vérifier la réception des messages.
  //
  // import { openStream } from 'k6/experimental/streams';
  //
  // const mercureUrl = __ENV.MERCURE_URL || 'http://localhost:3000';
  // const mercureToken = mercureTokenRes.json('token');
  // const sseUrl = `${mercureUrl}/.well-known/mercure?topic=${encodeURIComponent(`game/${gameId}/chat`)}`;
  // const stream = openStream(sseUrl, {
  //   headers: { Authorization: `Bearer ${mercureToken}` },
  // });
  // stream.onmessage = (event) => { /* traiter les messages reçus */ };
  // ... envoyer des messages puis stream.close();

  sleep(0.5);

  // 4. Envoyer des messages en boucle (simule 3-5 messages par joueur sur la durée)
  const messageCount = Math.floor(Math.random() * 3) + 3; // 3 à 5 messages

  for (let i = 0; i < messageCount; i++) {
    const content = messageContents[Math.floor(Math.random() * messageContents.length)];

    const msgRes = http.post(
      `${BASE_URL}/api/games/${gameId}/chat/messages`,
      JSON.stringify({ type: 'chat', content }),
      { headers: jsonHeaders(), tags: { endpoint: 'send-message' } }
    );

    sendMessageDuration.add(msgRes.timings.duration);

    // Vérification fonctionnelle (alimente le taux d'erreur)
    const msgOk = check(msgRes, {
      'message status 201': (r) => r.status === 201,
      'message a id': (r) => r.json('id') !== undefined,
    });
    // Timing : indicatif uniquement (séparé du taux d'erreur)
    check(msgRes, { 'message durée < 2000ms': (r) => r.timings.duration < 2000 });

    messageErrors.add(!msgOk);

    // Pause entre messages : simule le temps de réflexion d'un joueur (3-5s)
    sleep(Math.random() * 2 + 3);
  }
}
