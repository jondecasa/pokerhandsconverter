// Pure helpers for the "More analysis" block: the five groups of the pie chart and the equity curve of a range.

/** Pie slices, strongest first. */
export const GROUPS = [
    { key: 'better', label: 'Better than a pair', color: '#facc15' },
    { key: 'strong', label: 'Strong pair', color: '#eab308' },
    { key: 'other', label: 'Other pair', color: '#a16207' },
    { key: 'draw', label: 'Draw', color: '#713f12' },
    { key: 'nothing', label: 'Nothing', color: '#cbd5e1' },
];

const BETTER = new Set(['straightFlush', 'quads', 'fullHouse', 'nutFlush', 'otherFlush', 'straight', 'pocketSet', 'trips',
    'twoPairTop', 'twoPairTopLow', 'twoPairLow', 'pocketBoardPair', 'pairBoardPair']);
const STRONG = new Set(['overpair', 'topPairTK', 'topPairGK']);
const OTHER = new Set(['topPairWK', 'middleCard', 'ppAbove2nd', 'ppBelow2nd', 'bottomPair', 'ppBelowBoard']);
// real draws: backdoor draws are too far from a made hand to count
const DRAWS = new Set(['nutFlushDraw', 'otherFlushDraw', 'oesd2', 'oesd1', 'gutshot2', 'gutshot1', 'flushDrawOesd', 'flushDrawGutshot']);

/**
 * The pie slice of a hand, from its made-hand bucket and its draw (null when it has none). A pair with a draw is still a
 * pair; hands that only play what is on the board (board trips, board two pair, the board itself) count as nothing: their
 * cards add nothing.
 */
export function groupOf(made, draw) {
    if (BETTER.has(made)) return 'better';
    if (STRONG.has(made)) return 'strong';
    if (OTHER.has(made)) return 'other';
    if (draw && DRAWS.has(draw)) return 'draw';
    return 'nothing';
}

/** CSS conic-gradient for a list of {percent, color} slices. */
export function conicGradient(slices) {
    let from = 0;
    const stops = [];
    for (const s of slices) {
        if (s.percent <= 0) continue;
        stops.push(`${s.color} ${from.toFixed(2)}% ${(from + s.percent).toFixed(2)}%`);
        from += s.percent;
    }
    return stops.length ? `conic-gradient(${stops.join(', ')})` : '#e2e8f0';
}

/**
 * The equity curve of a range: its hands from the least to the most equity, each as a step as wide as its weight.
 * @param {Array<[number, number]>} items [equity 0..1, weight]
 * @returns {Array<{x0: number, x1: number, y: number}>} x as a share 0..1 of the range, y as equity 0..1
 */
export function curveSteps(items) {
    const sorted = items.filter(([, w]) => w > 0).sort((a, b) => a[0] - b[0]);
    const total = sorted.reduce((s, [, w]) => s + w, 0);
    if (!total) return [];
    let cumulative = 0;
    return sorted.map(([equity, weight]) => {
        const x0 = cumulative / total;
        cumulative += weight;
        return { x0, x1: cumulative / total, y: equity };
    });
}

/** SVG path of the steps inside a plot area: x from left to left + width, y from bottom (0) up to bottom - height (1). */
export function stepsPath(steps, { left, width, bottom, height }) {
    let d = '';
    steps.forEach((s, i) => {
        const y = (bottom - s.y * height).toFixed(1);
        d += `${i === 0 ? 'M' : 'L'}${(left + s.x0 * width).toFixed(1)},${y} L${(left + s.x1 * width).toFixed(1)},${y} `;
    });
    return d.trim();
}
