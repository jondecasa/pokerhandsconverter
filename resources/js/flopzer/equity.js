// Equity of one range against another on a given board.
//
// Every runout (the turn/river cards still to come) is enumerated, in random order, so the result can be shown while it
// is still being refined and is exact once every runout is done. Combos that share a card with the board, with the
// runout or with each other are excluded the way the game does it: each (hero combo, villain combo, runout) triple that
// can really be dealt counts once, weighted by the two combo weights.

import { COMBOS, COMBO_COUNT } from './cards.js';
import { evaluate } from './evaluator.js';

/** The live combos of a range: those with weight > 0 and no board card. */
export function liveCombos(weights, boardSet) {
    const out = [];
    for (let i = 0; i < COMBO_COUNT; i++) {
        if (weights[i] <= 0) continue;
        const [c1, c2] = COMBOS[i];
        if (boardSet.has(c1) || boardSet.has(c2)) continue;
        out.push({ index: i, c1, c2, w: weights[i] });
    }
    return out;
}

function mulberry32(seed) {
    let a = seed;
    return () => { a = (a + 0x6D2B79F5) | 0; let t = Math.imul(a ^ (a >>> 15), 1 | a); t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t; return ((t ^ (t >>> 14)) >>> 0) / 4294967296; };
}

/** All ways to deal `needed` more cards from `deck`, shuffled. */
function runoutsOf(deck, needed, rand) {
    const out = [];
    if (needed === 0) out.push([]);
    else if (needed === 1) for (const a of deck) out.push([a]);
    else for (let i = 0; i < deck.length; i++) for (let j = i + 1; j < deck.length; j++) out.push([deck[i], deck[j]]);
    for (let i = out.length - 1; i > 0; i--) { const j = Math.floor(rand() * (i + 1)); [out[i], out[j]] = [out[j], out[i]]; }
    return out;
}

/**
 * @param {Float32Array} heroWeights
 * @param {Float32Array} villainWeights
 * @param {number[]} board 3..5 cards
 * @param {number[]} dead cards known to be out of the deck (besides the board)
 * @returns an object with step(ms) -> boolean (true when finished), progress, and result()
 */
export function createEquityJob(heroWeights, villainWeights, board, seed = 1, dead = []) {
    // dead cards are out of the deck: no combo holds them and they never come on the turn or river
    const blocked = new Set([...board, ...dead]);
    const hero = liveCombos(heroWeights, blocked);
    const villain = liveCombos(villainWeights, blocked);
    const deck = [];
    for (let c = 0; c < 52; c++) if (!blocked.has(c)) deck.push(c);
    const runouts = runoutsOf(deck, 5 - board.length, mulberry32(seed));

    const sides = [makeSide(hero), makeSide(villain)];
    const buf = [0, 0, 0, 0, 0, 0, 0];
    let done = 0;

    function step(maxMs = 30) {
        const start = performance.now();
        while (done < runouts.length && performance.now() - start < maxMs) {
            const runout = runouts[done++];
            const full = board.concat(runout);
            const dead = new Set(runout);
            const scores = [scoreAll(hero, full, dead, buf), scoreAll(villain, full, dead, buf)];
            accumulate(sides[0], hero, scores[0], sides[1], villain, scores[1], dead);
            accumulate(sides[1], villain, scores[1], sides[0], hero, scores[0], dead);
        }
        return done >= runouts.length;
    }

    const result = () => ({
        done, total: runouts.length,
        hero: summarize(sides[0], hero), villain: summarize(sides[1], villain),
    });

    return { step, result, get progress() { return runouts.length ? done / runouts.length : 1; }, hero, villain };
}

function makeSide(list) {
    return { numer: new Float64Array(list.length), denom: new Float64Array(list.length) };
}

function scoreAll(list, full, dead, buf) {
    const scores = new Int32Array(list.length).fill(-1);
    const n = full.length;
    for (let k = 0; k < n; k++) buf[2 + k] = full[k];
    for (let i = 0; i < list.length; i++) {
        const { c1, c2 } = list[i];
        if (dead.has(c1) || dead.has(c2)) continue;
        buf[0] = c1; buf[1] = c2;
        scores[i] = evaluate(buf);
    }
    return scores;
}

/** Adds one runout's results of `me` against `them` into me's accumulators. */
function accumulate(meSide, me, meScores, _themSide, them, themScores, _dead) {
    // villains that are alive on this runout, sorted by score, with prefix sums of their weights
    const live = [];
    for (let i = 0; i < them.length; i++) if (themScores[i] >= 0) live.push(i);
    live.sort((a, b) => themScores[a] - themScores[b]);
    const n = live.length;
    const sortedScore = new Int32Array(n);
    const prefix = new Float64Array(n + 1);
    for (let k = 0; k < n; k++) { sortedScore[k] = themScores[live[k]]; prefix[k + 1] = prefix[k] + them[live[k]].w; }
    const total = prefix[n];

    // villain combos holding each card, to take the blocked ones back out
    const byCard = new Map();
    for (const i of live) for (const c of [them[i].c1, them[i].c2]) { if (!byCard.has(c)) byCard.set(c, []); byCard.get(c).push(i); }

    for (let a = 0; a < me.length; a++) {
        const s = meScores[a];
        if (s < 0) continue;
        // weights of villains strictly below / equal to s
        const lo = lowerBound(sortedScore, s);
        const hi = upperBound(sortedScore, s);
        let below = prefix[lo], equal = prefix[hi] - prefix[lo], all = total;
        const { c1, c2 } = me[a];
        for (const c of [c1, c2]) {
            const list = byCard.get(c);
            if (!list) continue;
            for (const b of list) {
                if (c === c2 && (them[b].c1 === c1 || them[b].c2 === c1)) continue; // already removed through c1
                const w = them[b].w, sb = themScores[b];
                all -= w;
                if (sb < s) below -= w; else if (sb === s) equal -= w;
            }
        }
        if (all <= 1e-12) continue;
        meSide.numer[a] += below + 0.5 * equal;
        meSide.denom[a] += all;
    }
}

function lowerBound(arr, x) { let lo = 0, hi = arr.length; while (lo < hi) { const m = (lo + hi) >> 1; if (arr[m] < x) lo = m + 1; else hi = m; } return lo; }
function upperBound(arr, x) { let lo = 0, hi = arr.length; while (lo < hi) { const m = (lo + hi) >> 1; if (arr[m] <= x) lo = m + 1; else hi = m; } return lo; }

function summarize(side, list) {
    // per combo equity (0..1), and the range-wide equity weighted by combo weight and by how many opponents each could face
    const equity = new Float32Array(COMBO_COUNT).fill(-1);
    let n = 0, d = 0;
    for (let i = 0; i < list.length; i++) {
        if (side.denom[i] > 0) {
            equity[list[i].index] = side.numer[i] / side.denom[i];
            n += list[i].w * side.numer[i];
            d += list[i].w * side.denom[i];
        }
    }
    return { equity, overall: d > 0 ? n / d : null };
}
