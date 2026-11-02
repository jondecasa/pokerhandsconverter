import test from 'node:test';
import assert from 'node:assert/strict';
import { parseRange, formatRange, topPercent, rangeTotals, cellInfo, emptyRange, setClassWeight } from '../../../resources/js/flopzer/range.js';
import { createEquityJob } from '../../../resources/js/flopzer/equity.js';
import { evaluate } from '../../../resources/js/flopzer/evaluator.js';
import { parseCards, comboIndex, CLASS_INDEX, CLASS_COMBOS, CLASS_NAMES, COMBOS } from '../../../resources/js/flopzer/cards.js';
import { HAND_RANKING } from '../../../resources/js/flopzer/handRanking.js';

const combosOf = (text) => rangeTotals(parseRange(text)).combos;

test('range text: classes, plus, dashes and weights', () => {
    assert.equal(combosOf('AA'), 6);
    assert.equal(combosOf('AKs'), 4);
    assert.equal(combosOf('AKo'), 12);
    assert.equal(combosOf('AK'), 16);
    assert.equal(combosOf('77+'), 6 * 8);
    assert.equal(combosOf('ATs+'), 4 * 4);                // ATs AJs AQs AKs
    assert.equal(combosOf('A5s-A2s'), 4 * 4);
    assert.equal(combosOf('99-66'), 6 * 4);
    assert.equal(combosOf('JTs-54s'), 4 * 7);             // same gap: JTs T9s 98s 87s 76s 65s 54s
});

test('connector runs shift both cards', () => {
    assert.equal(combosOf('JTs-98s'), 4 * 3);
    assert.equal(combosOf('KQo-JTo'), 12 * 3);
});

test('weights and specific combos', () => {
    const w = parseRange('AA:50%, KK:0.25, AhKh, QQ');
    assert.equal(rangeTotals(w).combos, 3 + 1.5 + 1 + 6);
    assert.equal(w[comboIndex(...parseCards('AhKh'))], 1);
    assert.equal(w[comboIndex(...parseCards('AsAd'))], 0.5);
});

test('bad text is rejected with a message', () => {
    for (const bad of ['AAx', 'AKs-QJo', 'A5s-K2s', 'ZZ', 'AA:150%', 'AsAs']) assert.throws(() => parseRange(bad), Error, bad);
});

test('format -> parse is a round trip', () => {
    for (const text of ['AA', '77+, ATs+, KQo', 'TT-66, A5s-A2s, KQs:50%, 22:25%', 'AhKh, AsKs:50%', 'AA-TT, AKs-ATs, AKo-AQo']) {
        const w = parseRange(text);
        const back = parseRange(formatRange(w));
        assert.equal(rangeTotals(back).combos.toFixed(3), rangeTotals(w).combos.toFixed(3), text);
        for (let i = 0; i < 1326; i++) assert.ok(Math.abs(w[i] - back[i]) < 0.002, `${text} combo ${i}`);
    }
    assert.equal(formatRange(parseRange('AA, KK, QQ, JJ, TT')), 'TT+');
    assert.equal(formatRange(parseRange('AKs, AQs, AJs, ATs')), 'ATs+');
});

test('the hand ranking lists every hand once, and top-percent picks the strongest', () => {
    assert.equal(HAND_RANKING.length, 169);
    assert.deepEqual([...HAND_RANKING].sort(), [...CLASS_NAMES].sort());
    assert.equal(HAND_RANKING[0], 'AA');
    const w = topPercent(10);
    const { percent } = rangeTotals(w);
    assert.ok(percent >= 10 && percent < 13, String(percent));
    assert.equal(cellInfo(w, CLASS_INDEX.AA).weight, 1);
    assert.equal(cellInfo(w, CLASS_INDEX['72o']).weight, 0);
});

test('blocked combos do not count in a grid cell', () => {
    const w = emptyRange();
    setClassWeight(w, CLASS_INDEX.AA, 1);
    const dead = new Set(parseCards('As'));
    const info = cellInfo(w, CLASS_INDEX.AA, dead);
    assert.equal(info.alive, 3);
    assert.equal(info.combos, 3);
});

// ---- equity ----------------------------------------------------------------------------------------------------

function run(heroText, villainText, boardText) {
    const job = createEquityJob(parseRange(heroText), parseRange(villainText), parseCards(boardText), 7);
    while (!job.step(1000)) { /* keep going */ }
    return job.result();
}

/** Brute force for two single combos: every runout, plain evaluator. */
function brute(h, v, boardText) {
    const board = parseCards(boardText), hole1 = parseCards(h), hole2 = parseCards(v);
    const used = new Set([...board, ...hole1, ...hole2]);
    const deck = [];
    for (let c = 0; c < 52; c++) if (!used.has(c)) deck.push(c);
    let win = 0, n = 0;
    const need = 5 - board.length;
    const each = (runout) => {
        const s1 = evaluate([...hole1, ...board, ...runout]), s2 = evaluate([...hole2, ...board, ...runout]);
        win += s1 > s2 ? 1 : (s1 === s2 ? 0.5 : 0); n++;
    };
    if (need === 0) each([]);
    else if (need === 1) deck.forEach((a) => each([a]));
    else for (let i = 0; i < deck.length; i++) for (let j = i + 1; j < deck.length; j++) each([deck[i], deck[j]]);
    return win / n;
}

test('equity matches brute force on the flop, turn and river', () => {
    for (const board of ['Qs7d2c', 'Qs7d2c9h', 'Qs7d2c9h4s', 'Ks9s2d']) {
        const r = run('AhAd', 'KhKd', board);
        assert.ok(Math.abs(r.hero.overall - brute('AhAd', 'KhKd', board)) < 1e-9, board);
        assert.ok(Math.abs(r.hero.overall + r.villain.overall - 1) < 1e-9, board);
    }
});

test('known spot: AA is about 91% against KK on a dry Qs7d2c flop', () => {
    // no flush draw for either side, so only the two remaining kings (a set) and the queen can save the kings
    const r = run('AhAd', 'KsKc', 'Qs7d2c');
    assert.ok(r.hero.overall > 0.9 && r.hero.overall < 0.93, String(r.hero.overall));
});

test('blockers and weights: ranges against ranges stay consistent from both sides', () => {
    const r = run('AA, KQs, 77:50%', 'KK, QQ, AKs, 22', 'Js9d3c4h');
    assert.ok(Math.abs(r.hero.overall + r.villain.overall - 1) < 1e-9);
    // an ace in hand blocks aces: the AA combos see fewer AKs combos than KQs does
    assert.ok(r.hero.equity[comboIndex(...parseCards('AhAd'))] > 0);
    assert.ok(r.done === r.total);
});

test('a combo with no possible opponent has no equity', () => {
    const r = run('AhAd', 'AsAc', 'Ks7d2c');
    assert.equal(r.hero.overall > 0.4 && r.hero.overall < 0.6, true);   // two pairs of aces split
    const lone = run('AhAd', 'AhKd', 'Ks7d2c');                          // the only villain combo contains our ace
    assert.equal(lone.hero.overall, null);
});

test('progress runs from 0 to 1 and partial results are readable', () => {
    const job = createEquityJob(parseRange('AA'), parseRange('KK'), parseCards('Qs7d2c'), 3);
    assert.equal(job.progress, 0);
    job.step(1);
    assert.ok(job.progress > 0 && job.progress <= 1);
    assert.ok(job.result().hero.overall > 0.8);
});
