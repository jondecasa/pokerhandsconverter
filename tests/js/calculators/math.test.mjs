import test from 'node:test';
import assert from 'node:assert/strict';
import { betEv, callEv, winRate, normalCdf, variance, simulateRuns, seededRandom } from '../../../resources/js/calculators/math.js';

const near = (actual, expected, tolerance = 1e-6) => assert.ok(Math.abs(actual - expected) <= tolerance, `${actual} ≉ ${expected}`);

test('a pure bluff breaks even when it works bet / (pot + bet) of the time', () => {
    const r = betEv({ pot: 100, bet: 50, fold: 1 / 3, equity: 0 });
    near(r.bluffBreakEven, 1 / 3);
    near(r.ev, 0);
});

test('bet EV adds the fold, call and raise branches', () => {
    // Pot 100, bet 75. Fold 40 %, raise 10 % (we fold), call 50 % with 30 % equity.
    const r = betEv({ pot: 100, bet: 75, fold: 0.4, raise: 0.1, equity: 0.3, checkEquity: 0.35 });
    near(r.parts.fold, 40);
    near(r.parts.call, 0.5 * (0.3 * 250 - 75));
    near(r.parts.raise, -7.5);
    near(r.ev, 40 + 0 - 7.5);
    near(r.check, 35);
    assert.equal(r.best, 'check');
    near(r.callFreq, 0.5);
});

test('the equity needed when called makes the bet break even', () => {
    const input = { pot: 100, bet: 75, fold: 0.2, raise: 0.05, equity: 0 };
    const { neededEquity } = betEv(input);
    near(betEv({ ...input, equity: neededEquity }).ev, 0, 1e-9);
});

test('fold and raise frequencies above 100 % are capped', () => {
    const r = betEv({ pot: 100, bet: 50, fold: 0.8, raise: 0.5, equity: 0.5 });
    near(r.callFreq, 0);
    assert.equal(r.neededEquity, null);
    near(r.ev, 80 - 0.2 * 50);
});

test('call EV and pot odds', () => {
    // Pot 100, villain bets 50: pot is 150, we call 50 and need 25 %.
    const r = callEv({ pot: 150, toCall: 50, equity: 0.25 });
    near(r.neededEquity, 0.25);
    near(r.ev, 0);
    near(r.potOdds, 3);
    near(callEv({ pot: 150, toCall: 50, equity: 0.2 }).impliedNeeded, 50);
    assert.equal(callEv({ pot: 150, toCall: 50, equity: 0.2 }).best, 'fold');
});

test('win rate in bb/100, money and per hour', () => {
    const r = winRate({ net: 500, hands: 50000, bigBlind: 0.1, stdDev: 90, handsPerHour: 400 });
    near(r.bb100, 10);
    near(r.money100, 1);
    near(r.interval.half, (1.96 * 90) / Math.sqrt(500));
    near(r.hourlyBb, 40);
    near(r.hourlyMoney, 4);
    assert.equal(winRate({ net: 10, hands: 0 }), null);
    near(winRate({ net: -300, hands: 20000 }).bb100, -1.5);
});

test('normal CDF matches known values', () => {
    near(normalCdf(0), 0.5, 1e-7);
    near(normalCdf(1.96), 0.975, 1e-4);
    near(normalCdf(-1), 0.158655, 1e-5);
});

test('variance over a sample', () => {
    const r = variance({ winRate: 5, stdDev: 100, hands: 100000, bankroll: 3000 });
    near(r.mean, 5000);
    near(r.sigma, 100 * Math.sqrt(1000));
    near(r.lossChance, normalCdf(-5000 / r.sigma));
    assert.equal(r.handsForConfidence, Math.ceil((1.645 * 100 / 5) ** 2 * 100));
    near(r.riskOfRuin, Math.exp(-2 * 5 * 3000 / 10000));
    near(Math.exp(-2 * 5 * r.bankrollFor5 / 10000), 0.05);
    assert.equal(variance({ winRate: -2, stdDev: 80, hands: 1000, bankroll: 2000 }).riskOfRuin, 1);
});

test('simulated runs start at zero and drift with the win rate', () => {
    const runs = simulateRuns({ winRate: 10, stdDev: 90, hands: 1000000, paths: 200, steps: 50, random: seededRandom(7) });
    assert.equal(runs.length, 200);
    assert.equal(runs[0].length, 51);
    assert.ok(runs.every((run) => run[0] === 0));
    const average = runs.reduce((sum, run) => sum + run[50], 0) / runs.length;
    // Expected 100,000 bb with a standard error of about 6,400 bb across 200 runs.
    assert.ok(Math.abs(average - 100000) < 25000, `average ${average}`);
});
