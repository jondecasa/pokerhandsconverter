// Cards, combos and the 13x13 hand grid.
//
// A card is an integer rank * 4 + suit: rank 0 = deuce ... 12 = ace, suit 0..3 = s h d c.
// The grid has the aces in the top-left corner: pairs on the diagonal, suited hands above it, offsuit below.

export const RANKS = '23456789TJQKA';
export const SUITS = 'shdc';
export const SUIT_SYMBOLS = { s: '♠', h: '♥', d: '♦', c: '♣' };

export const rankOf = (card) => card >> 2;
export const suitOf = (card) => card & 3;

export function parseCard(text) {
    const rank = RANKS.indexOf(text[0].toUpperCase());
    const suit = SUITS.indexOf(text[1].toLowerCase());
    if (rank < 0 || suit < 0 || text.length !== 2) throw new Error(`Bad card "${text}"`);
    return rank * 4 + suit;
}

export const cardName = (card) => RANKS[card >> 2] + SUITS[card & 3];

/** "AsKd7c" or "As Kd 7c" -> [cards] */
export function parseCards(text) {
    const clean = text.replace(/[\s,]+/g, '');
    const out = [];
    for (let i = 0; i < clean.length; i += 2) out.push(parseCard(clean.slice(i, i + 2)));
    return out;
}

// ---- the 1326 combos -------------------------------------------------------------------------------------------

/** combo index -> [high card, low card] (high = larger card integer) */
export const COMBOS = [];
const COMBO_AT = new Int16Array(52 * 52).fill(-1);
for (let a = 0; a < 52; a++) {
    for (let b = a + 1; b < 52; b++) {
        COMBO_AT[a * 52 + b] = COMBO_AT[b * 52 + a] = COMBOS.length;
        COMBOS.push([b, a]);
    }
}
export const COMBO_COUNT = COMBOS.length; // 1326

export const comboIndex = (c1, c2) => COMBO_AT[c1 * 52 + c2];

// ---- the 169 hand classes (grid cells) -------------------------------------------------------------------------

/** Grid cell index = row * 13 + col, row/col 0 = ace ... 12 = deuce. */
export const CLASS_COUNT = 169;

export function classIndexOfCards(c1, c2) {
    const r1 = 12 - rankOf(c1);
    const r2 = 12 - rankOf(c2);
    const suited = suitOf(c1) === suitOf(c2);
    if (r1 === r2) return r1 * 13 + r1;
    const hi = Math.min(r1, r2); // better rank = smaller index
    const lo = Math.max(r1, r2);
    // suited above the diagonal (row = high rank, col = low rank), offsuit below it
    return suited ? hi * 13 + lo : lo * 13 + hi;
}

export function classNameOf(index) {
    const row = Math.floor(index / 13);
    const col = index % 13;
    if (row === col) return RANKS[12 - row] + RANKS[12 - row];
    return row < col ? RANKS[12 - row] + RANKS[12 - col] + 's' : RANKS[12 - col] + RANKS[12 - row] + 'o';
}

export const CLASS_NAMES = Array.from({ length: CLASS_COUNT }, (_, i) => classNameOf(i));
export const CLASS_INDEX = Object.fromEntries(CLASS_NAMES.map((n, i) => [n, i]));

/** combo index -> class index, and class index -> combo indexes */
export const COMBO_CLASS = new Int16Array(COMBO_COUNT);
export const CLASS_COMBOS = Array.from({ length: CLASS_COUNT }, () => []);
COMBOS.forEach(([c1, c2], i) => {
    const k = classIndexOfCards(c1, c2);
    COMBO_CLASS[i] = k;
    CLASS_COMBOS[k].push(i);
});

export const comboName = (index) => {
    const [c1, c2] = COMBOS[index];
    return cardName(c1) + cardName(c2);
};
