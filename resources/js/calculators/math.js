// Pure maths behind the blog calculators (EV, win rate, variance). No DOM here, so node --test can check it.
// Percentages come in as fractions (0.3 = 30 %); win rates and standard deviations are per 100 hands.

const finite = (value) => (Number.isFinite(value) ? value : 0);
const clamp01 = (value) => Math.min(1, Math.max(0, finite(value)));

/**
 * EV of betting `bet` into `pot` against a villain who folds, raises (we then fold) or calls (we then win the pot
 * `equity` of the time), compared with checking and realising `checkEquity` of the current pot.
 */
export function betEv({ pot, bet, fold, raise = 0, equity, checkEquity = 0 }) {
    pot = Math.max(0, finite(pot));
    bet = Math.max(0, finite(bet));
    fold = clamp01(fold);
    raise = Math.min(clamp01(raise), 1 - fold);
    equity = clamp01(equity);
    const call = Math.max(0, 1 - fold - raise);

    const foldPart = fold * pot;
    const callPart = call * (equity * (pot + 2 * bet) - bet);
    const raisePart = -raise * bet;
    const ev = foldPart + callPart + raisePart;
    const check = clamp01(checkEquity) * pot;

    // Equity needed when called for the bet to break even, given these fold and raise frequencies.
    const neededEquity = call > 0 ? Math.max(0, (call * bet + raise * bet - fold * pot) / (call * (pot + 2 * bet))) : null;

    return {
        ev,
        check,
        best: ev >= check ? 'bet' : 'check',
        callFreq: call,
        parts: { fold: foldPart, call: callPart, raise: raisePart },
        // How often a pure bluff (no equity, never raised) must work: bet / (pot + bet).
        bluffBreakEven: pot + bet > 0 ? bet / (pot + bet) : 0,
        neededEquity: neededEquity === null ? null : Math.min(neededEquity, 1.5),
    };
}

/** EV of calling `toCall` when the pot (villain's bet included) is `pot` and we win `equity` of the time. */
export function callEv({ pot, toCall, equity }) {
    pot = Math.max(0, finite(pot));
    toCall = Math.max(0, finite(toCall));
    equity = clamp01(equity);

    const ev = equity * (pot + toCall) - toCall;
    const neededEquity = pot + toCall > 0 ? toCall / (pot + toCall) : 0;
    // Extra chips we would have to win later (implied odds) for the call to break even.
    const impliedNeeded = equity > 0 ? Math.max(0, toCall / equity - pot - toCall) : null;

    return { ev, neededEquity, potOdds: toCall > 0 ? pot / toCall : null, impliedNeeded, best: ev >= 0 ? 'call' : 'fold' };
}

/**
 * Win rate from a result over a number of hands. `net` is in big blinds, or in money when `bigBlind` is given.
 * `stdDev` (bb/100) adds a 95 % confidence interval; `handsPerHour` adds hourly figures.
 */
export function winRate({ net, hands, bigBlind = null, stdDev = null, handsPerHour = null }) {
    hands = Math.max(0, finite(hands));
    net = finite(net);
    const bb = bigBlind && bigBlind > 0 ? bigBlind : null;
    const netBb = bb ? net / bb : net;

    if (hands <= 0) {
        return null;
    }

    const bb100 = (netBb / hands) * 100;
    const result = { bb100, money100: bb ? bb100 * bb : null, netBb, interval: null, hourlyBb: null, hourlyMoney: null };

    if (stdDev && stdDev > 0) {
        const half = (1.96 * stdDev) / Math.sqrt(hands / 100);
        result.interval = { low: bb100 - half, high: bb100 + half, half };
    }

    if (handsPerHour && handsPerHour > 0) {
        result.hourlyBb = (bb100 * handsPerHour) / 100;
        result.hourlyMoney = bb ? result.hourlyBb * bb : null;
    }

    return result;
}

/** Standard normal cumulative distribution (Abramowitz & Stegun 7.1.26, error below 1.5e-7). */
export function normalCdf(x) {
    const sign = x < 0 ? -1 : 1;
    const z = Math.abs(x) / Math.SQRT2;
    const t = 1 / (1 + 0.3275911 * z);
    const erf = 1 - (((((1.061405429 * t - 1.453152027) * t) + 1.421413741) * t - 0.284496736) * t + 0.254829592) * t * Math.exp(-z * z);

    return 0.5 * (1 + sign * erf);
}

/** What a win rate and standard deviation (both bb/100) mean over `hands`, plus risk of ruin for a bankroll in bb. */
export function variance({ winRate: wr, stdDev, hands, bankroll = null }) {
    wr = finite(wr);
    stdDev = Math.max(0, finite(stdDev));
    hands = Math.max(0, finite(hands));

    const mean = (wr * hands) / 100;
    const sigma = stdDev * Math.sqrt(hands / 100);
    const lossChance = sigma > 0 ? normalCdf(-mean / sigma) : (mean < 0 ? 1 : 0);

    const result = {
        mean,
        sigma,
        range68: { low: mean - sigma, high: mean + sigma },
        range95: { low: mean - 1.96 * sigma, high: mean + 1.96 * sigma },
        lossChance,
        // Hands until a 95 % one-sided interval sits above zero: (1.645 · sd / wr)² · 100.
        handsForConfidence: wr > 0 && stdDev > 0 ? Math.ceil(((1.645 * stdDev) / wr) ** 2 * 100) : null,
        riskOfRuin: null,
        bankrollFor5: wr > 0 && stdDev > 0 ? (-(stdDev ** 2) * Math.log(0.05)) / (2 * wr) : null,
    };

    if (bankroll !== null && bankroll > 0 && stdDev > 0) {
        result.riskOfRuin = wr > 0 ? Math.min(1, Math.exp((-2 * wr * bankroll) / stdDev ** 2)) : 1;
    }

    return result;
}

/** Small seeded generator (mulberry32) so simulations can be repeated in tests. */
export function seededRandom(seed) {
    let a = seed >>> 0;

    return () => {
        a = (a + 0x6d2b79f5) >>> 0;
        let t = a;
        t = Math.imul(t ^ (t >>> 15), t | 1);
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61);

        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

function gaussian(random) {
    let u = 0;
    while (u === 0) u = random();

    return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * random());
}

/** `paths` random runs of `hands` hands, each sampled at `steps` points (cumulative bb, starting at 0). */
export function simulateRuns({ winRate: wr, stdDev, hands, paths = 5, steps = 100, random = Math.random }) {
    const chunk = hands / steps;
    const stepMean = (finite(wr) * chunk) / 100;
    const stepSd = Math.max(0, finite(stdDev)) * Math.sqrt(chunk / 100);

    return Array.from({ length: paths }, () => {
        const run = [0];
        let total = 0;
        for (let i = 0; i < steps; i++) {
            total += stepMean + stepSd * gaussian(random);
            run.push(total);
        }

        return run;
    });
}
