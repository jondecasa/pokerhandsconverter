import test from 'node:test';
import assert from 'node:assert/strict';
import { groupOf, GROUPS, conicGradient, curveSteps, stepsPath } from '../../../resources/js/flopzer/analysis.js';
import { MADE_ROWS, DRAW_ROWS, COMBO_ROWS } from '../../../resources/js/flopzer/categorize.js';

test('every made-hand and draw bucket lands in one of the five groups', () => {
    const keys = new Set(GROUPS.map((g) => g.key));
    const fine = (rows) => rows.flatMap((r) => (r.kids ? r.kids.map((k) => k.key) : [r.key]));
    const draws = [null, ...fine([...DRAW_ROWS, ...COMBO_ROWS])];
    for (const m of fine(MADE_ROWS)) for (const d of draws) assert.ok(keys.has(groupOf(m, d)), `${m}/${d}`);
});

test('groups: pairs beat draws, draws beat nothing, board-only hands are nothing', () => {
    assert.equal(groupOf('pocketSet', null), 'better');
    assert.equal(groupOf('twoPairTop', 'nutFlushDraw'), 'better');
    assert.equal(groupOf('overpair', null), 'strong');
    assert.equal(groupOf('topPairTK', 'oesdPair'), 'strong');       // a pair with a draw stays a pair
    assert.equal(groupOf('topPairWK', null), 'other');
    assert.equal(groupOf('middleCard', 'gutshotPair'), 'other');
    assert.equal(groupOf('ppBelowBoard', null), 'other');
    assert.equal(groupOf('aceHigh', 'nutFlushDraw'), 'draw');
    assert.equal(groupOf('lowHigh', 'oesd2'), 'draw');
    assert.equal(groupOf('kingHigh', 'overBdfd'), 'nothing');       // backdoor draws do not count
    assert.equal(groupOf('lowHigh', 'bdfd2'), 'nothing');
    assert.equal(groupOf('boardTrips', null), 'nothing');
    assert.equal(groupOf('playsBoard', null), 'nothing');
});

test('conic gradient covers the slices in order and ignores empty ones', () => {
    const css = conicGradient([{ percent: 25, color: 'red' }, { percent: 0, color: 'green' }, { percent: 75, color: 'blue' }]);
    assert.equal(css, 'conic-gradient(red 0.00% 25.00%, blue 25.00% 100.00%)');
    assert.equal(conicGradient([]), '#e2e8f0');
});

test('the equity curve goes from the weakest hand to the strongest, each as wide as its weight', () => {
    const steps = curveSteps([[0.8, 1], [0.2, 3], [0.5, 1]]);
    assert.deepEqual(steps.map((s) => s.y), [0.2, 0.5, 0.8]);
    assert.equal(steps[0].x0, 0);
    assert.equal(steps[0].x1, 3 / 5);
    assert.equal(steps[2].x1, 1);
    assert.deepEqual(curveSteps([]), []);
    assert.deepEqual(curveSteps([[0.5, 0]]), []);
});

test('the step path stays inside the plot area', () => {
    const d = stepsPath(curveSteps([[0, 1], [1, 1]]), { left: 40, width: 100, bottom: 200, height: 100 });
    assert.equal(d, 'M40.0,200.0 L90.0,200.0 L90.0,100.0 L140.0,100.0');
});
