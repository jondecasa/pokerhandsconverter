import test from 'node:test';
import assert from 'node:assert/strict';
import { evaluate, categoryOfScore, CATEGORY } from '../../../resources/js/flopzer/evaluator.js';
import { parseCards, COMBOS, comboIndex, CLASS_NAMES, CLASS_COMBOS, classNameOf, COMBO_COUNT } from '../../../resources/js/flopzer/cards.js';

const score = (text) => evaluate(parseCards(text));

test('the 2,598,960 five-card hands fall into the known category counts', () => {
    const counts = new Array(9).fill(0);
    const hand = [0, 0, 0, 0, 0];
    for (let a = 0; a < 48; a++) for (let b = a + 1; b < 49; b++) for (let c = b + 1; c < 50; c++) for (let d = c + 1; d < 51; d++) for (let e = d + 1; e < 52; e++) {
        hand[0] = a; hand[1] = b; hand[2] = c; hand[3] = d; hand[4] = e;
        counts[categoryOfScore(evaluate(hand))]++;
    }
    assert.deepEqual(counts, [1302540, 1098240, 123552, 54912, 10200, 5108, 3744, 624, 40]);
});

test('categories are ordered from high card up to straight flush', () => {
    const ladder = ['AsKd9c7h3d', 'AsAd9c7h3d', 'AsAd9c9h3d', 'AsAdAc7h3d', '5s4d3c2hAd', 'As9s7s4s2s', 'AsAdAc7h7d', 'AsAdAcAh3d', '9s8s7s6s5s'];
    const scores = ladder.map(score);
    for (let i = 1; i < scores.length; i++) assert.ok(scores[i] > scores[i - 1], `${ladder[i]} should beat ${ladder[i - 1]}`);
    assert.deepEqual(scores.map(categoryOfScore), [0, 1, 2, 3, 4, 5, 6, 7, 8]);
});

test('the wheel is the lowest straight and the royal flush the highest hand', () => {
    assert.ok(score('5s4d3c2hAd') < score('6s5d4c3h2d'));
    assert.ok(score('AsKsQsJsTs') > score('KsQsJsTs9s'));
    assert.equal(categoryOfScore(score('5s4s3s2sAs')), CATEGORY.STRAIGHT_FLUSH);
});

test('kickers decide between equal categories', () => {
    assert.ok(score('AsAdKc7h3d') > score('AsAdQc7h3d'));
    assert.ok(score('AsAdKcKh3d') > score('AsAdQcQh9d'));
    assert.ok(score('AsAd9c7h3d') > score('KsKd9c7h3d'));
    assert.equal(score('AsKd9c7h3d'), score('AhKc9d7s3h')); // suits never matter outside a flush
});

test('seven cards: the best five are found', () => {
    assert.equal(categoryOfScore(score('AsKsQsJs9s2d3c')), CATEGORY.FLUSH);           // flush, no straight
    assert.equal(categoryOfScore(score('AsKsQsJsTs2d3c')), CATEGORY.STRAIGHT_FLUSH);
    assert.equal(categoryOfScore(score('AsAdAcKhKd2c3d')), CATEGORY.FULL_HOUSE);
    assert.equal(categoryOfScore(score('AsAdAcKhKdKc3d')), CATEGORY.FULL_HOUSE);       // two sets: the better one is trips
    assert.ok(score('AsAdAcKhKdKc3d') > score('AsAdAcKhKd2c3d') - 1);
    assert.equal(categoryOfScore(score('AsAdKcKh9d9c3d')), CATEGORY.TWO_PAIR);        // three pairs: the best two count
    assert.equal(score('AsAdKcKh9d9c3d'), score('AsAdKcKh9d3c2d'));                     // ...the pair of nines only supplies the kicker
    assert.ok(score('AsAdKcKh9d9c3d') > score('AsAdKcKh8d3c2d'));
    assert.equal(categoryOfScore(score('As2d3c4h5d9c9d')), CATEGORY.STRAIGHT);          // wheel beats the pair of nines
    assert.equal(categoryOfScore(score('AsKd7c7h7d7s2c')), CATEGORY.QUADS);
    assert.equal(categoryOfScore(score('9s8s7s6s5sAsAd')), CATEGORY.STRAIGHT_FLUSH);    // the ace pair does not matter
});

test('a flush with six suited cards uses the best five', () => {
    assert.ok(score('AsKsQs9s4s2s3d') > score('KsQs9s4s2s3d7c'));
    assert.ok(score('AsKsQs9s4s2sJs') === score('AsKsQsJs9s4s2d'));
});

test('combos and grid classes line up', () => {
    assert.equal(COMBO_COUNT, 1326);
    assert.equal(CLASS_NAMES[0], 'AA');
    assert.equal(CLASS_NAMES[1], 'AKs');
    assert.equal(CLASS_NAMES[13], 'AKo');
    assert.equal(CLASS_NAMES[168], '22');
    assert.equal(classNameOf(12), 'A2s');
    const sizes = CLASS_COMBOS.map((c) => c.length);
    assert.equal(sizes.reduce((a, b) => a + b, 0), 1326);
    assert.equal(sizes[0], 6);   // AA
    assert.equal(sizes[1], 4);   // AKs
    assert.equal(sizes[13], 12); // AKo
    const [c1, c2] = parseCards('AsKs');
    assert.equal(CLASS_NAMES[CLASS_COMBOS.findIndex((list) => list.includes(comboIndex(c1, c2)))], 'AKs');
    assert.deepEqual(COMBOS[comboIndex(c1, c2)].slice().sort(), [c1, c2].sort());
});
