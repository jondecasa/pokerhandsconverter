// What a hand is on a given board. Two independent lists, as in Flopzilla:
//   - made hand: every combo falls in exactly one row (some rows unfold into finer ones);
//   - draw: the draw (or draw + pair combination) of the combos that have one; the others have none.
// A row is { key, label, kids? }. A row with kids is the sum of them; a row without kids is its own fine bucket.

import { evaluate } from './evaluator.js';

const kid = (key, label) => ({ key, label });

export const MADE_ROWS = [
    { key: 'straightFlush', label: 'Straight flush' },
    { key: 'quads', label: 'Quads' },
    { key: 'fullHouse', label: 'Full house' },
    { key: 'flush', label: 'Flush', kids: [kid('nutFlush', 'Nut flush'), kid('otherFlush', 'Other flush')] },
    { key: 'straight', label: 'Straight' },
    { key: 'set', label: 'Set', kids: [kid('pocketSet', 'Set (pocket pair)'), kid('trips', 'Trips (pair on board)'), kid('boardTrips', 'Trips on board')] },
    {
        key: 'twoPair', label: 'Two pair',
        kids: [kid('twoPairTop', 'Top two pair'), kid('twoPairTopLow', 'Top and lower pair'), kid('twoPairLow', 'Lower two pair'),
            kid('pocketBoardPair', 'Pocket pair + board pair'), kid('pairBoardPair', 'Pair + board pair'), kid('boardTwoPair', 'Two pair on board')],
    },
    { key: 'overpair', label: 'overpair' },
    { key: 'topPair', label: 'Top pair', kids: [kid('topPairTK', 'Top kicker'), kid('topPairGK', 'Good kicker'), kid('topPairWK', 'Weak kicker')] },
    { key: 'middlePair', label: 'Middle pair', kids: [kid('middleCard', 'Pair with a middle card'), kid('ppAbove2nd', 'Pocket pair above the 2nd card')] },
    { key: 'ppBelow2nd', label: 'pp < 2nd card' },
    { key: 'bottomPair', label: 'Bottom pair' },
    { key: 'ppBelowBoard', label: 'pp < board' },
    { key: 'noMade', label: 'No made hand', kids: [kid('aceHigh', 'Ace high'), kid('kingHigh', 'King high'), kid('lowHigh', 'Queen high or lower'), kid('playsBoard', 'Plays the board')] },
];

export const DRAW_ROWS = [
    { key: 'flushDraw', label: 'flushdraw', kids: [kid('nutFlushDraw', 'Nut flush draw'), kid('otherFlushDraw', 'Other flush draw')] },
    { key: 'oesd2', label: 'oesd (2 card)' },
    { key: 'oesd1', label: 'oesd (1 card)' },
    { key: 'gutshot2', label: 'gutshot (2 crd)' },
    { key: 'gutshot1', label: 'gutshot (1 crd)' },
    { key: 'overBdfd', label: '1 overc.+bdfd' },
    { key: 'backdoorFd', label: 'backdoor fd', kids: [kid('bdfd2', '2 cards of the suit'), kid('bdfd1', '1 card of the suit')] },
];

export const COMBO_ROWS = [
    { key: 'flushDrawPair', label: 'flushdraw+pair' },
    { key: 'flushDrawOesd', label: 'flushdr.+oesd' },
    { key: 'flushDrawGutshot', label: 'flushdr.+gutsh.' },
    { key: 'oesdPair', label: 'oesd+pair' },
    { key: 'gutshotPair', label: 'gutshot+pair' },
    { key: 'gutshotBdfd', label: 'gutsh.+bdfd' },
];

function indexRows(rows) {
    const parent = {}, label = {};
    for (const row of rows) {
        label[row.key] = row.label;
        parent[row.key] = row.key;
        for (const k of row.kids ?? []) { label[k.key] = k.label; parent[k.key] = row.key; }
    }
    return { parent, label };
}
const MADE_INDEX = indexRows(MADE_ROWS);
const DRAW_INDEX = indexRows([...DRAW_ROWS, ...COMBO_ROWS]);

