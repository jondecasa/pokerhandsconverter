import test from 'node:test';
import assert from 'node:assert/strict';
import { classify, MADE_ROWS, DRAW_ROWS, COMBO_ROWS, madeRowOf, drawRowOf } from '../../../resources/js/flopzer/categorize.js';
import { parseCards, COMBOS } from '../../../resources/js/flopzer/cards.js';

const made = (hole, board) => classify(parseCards(hole), parseCards(board)).made;
const draw = (hole, board) => classify(parseCards(hole), parseCards(board)).draw;

test('pairs on a dry flop (Ks 7d 2c)', () => {
    const b = 'Ks7d2c';
    assert.equal(made('AhAd', b), 'overpair');
    assert.equal(made('AhKd', b), 'topPairTK');
    assert.equal(made('KhQd', b), 'topPairGK');
    assert.equal(made('Kh3d', b), 'topPairWK');
    assert.equal(made('7h6d', b), 'middleCard');
    assert.equal(made('2h3d', b), 'bottomPair');
    assert.equal(made('9s9d', b), 'ppAbove2nd');   // between the king and the seven
    assert.equal(made('5s5d', b), 'ppBelow2nd');   // under the seven, over the deuce
    assert.equal(made('3h3d', b), 'ppBelow2nd');
    assert.equal(made('7h7s', b), 'pocketSet');
    assert.equal(made('KhKd', b), 'pocketSet');
    assert.equal(made('AhQd', b), 'aceHigh');
    assert.equal(made('QhJd', b), 'lowHigh');
    assert.equal(made('Kh7h', b), 'twoPairTop');
    assert.equal(made('Kh2h', b), 'twoPairTopLow');
    assert.equal(made('7h2h', b), 'twoPairLow');
});

test('a pocket pair under the whole board and the ace-high top pair kicker', () => {
    assert.equal(made('3h3d', 'Ks7d5c'), 'ppBelowBoard');
    assert.equal(made('AhKd', 'As7d2c'), 'topPairTK');
    assert.equal(made('AhQd', 'As7d2c'), 'topPairGK');
    assert.equal(made('Ah3d', 'As7d2c'), 'topPairWK');
});

test('turn boards: "middle" covers every card between the top and the lowest', () => {
    const b = 'Ks9d7c3h';
    assert.equal(made('9h2d', b), 'middleCard');
    assert.equal(made('7h2d', b), 'middleCard');
    assert.equal(made('3s2d', b), 'bottomPair');
    assert.equal(made('8h8d', b), 'ppBelow2nd');     // under the nine, over the three
    assert.equal(made('2h2d', b), 'ppBelowBoard');
});

test('paired boards', () => {
    assert.equal(made('Kh7s', 'Kd7d2c'), 'twoPairTop');
    assert.equal(made('Ah2s', 'Kd7d7c'), 'aceHigh');            // the board pair is all there is
    assert.equal(made('Kh2s', 'Kd7d7c'), 'pairBoardPair');
    assert.equal(made('JhJs', 'Kd7d7c'), 'pocketBoardPair');
    assert.equal(made('7h2s', 'Kd7d7c'), 'trips');
    assert.equal(made('Ah2s', 'Kd7d7c7h'), 'boardTrips');
    assert.equal(made('KhKs', 'Kd7d7c'), 'fullHouse');
    assert.equal(made('5h3s', 'KdKc9h9d'), 'boardTwoPair');       // both pairs are on the board
    assert.equal(made('5h3s', 'Kd7dKc9h'), 'lowHigh');            // one board pair and nothing of ours
});

test('big hands', () => {
    assert.equal(made('AhKh', 'QhJhTh'), 'straightFlush');
    assert.equal(made('AhKd', 'QhJhTc'), 'straight');
    assert.equal(made('Ah2h', 'KhJh8h'), 'nutFlush');
    assert.equal(made('Qh2h', 'KhJh8h'), 'otherFlush');
    assert.equal(made('7h7d', 'Kd7c7s'), 'quads');
});

test('on the river a hand that is just the board is flagged', () => {
    assert.equal(made('2d3d', 'AsKsQsJsTs'), 'playsBoard');
    assert.equal(made('2d3d', 'Ah9h9d3c3s'), 'fullHouse');
    assert.equal(made('3d2d', 'Ah9h9d3c5s'), 'pairBoardPair');
    assert.equal(made('4d2d', 'Ah9h9d3c3s'), 'playsBoard');
    assert.equal(draw('AhKh', 'QhJhTc2s9d'), null);
});

test('rows: every fine bucket has a row, and the row of a bucket is what the table sums', () => {
    assert.equal(madeRowOf('topPairTK'), 'topPair');
    assert.equal(madeRowOf('ppAbove2nd'), 'middlePair');
    assert.equal(madeRowOf('overpair'), 'overpair');
    assert.equal(madeRowOf('aceHigh'), 'noMade');
    assert.equal(drawRowOf('nutFlushDraw'), 'flushDraw');
    assert.equal(drawRowOf('bdfd1'), 'backdoorFd');
    assert.equal(drawRowOf('oesdPair'), 'oesdPair');
    const info = classify(parseCards('AhKd'), parseCards('As7d2c'));
    assert.deepEqual([info.made, info.madeRow], ['topPairTK', 'topPair']);
});

