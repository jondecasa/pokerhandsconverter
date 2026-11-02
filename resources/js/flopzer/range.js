// A range is a Float32Array of 1326 weights (0..1), one per exact combo, so a range can hold "only the hearts" of a hand.
// The grid shows the average weight of the combos still alive (not blocked by the board).

import { COMBO_COUNT, COMBOS, CLASS_COMBOS, CLASS_COUNT, CLASS_NAMES, CLASS_INDEX, RANKS, SUITS, comboIndex, parseCard, cardName, classIndexOfCards, COMBO_CLASS } from './cards.js';
import { HAND_RANKING } from './handRanking.js';

export const emptyRange = () => new Float32Array(COMBO_COUNT);
export const cloneRange = (weights) => Float32Array.from(weights);

export function setClassWeight(weights, classIndex, w) {
    for (const i of CLASS_COMBOS[classIndex]) weights[i] = w;
}

/** Combos blocked by the dead cards (a set of card integers). */
export const isBlocked = (comboIdx, dead) => dead.has(COMBOS[comboIdx][0]) || dead.has(COMBOS[comboIdx][1]);

export function rangeTotals(weights, dead = new Set()) {
    let combos = 0;
    for (let i = 0; i < COMBO_COUNT; i++) if (weights[i] > 0 && !isBlocked(i, dead)) combos += weights[i];
    return { combos, percent: (100 * combos) / 1326 };
}

/** Average weight (0..1) of a grid cell over its live combos, and how many combos are alive. */
export function cellInfo(weights, classIndex, dead = new Set()) {
    let alive = 0, sum = 0;
    for (const i of CLASS_COMBOS[classIndex]) {
        if (isBlocked(i, dead)) continue;
        alive++;
        sum += weights[i];
    }
    return { alive, weight: alive ? sum / alive : 0, combos: sum };
}

// ---- text -> range ---------------------------------------------------------------------------------------------

const rankIdx = (ch) => RANKS.indexOf(ch.toUpperCase());

/** "AA-TT, AKs-ATs, KQo, 77+, A5s-A2s:50%, AhKh" -> weights. Throws Error with a readable message. */
export function parseRange(text) {
    const weights = emptyRange();
    const tokens = text.split(/[\s,;]+/).filter(Boolean);
    for (const raw of tokens) {
        const m = raw.match(/^(.*?)(?:[:@](\d+(?:\.\d+)?)(%?))?$/);
        let w = 1;
        if (m[2] !== undefined) {
            w = parseFloat(m[2]);
            if (m[3] === '%' || w > 1) w /= 100;
            if (w < 0 || w > 1) throw new Error(`Weight out of range in "${raw}"`);
        }
        for (const index of expandToken(m[1], raw)) weights[index] = w;
    }
    return weights;
}

function expandToken(token, raw) {
    if (/^[2-9TJQKAtjqka][shdc][2-9TJQKAtjqka][shdc]$/i.test(token)) {
        const [c1, c2] = [parseCard(token.slice(0, 2)), parseCard(token.slice(2, 4))];
        if (c1 === c2) throw new Error(`"${raw}" repeats a card`);
        return [comboIndex(c1, c2)];
    }
    const plus = token.endsWith('+');
    const body = plus ? token.slice(0, -1) : token;
    const dash = body.split('-');
    if (dash.length > 2) throw new Error(`Cannot read "${raw}"`);
    const first = parseHand(dash[0], raw);
    const classes = [];

    if (dash.length === 2) {
        const second = parseHand(dash[1], raw);
        if (first.pair !== second.pair || (!first.pair && first.kind !== second.kind)) throw new Error(`"${raw}" mixes different hand types`);
        if (first.pair) {
            for (let r = Math.min(first.hi, second.hi); r <= Math.max(first.hi, second.hi); r++) classes.push(pairClass(r));
        } else if (first.hi === second.hi) {
            for (let r = Math.min(first.lo, second.lo); r <= Math.max(first.lo, second.lo); r++) classes.push(...handClasses(first.hi, r, first.kind));
        } else if (first.hi - first.lo === second.hi - second.lo) {
            const gap = first.hi - first.lo;
            for (let hi = Math.min(first.hi, second.hi); hi <= Math.max(first.hi, second.hi); hi++) classes.push(...handClasses(hi, hi - gap, first.kind));
        } else {
            throw new Error(`"${raw}" is not a valid range (the hands must share the top card or the gap)`);
        }
    } else if (plus) {
        if (first.pair) {
            for (let r = first.hi; r <= 12; r++) classes.push(pairClass(r));
        } else {
            for (let r = first.lo; r < first.hi; r++) classes.push(...handClasses(first.hi, r, first.kind));
        }
    } else {
        classes.push(...(first.pair ? [pairClass(first.hi)] : handClasses(first.hi, first.lo, first.kind)));
    }

    return classes.flatMap((k) => CLASS_COMBOS[k]);
}

