// Alpine components for the calculators embedded in blog posts ([calculator:ev], [calculator:winrate],
// [calculator:variance]). Inputs are plain numbers; percentages are typed as 0-100 and divided here.

import { betEv, callEv, winRate, variance, simulateRuns } from './math.js';

const lang = () => document.documentElement.lang || 'en';
const num = (value) => (value === '' || value === null || value === undefined || !Number.isFinite(Number(value)) ? null : Number(value));

export function format(value, digits = 2) {
    if (value === null || value === undefined || !Number.isFinite(value)) return '—';

    return new Intl.NumberFormat(lang(), { maximumFractionDigits: digits, minimumFractionDigits: 0 }).format(value);
}

export function signed(value, digits = 2) {
    if (value === null || value === undefined || !Number.isFinite(value)) return '—';

    return (value > 0 ? '+' : '') + format(value, digits);
}

export function percent(fraction, digits = 1) {
    if (fraction === null || fraction === undefined || !Number.isFinite(fraction)) return '—';

    return new Intl.NumberFormat(lang(), { style: 'percent', maximumFractionDigits: digits }).format(fraction);
}

const helpers = { format, signed, percent };

const CHART = { width: 640, height: 260, left: 64, right: 12, top: 12, bottom: 28 };

export function registerCalculators(Alpine) {
    Alpine.data('evCalculator', () => ({
        ...helpers,
        mode: 'bet',
        pot: 100, bet: 66, fold: 40, raise: 5, equity: 30, checkEquity: 35,
        callPot: 150, toCall: 50, callEquity: 30,

        get betResult() {
            return betEv({
                pot: num(this.pot) ?? 0, bet: num(this.bet) ?? 0,
                fold: (num(this.fold) ?? 0) / 100, raise: (num(this.raise) ?? 0) / 100,
                equity: (num(this.equity) ?? 0) / 100, checkEquity: (num(this.checkEquity) ?? 0) / 100,
            });
        },

        get callResult() {
            return callEv({ pot: num(this.callPot) ?? 0, toCall: num(this.toCall) ?? 0, equity: (num(this.callEquity) ?? 0) / 100 });
        },
    }));

    Alpine.data('winrateCalculator', () => ({
        ...helpers,
        unit: 'bb', net: 1500, hands: 40000, bigBlind: 0.5, stdDev: 90, handsPerHour: '',

        get result() {
            return winRate({
                net: num(this.net) ?? 0,
                hands: num(this.hands) ?? 0,
                bigBlind: this.unit === 'money' ? num(this.bigBlind) : null,
                stdDev: num(this.stdDev),
                handsPerHour: num(this.handsPerHour),
            });
        },

        // 'winner', 'loser' or 'unclear' at 95 % confidence.
        get verdict() {
            const interval = this.result?.interval;
            if (!interval) return null;
            if (interval.low > 0) return 'winner';
            if (interval.high < 0) return 'loser';

            return 'unclear';
        },
    }));

    Alpine.data('varianceCalculator', () => ({
        ...helpers,
        winRate: 5, stdDev: 90, hands: 100000, bankroll: 3000,
        runs: [],
        chart: CHART,

        init() {
            this.simulate();
            this.$watch('winRate', () => this.simulate());
            this.$watch('stdDev', () => this.simulate());
            this.$watch('hands', () => this.simulate());
        },

        get inputs() {
            return { winRate: num(this.winRate) ?? 0, stdDev: Math.max(0, num(this.stdDev) ?? 0), hands: Math.min(10000000, Math.max(0, num(this.hands) ?? 0)) };
        },

        get result() {
            return variance({ ...this.inputs, bankroll: num(this.bankroll) });
        },

        simulate() {
            const { winRate: wr, stdDev, hands } = this.inputs;
            this.runs = hands > 0 ? simulateRuns({ winRate: wr, stdDev, hands, paths: 5, steps: 100 }) : [];
        },

        // Everything the SVG needs: y range, the expected line, the 68 % and 95 % bands and the simulated runs.
        get plot() {
            const { winRate: wr, stdDev, hands } = this.inputs;
            const steps = 100;
            const points = Array.from({ length: steps + 1 }, (_, i) => i / steps);
            const mean = (t) => (wr * hands * t) / 100;
            const sigma = (t) => stdDev * Math.sqrt((hands * t) / 100);

            const values = [0, mean(1) + 1.96 * sigma(1), mean(1) - 1.96 * sigma(1), ...this.runs.flat()];
            let min = Math.min(...values);
            let max = Math.max(...values);
            if (max - min < 1) { max += 1; min -= 1; }
            const pad = (max - min) * 0.05;
            min -= pad; max += pad;

            const w = CHART.width - CHART.left - CHART.right;
            const h = CHART.height - CHART.top - CHART.bottom;
            const x = (t) => CHART.left + t * w;
            const y = (v) => CHART.top + (1 - (v - min) / (max - min)) * h;
            const line = (fn) => points.map((t, i) => `${i ? 'L' : 'M'}${x(t).toFixed(1)},${y(fn(t)).toFixed(1)}`).join(' ');
            const band = (z) => {
                const upper = points.map((t) => `${x(t).toFixed(1)},${y(mean(t) + z * sigma(t)).toFixed(1)}`);
                const lower = points.slice().reverse().map((t) => `${x(t).toFixed(1)},${y(mean(t) - z * sigma(t)).toFixed(1)}`);

                return [...upper, ...lower].join(' ');
            };

            return {
                expected: line(mean),
                band68: band(1),
                band95: band(1.96),
                runs: this.runs.map((run, index) => ({ index, d: run.map((v, i) => `${i ? 'L' : 'M'}${x(i / steps).toFixed(1)},${y(v).toFixed(1)}`).join(' ') })),
                zeroY: y(0),
                ticks: [max - pad, (max + min) / 2, min + pad].map((v) => ({ y: y(v), label: format(v, 0) })),
                left: CHART.left, right: CHART.width - CHART.right, bottom: CHART.height - CHART.bottom,
            };
        },
    }));
}
