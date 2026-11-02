// Best-five-of-up-to-seven hand evaluator. Returns an integer: the bigger, the better.
//
//   score = category << 20 | tie-break nibbles
//   categories: 0 high card, 1 pair, 2 two pair, 3 trips, 4 straight, 5 flush, 6 full house, 7 quads, 8 straight flush

export const CATEGORY = { HIGH: 0, PAIR: 1, TWO_PAIR: 2, TRIPS: 3, STRAIGHT: 4, FLUSH: 5, FULL_HOUSE: 6, QUADS: 7, STRAIGHT_FLUSH: 8 };

// top rank of the best straight inside a 13-bit rank mask (bit 12 = ace), or -1. The wheel (A-2-3-4-5) has top rank 3.
const STRAIGHT_TOP = new Int8Array(8192).fill(-1);
for (let mask = 0; mask < 8192; mask++) {
    for (let top = 12; top >= 4; top--) {
        const window = 0b11111 << (top - 4);
        if ((mask & window) === window) { STRAIGHT_TOP[mask] = top; break; }
    }
    if (STRAIGHT_TOP[mask] < 0 && (mask & 0b1000000001111) === 0b1000000001111) STRAIGHT_TOP[mask] = 3;
}
export const straightTop = (mask) => STRAIGHT_TOP[mask];

const rc = new Int8Array(13);
const sc = new Int8Array(4);
const suitMask = new Int16Array(4);

export function evaluate(cards) {
    rc.fill(0); sc.fill(0); suitMask.fill(0);
    let rankMask = 0;
    for (let i = 0; i < cards.length; i++) {
        const r = cards[i] >> 2;
        const s = cards[i] & 3;
        rc[r]++; sc[s]++;
        suitMask[s] |= 1 << r;
        rankMask |= 1 << r;
    }

    let flushMask = 0;
    for (let s = 0; s < 4; s++) if (sc[s] >= 5) flushMask = suitMask[s];

    if (flushMask) {
        const top = STRAIGHT_TOP[flushMask];
        if (top >= 0) return (8 << 20) | top;
    }

    let quad = -1, trips = -1, trips2 = -1, pair1 = -1, pair2 = -1, pair3 = -1;
    for (let r = 12; r >= 0; r--) {
        const n = rc[r];
        if (n === 4) { if (quad < 0) quad = r; }
        else if (n === 3) { if (trips < 0) trips = r; else if (trips2 < 0) trips2 = r; }
        else if (n === 2) { if (pair1 < 0) pair1 = r; else if (pair2 < 0) pair2 = r; else if (pair3 < 0) pair3 = r; }
    }

    if (quad >= 0) {
        let kicker = -1;
        for (let r = 12; r >= 0; r--) if (r !== quad && rc[r] > 0) { kicker = r; break; }
        return (7 << 20) | (quad << 4) | kicker;
    }

    if (trips >= 0 && (pair1 >= 0 || trips2 >= 0)) {
        const second = Math.max(pair1, trips2);
        return (6 << 20) | (trips << 4) | second;
    }

    if (flushMask) return (5 << 20) | topFive(flushMask);

    const st = STRAIGHT_TOP[rankMask];
    if (st >= 0) return (4 << 20) | st;

    if (trips >= 0) {
        let k1 = -1, k2 = -1;
        for (let r = 12; r >= 0; r--) {
            if (r === trips || rc[r] === 0) continue;
            if (k1 < 0) k1 = r; else { k2 = r; break; }
        }
        return (3 << 20) | (trips << 8) | (k1 << 4) | k2;
    }

    if (pair2 >= 0) {
        let kicker = -1;
        for (let r = 12; r >= 0; r--) if (r !== pair1 && r !== pair2 && rc[r] > 0) { kicker = r; break; }
        return (2 << 20) | (pair1 << 8) | (pair2 << 4) | kicker;
    }

    if (pair1 >= 0) {
        let k1 = -1, k2 = -1, k3 = -1;
        for (let r = 12; r >= 0; r--) {
            if (r === pair1 || rc[r] === 0) continue;
            if (k1 < 0) k1 = r; else if (k2 < 0) k2 = r; else { k3 = r; break; }
        }
        return (1 << 20) | (pair1 << 12) | (k1 << 8) | (k2 << 4) | k3;
    }

    return topFive(rankMask);
}

/** The five highest ranks of a mask packed as nibbles (a single rank per nibble). */
function topFive(mask) {
    let out = 0, n = 0;
    for (let r = 12; r >= 0 && n < 5; r--) {
        if (mask & (1 << r)) { out = (out << 4) | r; n++; }
    }
    return out;
}

export const categoryOfScore = (score) => score >> 20;