/** fine bucket -> its row, and any bucket -> a readable name */
export const madeRowOf = (key) => MADE_INDEX.parent[key];
export const drawRowOf = (key) => DRAW_INDEX.parent[key];
export const madeLabel = (key) => MADE_INDEX.label[key];
export const drawLabel = (key) => DRAW_INDEX.label[key];

const ACE = 12;
const KING = 11;
const TEN = 8;

// the ten five-rank windows that make a straight (bit r = rank r; the wheel uses the ace as a low card)
const WINDOWS = [];
for (let top = 12; top >= 4; top--) WINDOWS.push(0b11111 << (top - 4));
WINDOWS.push((1 << 12) | 0b1111);

const popcount = (n) => { let c = 0; while (n) { c += n & 1; n >>= 1; } return c; };

const SINGLE_PAIRS = new Set(['overpair', 'topPairTK', 'topPairGK', 'topPairWK', 'middleCard', 'ppAbove2nd', 'ppBelow2nd', 'bottomPair', 'ppBelowBoard']);

/** @returns {{made: string, madeRow: string, draw: (string|null), drawRow: (string|null)}} */
export function classify(hole, board) {
    const [h1, h2] = hole;
    const r1 = h1 >> 2, r2 = h2 >> 2;
    const pocket = r1 === r2;
    const score = evaluate([h1, h2, ...board]);
    const cat = score >> 20;

    const boardCount = new Int8Array(13);
    let boardMask = 0;
    for (const c of board) { boardCount[c >> 2]++; boardMask |= 1 << (c >> 2); }
    const boardRanks = [];
    for (let r = 12; r >= 0; r--) if (boardCount[r]) boardRanks.push(r);

    const made = madeHand({ hole, board, r1, r2, pocket, score, cat, boardCount, boardRanks });
    const draw = board.length >= 5 ? null : drawOf({ hole, board, r1, r2, pocket, cat, boardCount, boardMask, boardTop: boardRanks[0], isPair: SINGLE_PAIRS.has(made) });
    return { made, madeRow: MADE_INDEX.parent[made], draw, drawRow: draw ? DRAW_INDEX.parent[draw] : null };
}

function madeHand({ hole, board, r1, r2, pocket, score, cat, boardCount, boardRanks }) {
    if (board.length === 5 && evaluate(board) === score) return 'playsBoard';

    switch (cat) {
        case 8: return 'straightFlush';
        case 7: return 'quads';
        case 6: return 'fullHouse';
        case 5: return isNutFlush(hole, board) ? 'nutFlush' : 'otherFlush';
        case 4: return 'straight';
        case 3:
            if (pocket && boardCount[r1] >= 1) return 'pocketSet';
            if (boardCount[r1] >= 2 || boardCount[r2] >= 2) return 'trips';
            return 'boardTrips';
        case 2: {
            const pairs = [];
            for (let r = 12; r >= 0; r--) if (boardCount[r] + (r === r1) + (r === r2) === 2) pairs.push(r);
            const [p1, p2] = pairs;
            const inHole = (r) => r === r1 || r === r2;
            if (pocket && (inHole(p1) || inHole(p2))) return 'pocketBoardPair';
            if (inHole(p1) && inHole(p2)) {
                const i1 = boardRanks.indexOf(p1), i2 = boardRanks.indexOf(p2);
                if (i1 === 0 && i2 === 1) return 'twoPairTop';
                return i1 === 0 ? 'twoPairTopLow' : 'twoPairLow';
            }
            if (inHole(p1) || inHole(p2)) return 'pairBoardPair';
            return 'boardTwoPair';
        }
        case 1: {
            if (pocket && boardCount[r1] === 0) {
                if (r1 > boardRanks[0]) return 'overpair';
                if (boardRanks.length > 1 && r1 > boardRanks[1]) return 'ppAbove2nd';
                return r1 > boardRanks[boardRanks.length - 1] ? 'ppBelow2nd' : 'ppBelowBoard';
            }
            const paired = boardCount[r1] === 1 ? r1 : (boardCount[r2] === 1 ? r2 : -1);
            if (paired >= 0) {
                const index = boardRanks.indexOf(paired);
                if (index === 0) {
                    const kicker = paired === r1 ? r2 : r1;
                    const best = paired === ACE ? KING : ACE;
                    return kicker === best ? 'topPairTK' : (kicker >= TEN ? 'topPairGK' : 'topPairWK');
                }
                return index === boardRanks.length - 1 ? 'bottomPair' : 'middleCard';
            }
            return noPair(r1, r2); // the pair is the board's own
        }
        default: return noPair(r1, r2);
    }
}

