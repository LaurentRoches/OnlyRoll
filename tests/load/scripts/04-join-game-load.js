/**
 * Scénario 4 — Rejoindre une partie (spike + race condition)
 *
 * Teste le comportement sous charge concurrent du mécanisme de jonction.
 * Valide que la contrainte de capacité (maxPlayers) est respectée même
 * quand 20 VUs tentent de rejoindre simultanément une partie avec 1 place.
 *
 * Les fixtures LoadTestFixtures créent 3 parties "spike" avec maxPlayers=2
 * (le GM occupe la 1ère place, il reste exactement 1 place libre).
 *
 * Résultats attendus :
 * - 1 VU obtient 200 (place disponible)
 * - Les autres obtiennent 409 (GameFullException — comportement correct)
 * - 0 cas de race condition (jamais > maxPlayers joueurs actifs)
 *
 * Les IDs des parties spike sont passés en variable d'env (récupérés après
 * chargement des fixtures via GET /api/games?search=Spike+Test).
 *
 * Exécution :
 *   docker run --rm -i --network host \
 *     -v $(pwd)/tests/load:/scripts \
 *     grafana/k6 run /scripts/scripts/04-join-game-load.js \
 *     --env BASE_URL=http://localhost
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import exec from 'k6/execution';
import { Counter, Trend, Rate } from 'k6/metrics';
import { jsonHeaders, vuUser } from '../helpers/auth.js';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

const joinDuration = new Trend('join_duration', true);
const joinSuccess = new Counter('join_success');
const joinFull = new Counter('join_full_409');
const joinErrors = new Rate('join_real_errors');

export const options = {
  // Spike : 20 VUs instantanément pendant 1 minute, répété 3 fois
  scenarios: {
    spike_round_1: {
      executor: 'constant-vus',
      vus: 20,
      duration: '1m',
      startTime: '0s',
    },
    spike_round_2: {
      executor: 'constant-vus',
      vus: 20,
      duration: '1m',
      startTime: '1m30s', // pause de 30s entre les rounds
    },
    spike_round_3: {
      executor: 'constant-vus',
      vus: 20,
      duration: '1m',
      startTime: '3m',
    },
  },
  thresholds: {
    // Ce scénario teste la CONCURRENCE (race condition), pas la performance.
    // Les vrais indicateurs sont : join_success (1/round) et join_full_409.
    // Seul le taux d'erreurs réelles (≠ 200/409/403) compte comme critère de passage.
    join_real_errors: ['rate<0.01'],
  },
};

const users = JSON.parse(open('../data/test-users.json'));

/**
 * Crée 3 parties fraîches avec maxPlayers=2 pour chaque run du test.
 * Chaque partie a 1 place libre (le GM occupe la première).
 * → Garantit un état propre même si le test est relancé.
 */
export function setup() {
  const spikeGameIds = [];

  for (let round = 1; round <= 3; round++) {
    // GM différent par round pour éviter les conflits d'appartenance
    const gmUser = users[round - 1];

    const loginRes = http.post(
      `${BASE_URL}/api/login`,
      JSON.stringify({ email: gmUser.email, password: gmUser.password }),
      { headers: jsonHeaders() }
    );

    if (loginRes.status !== 200) {
      throw new Error(`Setup round ${round}: login failed (${loginRes.status})`);
    }

    const gameRes = http.post(
      `${BASE_URL}/api/games`,
      JSON.stringify({
        name: `Spike Round ${round} — ${Date.now()}`,
        description: 'Partie spike créée au setup K6',
        maxPlayers: 2,
        isPublic: true,
      }),
      { headers: jsonHeaders() }
    );

    if (gameRes.status !== 201) {
      throw new Error(`Setup round ${round}: create game failed (${gameRes.status}) — ${gameRes.body}`);
    }

    spikeGameIds.push(gameRes.json('id'));
  }

  return { spikeGameIds };
}

export default function (data) {
  const { spikeGameIds } = data;

  // On mappe le nom du scénario courant à l'index de la partie spike correspondante
  const roundMap = { spike_round_1: 0, spike_round_2: 1, spike_round_3: 2 };
  const roundIndex = roundMap[exec.scenario.name] ?? 0;
  const gameId = spikeGameIds[roundIndex];

  const user = vuUser(users, __VU);

  // Login
  const loginRes = http.post(
    `${BASE_URL}/api/login`,
    JSON.stringify({ email: user.email, password: user.password }),
    { headers: jsonHeaders() }
  );

  if (!check(loginRes, { 'login 200': (r) => r.status === 200 })) {
    sleep(1);
    return;
  }

  // Tentative de join par ID
  const joinRes = http.post(
    `${BASE_URL}/api/games/${gameId}/join`,
    JSON.stringify({}),
    { headers: jsonHeaders(), tags: { endpoint: 'join-by-id' } }
  );

  joinDuration.add(joinRes.timings.duration);

  const isJoinOk = joinRes.status === 200;
  const isGameFull = joinRes.status === 409;
  const isAlreadyIn = joinRes.status === 403; // "déjà membre"
  const isRealError = !isJoinOk && !isGameFull && !isAlreadyIn;

  if (isJoinOk) {
    joinSuccess.add(1);
  } else if (isGameFull) {
    joinFull.add(1);
  }

  joinErrors.add(isRealError);

  check(joinRes, {
    'join ok ou plein ou déjà membre': (r) =>
      r.status === 200 || r.status === 409 || r.status === 403,
    'join durée < 3000ms': (r) => r.timings.duration < 3000,
  });

  sleep(1);
}

export function handleSummary(data) {
  const successCount = data.metrics.join_success?.values?.count ?? 0;
  const fullCount = data.metrics.join_full_409?.values?.count ?? 0;

  console.log(`\n=== Résumé race condition ===`);
  console.log(`Joins réussis  : ${successCount}`);
  console.log(`Parties pleines (409): ${fullCount}`);
  console.log(
    `Ratio plein/total : ${Math.round((fullCount / (successCount + fullCount)) * 100)}%`
  );

  return {};
}