function parseHand(text, raw) {
    const m = text.match(/^([2-9TJQKAtjqka])([2-9TJQKAtjqka])([soSO]?)$/);
    if (!m) throw new Error(`Cannot read "${raw}"`);
    const a = rankIdx(m[1]), b = rankIdx(m[2]);
    const kind = m[3].toLowerCase();
    if (a === b) {
        if (kind) throw new Error(`"${raw}": a pair cannot be suited or offsuit`);
        return { pair: true, hi: a, lo: a, kind: '' };
    }
    return { pair: false, hi: Math.max(a, b), lo: Math.min(a, b), kind };
}

const pairClass = (r) => CLASS_INDEX[RANKS[r] + RANKS[r]];
const handClasses = (hi, lo, kind) => {
    const out = [];
    if (kind !== 'o') out.push(CLASS_INDEX[RANKS[hi] + RANKS[lo] + 's']);
    if (kind !== 's') out.push(CLASS_INDEX[RANKS[hi] + RANKS[lo] + 'o']);
    return out;
};

// ---- range -> text ---------------------------------------------------------------------------------------------

const fmtWeight = (w) => (w >= 0.9995 ? '' : ':' + Math.round(w * 1000) / 10 + '%');

/** Weights -> compact text that parseRange reads back to the same range (to the percent). */
export function formatRange(weights) {
    // class-level weights where every combo agrees, otherwise individual combos
    const classWeight = new Array(CLASS_COUNT).fill(0);
    const loose = [];
    for (let k = 0; k < CLASS_COUNT; k++) {
        const list = CLASS_COMBOS[k];
        const w0 = weights[list[0]];
        if (list.every((i) => Math.abs(weights[i] - w0) < 1e-6)) classWeight[k] = Math.round(w0 * 1000) / 1000;
        else for (const i of list) if (weights[i] > 0) loose.push(i);
    }

    const byWeight = new Map();
    classWeight.forEach((w, k) => { if (w > 0) { if (!byWeight.has(w)) byWeight.set(w, []); byWeight.get(w).push(k); } });

    const parts = [];
    for (const [w, ks] of [...byWeight.entries()].sort((a, b) => b[0] - a[0])) {
        const set = new Set(ks);
        const tokens = [];
        // pairs, highest first
        runs(12, 0, (r) => set.has(pairClass(r))).forEach(([from, to]) => {
            if (from === to) tokens.push(RANKS[from] + RANKS[from]);
            else if (from === 12) tokens.push(RANKS[to] + RANKS[to] + '+');
            else tokens.push(RANKS[from] + RANKS[from] + '-' + RANKS[to] + RANKS[to]);
        });
        for (const kind of ['s', 'o']) {
            for (let hi = 12; hi >= 1; hi--) {
                runs(hi - 1, 0, (lo) => set.has(CLASS_INDEX[RANKS[hi] + RANKS[lo] + kind])).forEach(([from, to]) => {
                    const name = (lo) => RANKS[hi] + RANKS[lo] + kind;
                    if (from === to) tokens.push(name(from));
                    else if (from === hi - 1) tokens.push(name(to) + '+');
                    else tokens.push(name(from) + '-' + name(to));
                });
            }
        }
        const suffix = fmtWeight(w);
        parts.push(tokens.map((t) => t + suffix).join(', '));
    }
    for (const i of loose) parts.push(cardName(COMBOS[i][0]) + cardName(COMBOS[i][1]) + fmtWeight(weights[i]));
    return parts.filter(Boolean).join(', ');
}

/** Runs of consecutive indexes (from high to low) for which `has(index)` is true: [[from, to], ...] with from >= to. */
function runs(from, to, has) {
    const out = [];
    let start = null;
    for (let i = from; i >= to; i--) {
        if (has(i)) { if (start === null) start = i; if (i === to) { out.push([start, i]); start = null; } }
        else if (start !== null) { out.push([start, i + 1]); start = null; }
    }
    return out;
}

// ---- strength ranking ------------------------------------------------------------------------------------------

/** The strongest hands covering about `percent` % of all combos, as a range with full weights. */
export function topPercent(percent) {
    const weights = emptyRange();
    const target = (percent / 100) * 1326;
    let taken = 0;
    for (const name of HAND_RANKING) {
        if (taken >= target - 1e-9) break;
        const k = CLASS_INDEX[name];
        setClassWeight(weights, k, 1);
        taken += CLASS_COMBOS[k].length;
    }
    return weights;
}

export { CLASS_NAMES, COMBO_CLASS, classIndexOfCards, SUITS };