test('flush draws, with and without the nuts', () => {
    assert.equal(draw('AsQs', 'Ks7s2c'), 'nutFlushDraw');
    assert.equal(draw('QsJs', 'Ks7s2c'), 'otherFlushDraw');
    assert.equal(draw('AsQd', 'Ks7s2s'), 'nutFlushDraw');       // one card of the suit is enough
});

test('straight draws: open-ended and gutshot, leaning on one or two hole cards', () => {
    assert.equal(draw('7h6d', '9h8d2c'), 'oesd2');
    assert.equal(draw('JhTh', '9h8d2c'), 'oesd2');
    assert.equal(draw('Td2c', '9h8d7c'), 'oesd1');             // the board holds 7-8-9, the ten extends it
    assert.equal(draw('7h5d', '9h8d2c'), 'gutshot2');
    assert.equal(draw('Kd5c', '9h8d6c'), 'gutshot1');          // 5-6-8-9 needs the seven
    assert.equal(draw('9d8c', 'Jh7s5c'), 'oesd2');             // double gutshot: eight outs count as open-ended
    assert.equal(draw('5d4c', 'Kh3s2h'), 'oesd2');             // 2-3-4-5: ace and six
    assert.equal(draw('AdKc', 'QsJh2d'), 'gutshot2');          // only the ten
    assert.equal(draw('Ad5c', '3s2h9d'), 'gutshot2');          // A-2-3-5 needs the four
    assert.equal(made('5d4c', 'Ah3s2h'), 'straight');          // the wheel is already made
});

test('combinations', () => {
    assert.equal(draw('7h6h', '9h8h2c'), 'flushDrawOesd');
    assert.equal(draw('Jh9h', '8h7h2c'), 'flushDrawGutshot');
    assert.equal(draw('Ks3s', 'Kd7s2s'), 'flushDrawPair');     // top pair + flush draw
    assert.equal(draw('9d8c', '9s7h6c'), 'oesdPair');          // pair of nines + 6-7-8-9
    assert.equal(draw('Jd9c', 'Js8h7c'), 'gutshotPair');       // pair of jacks + the ten
    assert.equal(draw('Jh9h', '8h7s2d'), 'gutshotBdfd');       // gutshot with a backdoor flush draw
});

test('backdoor flush draws, one or two cards of the suit, and one overcard with a backdoor', () => {
    assert.equal(draw('KhQh', '9h8d2c'), 'bdfd2');
    assert.equal(draw('3h4s', '9h8h2c'), 'bdfd1');             // one heart in hand, two on the board
    assert.equal(draw('Ah3h', '9h8d2c'), 'overBdfd');          // an ace over the board
    assert.equal(draw('AhKh', '9h8d2c'), 'bdfd2');             // two overcards are not "one overcard"
    assert.equal(draw('AhAs', 'Qh9h4d'), 'bdfd1');             // an overpair can still have a backdoor
});

test('hands with nothing to draw to', () => {
    assert.equal(draw('Qd3c', 'Kc7d2h'), null);
    assert.equal(draw('AdKc', '9h8d2c'), null);                 // overcards alone are not a draw
    assert.equal(draw('Ah2h', 'KhJh8h'), null);                 // already a flush
    assert.equal(draw('Ah2h', 'KhJh8h3d'), null);
});

test('every bucket classify returns is declared, for every combo on a few boards', () => {
    const made = new Set(MADE_ROWS.flatMap((r) => (r.kids ? r.kids.map((k) => k.key) : [r.key])));
    const draws = new Set([...DRAW_ROWS, ...COMBO_ROWS].flatMap((r) => (r.kids ? r.kids.map((k) => k.key) : [r.key])));
    for (const board of ['Ks7d2c', '9h8h7h', 'AsAdAc', 'Ks7d2c9h', 'Ks7d2c9h4s', '2c2d2h2s3d', 'Qh9h4d']) {
        const cards = parseCards(board);
        for (const [c1, c2] of COMBOS) {
            if (cards.includes(c1) || cards.includes(c2)) continue;
            const info = classify([c1, c2], cards);
            assert.ok(made.has(info.made), `unknown made key ${info.made} on ${board}`);
            assert.ok(info.draw === null || draws.has(info.draw), `unknown draw key ${info.draw} on ${board}`);
            assert.ok(info.madeRow && (info.draw === null) === (info.drawRow === null));
        }
    }
});

test('finer rows keep a readable name away from their row', async () => {
    const { madeLabel, drawLabel } = await import('../../../resources/js/flopzer/categorize.js');
    assert.equal(madeLabel('topPairGK'), 'Top pair, good kicker');
    assert.equal(drawLabel('bdfd2'), 'backdoor fd (2 cards of the suit)');
    assert.equal(madeLabel('aceHigh'), 'Ace high');
    assert.equal(drawLabel('nutFlushDraw'), 'Nut flush draw');
});

test('a flush that is not the nuts is just a flush', async () => {
    const { madeLabel, drawLabel } = await import('../../../resources/js/flopzer/categorize.js');
    assert.equal(madeLabel('otherFlush'), 'Flush');
    assert.equal(madeLabel('nutFlush'), 'Nut flush');
    assert.equal(drawLabel('otherFlushDraw'), 'Flush draw');
    assert.equal(drawLabel('nutFlushDraw'), 'Nut flush draw');
});