function noPair(r1, r2) {
    const top = Math.max(r1, r2);
    return top === ACE ? 'aceHigh' : (top === KING ? 'kingHigh' : 'lowHigh');
}

/** Does the hand hold the best card of the flush suit that is not on the board? */
function isNutFlush(hole, board) {
    const count = [0, 0, 0, 0];
    for (const c of [...hole, ...board]) count[c & 3]++;
    const suit = count.findIndex((n) => n >= 5);
    const onBoard = new Set(board);
    for (let r = 12; r >= 0; r--) {
        const card = r * 4 + suit;
        if (onBoard.has(card)) continue;
        return hole.includes(card);
    }
    return false;
}

function drawOf({ hole, board, r1, r2, pocket, cat, boardCount, boardMask, boardTop, isPair }) {
    if (cat >= 5) return null; // flush or better: nothing left to draw to

    const holeMask = (1 << r1) | (1 << r2);
    const mask = holeMask | boardMask;

    // flush draw (four to a flush using a hole card) and backdoor flush draw (three, flop only)
    const boardSuit = [0, 0, 0, 0], holeSuit = [0, 0, 0, 0];
    for (const c of board) boardSuit[c & 3]++;
    for (const c of hole) holeSuit[c & 3]++;
    let flushDraw = -1, backdoor = 0;
    for (let s = 0; s < 4; s++) {
        if (holeSuit[s] >= 1 && boardSuit[s] + holeSuit[s] === 4) flushDraw = s;
        if (board.length === 3 && holeSuit[s] >= 1 && boardSuit[s] + holeSuit[s] === 3) backdoor = holeSuit[s];
    }
    let nut = false;
    if (flushDraw >= 0) {
        const onBoard = new Set(board);
        for (let r = 12; r >= 0; r--) {
            const card = r * 4 + flushDraw;
            if (onBoard.has(card)) continue;
            nut = hole.includes(card);
            break;
        }
    }

    // straight draw: ranks that would complete a straight using a hole card, and how many hole cards the draw leans on
    const outs = [];
    let used = 0;
    if (cat < 4) {
        for (let r = 0; r < 13; r++) {
            if (mask & (1 << r)) continue;
            const withOut = mask | (1 << r);
            let out = false;
            for (const w of WINDOWS) {
                if ((withOut & w) === w && (w & (1 << r)) && (w & holeMask)) { out = true; used = Math.max(used, popcount(w & holeMask)); }
            }
            if (out) outs.push(r);
        }
    }
    const straight = outs.length >= 2 ? 'oesd' : (outs.length === 1 ? 'gutshot' : null); // eight outs of two kinds count as open-ended
    const cards = used >= 2 ? 2 : 1;

    const bdfd = backdoor > 0;
    const over = !pocket && boardCount[r1] === 0 && boardCount[r2] === 0 ? (r1 > boardTop) + (r2 > boardTop) : 0;

    if (flushDraw >= 0 && isPair) return 'flushDrawPair';
    if (flushDraw >= 0 && straight === 'oesd') return 'flushDrawOesd';
    if (flushDraw >= 0 && straight === 'gutshot') return 'flushDrawGutshot';
    if (flushDraw >= 0) return nut ? 'nutFlushDraw' : 'otherFlushDraw';
    if (straight === 'oesd' && isPair) return 'oesdPair';
    if (straight === 'gutshot' && isPair) return 'gutshotPair';
    if (straight === 'gutshot' && bdfd) return 'gutshotBdfd';
    if (straight) return straight + cards;
    if (bdfd && over === 1) return 'overBdfd';
    if (bdfd) return backdoor === 2 ? 'bdfd2' : 'bdfd1';
    return null;
}
