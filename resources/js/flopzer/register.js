// The Alpine component behind /flopzer. The ranges are typed arrays kept outside Alpine's reactive proxy (a proxy around
// a typed array breaks its methods); `rev` is bumped whenever one of them changes so the page recomputes.

import { COMBOS, CLASS_COUNT, CLASS_NAMES, CLASS_COMBOS, COMBO_CLASS, RANKS, SUITS, SUIT_SYMBOLS, parseCard, parseCards, cardName, comboName, comboIndex } from './cards.js';
import { MADE_ROWS, DRAW_ROWS, COMBO_ROWS, classify, madeLabel, drawLabel } from './categorize.js';
import { emptyRange, cloneRange, setClassWeight, parseRange, formatRange, topPercent, rangeTotals } from './range.js';
import { createEquityJob } from './equity.js';
import { GROUPS, groupOf, conicGradient, curveSteps, stepsPath } from './analysis.js';

const STORE = 'flopzer.v1';
const SAVED = 'flopzer.saved.v1';
const SUIT_COLORS = { s: '#334155', h: '#dc2626', d: '#2563eb', c: '#16a34a' };
// Weight levels: "No weight" is always 100 %; there are three more, each with a colour of its own and a starting weight
// that the slider can still change.
const TIER_COLORS = [[14, 165, 233], [20, 184, 166], [168, 85, 247]];
const TIER_WEIGHTS = [75, 50, 25];
const rgba = (rgb, alpha) => `rgba(${rgb[0]},${rgb[1]},${rgb[2]},${alpha})`;


const read = (key, fallback) => { try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch (e) { return fallback; } };
const write = (key, value) => { try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* private mode: nothing to keep */ } };

export function registerFlopzer(Alpine) {
    Alpine.data('flopzer', (presets = []) => {
        const ranges = { hero: emptyRange(), villain: emptyRange() };
        const eqCombo = { hero: null, villain: null };
        let history = [];
        let job = null, jobToken = 0, handJob = null, handToken = 0, hotToken = 0, eqTimer = null, saveTimer = null;
        let memo = { key: '', rows: [], byClass: null };
        let deadMemo = { key: '', set: new Set() };

        return {
            rev: 0,
            side: 'hero',
            board: [],
            deadCards: [],
            tierWeights: [...TIER_WEIGHTS], activeTier: 0,
            tierColors: TIER_COLORS.map((c) => rgba(c, 1)),
            painting: false,
            paintValue: 0,
            draft: '', draftError: '', draftFocus: false,
            top: 20,
            selMade: [], selDraw: [], exMade: [], exDraw: [], openRows: {},
            hoverClass: null,
            eq: { running: false, progress: 0, hero: null, villain: null, rev: 0 },
            myText: '', myEq: { running: false, value: null },
            saved: [], saveName: '',
            presets, presetOpen: false, presetSide: 'hero',
            showDead: false,
            analysisTab: 'matrix',
            hot: { running: false, progress: 0, values: {}, rev: 0 },
            ranks: RANKS.split('').reverse(), suits: SUITS.split(''), symbols: SUIT_SYMBOLS,
            classNames: CLASS_NAMES, classIndexes: Array.from({ length: CLASS_COUNT }, (_, i) => i),

            // ---------- setup ----------
            init() {
                const saved = read(STORE, null);
                if (saved) {
                    try {
                        if (saved.hero) ranges.hero = parseRange(saved.hero);
                        if (saved.villain) ranges.villain = parseRange(saved.villain);
                        this.board = (saved.board || []).map(parseCard).slice(0, 5);
                        this.deadCards = (saved.dead || []).map(parseCard).filter((c) => !this.board.includes(c));
                        this.myText = saved.my || '';
                        if (Array.isArray(saved.tiers) && saved.tiers.length === TIER_WEIGHTS.length) this.tierWeights = saved.tiers.map((w, i) => Math.min(99, Math.max(1, Number(w) || TIER_WEIGHTS[i])));
                        this.activeTier = Number.isInteger(saved.tier) && saved.tier >= 0 && saved.tier <= TIER_WEIGHTS.length ? saved.tier : 0;
                        this.side = saved.side === 'villain' ? 'villain' : 'hero';
                    } catch (e) { /* a broken saved state is simply ignored */ }
                } else {
                    ranges.hero = parseRange('77+, ATs+, KQs, AJo+, KQo');
                    ranges.villain = parseRange('22+, A2s+, K9s+, QTs+, JTs, ATo+, KJo+');
                    this.board = ['Ks', '7d', '2c'].map(parseCard);
                }
                this.saved = read(SAVED, []);
                this.rev++;
                this.syncDraft();
                this.scheduleEquity();
                window.addEventListener('pointerup', () => { this.painting = false; });
                window.addEventListener('pointercancel', () => { this.painting = false; });
                window.addEventListener('keydown', (e) => { if (e.key === 'Escape') this.presetOpen = false; });
            },

            get weights() { this.rev; return ranges[this.side]; },
            get otherSide() { return this.side === 'hero' ? 'villain' : 'hero'; },
            get sideLabel() { return this.side === 'hero' ? 'Your range' : 'Opponent range'; },

            persist() {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(() => write(STORE, {
                    hero: formatRange(ranges.hero), villain: formatRange(ranges.villain),
                    board: this.board.map(cardName), dead: this.deadCards.map(cardName), my: this.myText, side: this.side,
                    tiers: this.tierWeights, tier: this.activeTier,
                }), 400);
            },

            changed() {
                this.rev++;
                this.syncDraft();
                this.persist();
                this.scheduleEquity();
            },

            snapshot() {
                history.push({ side: this.side, weights: cloneRange(ranges[this.side]) });
                if (history.length > 60) history.shift();
            },
            get canUndo() { this.rev; return history.length > 0; },
            undo() {
                const last = history.pop();
                if (!last) return;
                ranges[last.side] = last.weights;
                this.side = last.side;
                this.changed();
            },

            // ---------- the grid ----------
            /** Board plus dead cards: nobody can hold them and they never come on a later street. */
            get dead() {
                const key = this.board.join(',') + '|' + this.deadCards.join(',');
                if (deadMemo.key !== key) deadMemo = { key, set: new Set([...this.board, ...this.deadCards]) };
                return deadMemo.set;
            },

            cell(k) {
                this.rev;
                const dead = this.dead;
                let alive = 0, sum = 0, match = 0;
                for (const i of CLASS_COMBOS[k]) {
                    const [a, b] = COMBOS[i];
                    if (dead.has(a) || dead.has(b)) continue;
                    alive++;
                    sum += ranges[this.side][i];
                }
                if (this.selActive) {
                    const rows = this.rowsByClass()[k];
                    if (rows) for (const r of rows) if (this.matches(r)) match += r.w;
                }
                return { alive, weight: alive ? sum / alive : 0, share: sum > 0 ? match / sum : 0 };
            },

            cellStyle(k) {
                const c = this.cell(k);
                const outline = this.hoverClass === k ? '2px solid #111827' : '';
                if (c.alive === 0) return { backgroundColor: '#e2e8f0', color: '#94a3b8', outline, backgroundImage: 'repeating-linear-gradient(45deg, transparent 0 4px, rgba(148,163,184,.35) 4px 6px)' };
                if (c.weight <= 0) return { backgroundColor: '#f1f5f9', color: '#64748b', outline };
                const alpha = 0.25 + 0.75 * c.weight;
                const tier = this.tierWeights.findIndex((tw) => Math.abs(tw / 100 - c.weight) < 0.005);
                const base = tier >= 0 ? rgba(TIER_COLORS[tier], 0.92) : `rgba(79,70,229,${alpha})`;
                const light = c.weight > 0.45;
                if (!this.selActive) return { backgroundColor: base, color: light ? '#fff' : '#312e81', outline };
                const amber = `rgba(245,158,11,${0.35 + 0.65 * c.weight})`;
                const cut = Math.round(c.share * 100);
                return {
                    background: `linear-gradient(to right, ${amber} 0%, ${amber} ${cut}%, ${base} ${cut}%, ${base} 100%)`,
                    color: light ? '#fff' : '#312e81', outline,
                };
            },

            // painting: click toggles a cell between the brush weight and empty; dragging applies the same value
            startPaint(k) {
                this.snapshot();
                const w = this.brush;
                this.paintValue = Math.abs(this.cell(k).weight - w) < 0.01 ? 0 : w;
                this.painting = true;
                this.applyPaint(k);
            },
            movePaint(event) {
                if (!this.painting) return;
                const el = document.elementFromPoint(event.clientX, event.clientY);
                const k = el && el.dataset ? el.dataset.cell : undefined;
                if (k !== undefined) this.applyPaint(Number(k));
                this.hoverClass = k !== undefined ? Number(k) : this.hoverClass;
            },
            applyPaint(k) {
                setClassWeight(ranges[this.side], k, this.paintValue);
                this.changed();
            },

            /** The weight (0..1) the next click paints: the active level's. */
            get brush() { return this.activeTier === 0 ? 1 : this.tierWeights[this.activeTier - 1] / 100; },
            get brushPercent() { return Math.round(this.brush * 100); },
            setTier(i) { this.activeTier = i; this.persist(); },
            setTierWeight(value) {
                if (this.activeTier === 0) return;
                const w = Math.min(99, Math.max(1, Math.round(Number(value)) || 1));
                this.tierWeights = this.tierWeights.map((x, i) => (i === this.activeTier - 1 ? w : x));
                this.persist();
            },

            /** The weights present in the range on show, with how many combos carry each. */
            get weightLegend() {
                this.rev;
                const dead = this.dead, w = ranges[this.side], seen = new Map();
                for (let i = 0; i < w.length; i++) {
                    if (w[i] <= 0) continue;
                    const [a, b] = COMBOS[i];
                    if (dead.has(a) || dead.has(b)) continue;
                    const key = Math.round(w[i] * 100);
                    seen.set(key, (seen.get(key) || 0) + 1);
                }
                return [...seen.entries()].sort((x, y) => y[0] - x[0]).map(([percent, combos]) => {
                    const tier = this.tierWeights.findIndex((tw) => tw === percent);
                    return { percent, combos, color: tier >= 0 ? rgba(TIER_COLORS[tier], 1) : 'rgb(79,70,229)' };
                });
            },

            get totals() { this.rev; return rangeTotals(ranges[this.side], this.dead); },
            totalsOf(side) { this.rev; return rangeTotals(ranges[side], this.dead); },

            // ---------- range tools ----------
            syncDraft() { if (!this.draftFocus) { this.draft = formatRange(ranges[this.side]); this.draftError = ''; } },
            applyDraft() {
                try {
                    const w = parseRange(this.draft);
                    this.snapshot();
                    ranges[this.side] = w;
                    this.draftError = '';
                    this.draftFocus = false;
                    this.changed();
                } catch (e) { this.draftError = e.message; }
            },
            clearRange() { this.snapshot(); ranges[this.side] = emptyRange(); this.changed(); },
            fillRange() { this.snapshot(); ranges[this.side] = topPercent(100); this.changed(); },
            applyTop() { this.snapshot(); ranges[this.side] = topPercent(Number(this.top)); this.changed(); },
            copyOver() { this.snapshot(); ranges[this.otherSide] = cloneRange(ranges[this.side]); this.rev++; this.persist(); this.scheduleEquity(); },
            switchSide(side) { this.side = side; this.clearSelection(); this.syncDraft(); this.persist(); this.scheduleEquity(); },

            /** Quick picks added with the brush weight: pocket pairs, broadway hands (both cards T or better), suited hands. */
            quick(kind) {
                this.snapshot();
                const w = this.brush;
                CLASS_NAMES.forEach((name, k) => {
                    const hi = RANKS.indexOf(name[0]), lo = RANKS.indexOf(name[1]);
                    const hit = kind === 'pocket' ? name.length === 2 : (kind === 'broadway' ? hi >= 8 && lo >= 8 : name.endsWith('s'));
                    if (hit) setClassWeight(ranges[this.side], k, w);
                });
                this.changed();
            },

            // ranges from the Ranges section (open-raise charts)
            openPresets(side) { this.presetSide = side; this.presetOpen = true; },
            loadPreset(option) {
                this.side = this.presetSide;
                this.snapshot();
                ranges[this.side] = parseRange(option.text);
                this.clearSelection();
                this.presetOpen = false;
                this.changed();
            },

            saveRange() {
                const name = this.saveName.trim();
                if (!name) return;
                this.saved = [...this.saved.filter((s) => s.name !== name), { name, text: formatRange(ranges[this.side]) }].sort((a, b) => a.name.localeCompare(b.name));
                write(SAVED, this.saved);
                this.saveName = '';
            },
            loadRange(item) { this.snapshot(); ranges[this.side] = parseRange(item.text); this.changed(); },
            deleteSaved(item) { this.saved = this.saved.filter((s) => s !== item); write(SAVED, this.saved); },

            // ---------- the board and the dead cards ----------
            get dealt() { return this.board.map((c) => ({ card: c, name: cardName(c), rank: RANKS[c >> 2], suit: SUITS[c & 3], color: SUIT_COLORS[SUITS[c & 3]] })); },
            get street() { return ['Preflop', 'Preflop', 'Preflop', 'Flop', 'Turn', 'River'][this.board.length]; },
            onBoard(rank, suit) { return this.board.includes(parseCard(rank + suit)); },
            isDead(rank, suit) { return this.deadCards.includes(parseCard(rank + suit)); },
            toggleCard(rank, suit) {
                const c = parseCard(rank + suit);
                this.deadCards = this.deadCards.filter((x) => x !== c);
                this.board = this.board.includes(c) ? this.board.filter((x) => x !== c) : (this.board.length < 5 ? [...this.board, c] : this.board);
                this.boardChanged();
            },
            toggleDead(rank, suit) {
                const c = parseCard(rank + suit);
                if (this.board.includes(c)) return;
                this.deadCards = this.deadCards.includes(c) ? this.deadCards.filter((x) => x !== c) : [...this.deadCards, c];
                this.boardChanged();
            },
            clearDead() { this.deadCards = []; this.boardChanged(); },
            removeCard(card) { this.board = this.board.filter((c) => c !== card); this.boardChanged(); },
            clearBoard() { this.board = []; this.boardChanged(); },
            randomFlop() {
                const deck = Array.from({ length: 52 }, (_, i) => i).filter((c) => !this.deadCards.includes(c));
                for (let i = 0; i < 3; i++) { const j = i + Math.floor(Math.random() * (deck.length - i)); [deck[i], deck[j]] = [deck[j], deck[i]]; }
                this.board = deck.slice(0, 3);
                this.boardChanged();
            },
            randomCard() {
                if (this.board.length < 3 || this.board.length >= 5) return;
                const free = Array.from({ length: 52 }, (_, i) => i).filter((c) => !this.board.includes(c) && !this.deadCards.includes(c));
                this.board = [...this.board, free[Math.floor(Math.random() * free.length)]];
                this.boardChanged();
            },
            boardChanged() { this.clearSelection(); this.changed(); },

            // ---------- the breakdown ----------
            get ready() { return this.board.length >= 3; },

            /** Every live combo of the range on the shown side, classified. */
            get rows() {
                this.rev;
                if (!this.ready) return [];
                const key = this.side + '|' + this.rev + '|' + this.board.join(',') + '|' + this.deadCards.join(',');
                if (memo.key === key) return memo.rows;
                const dead = this.dead, w = ranges[this.side], out = [];
                for (let i = 0; i < w.length; i++) {
                    if (w[i] <= 0) continue;
                    const [a, b] = COMBOS[i];
                    if (dead.has(a) || dead.has(b)) continue;
                    out.push({ i, w: w[i], ...classify([a, b], this.board) });
                }
                memo = { key, rows: out, byClass: null };
                return out;
            },
            rowsByClass() {
                const rows = this.rows;
                if (!memo.byClass) {
                    memo.byClass = {};
                    for (const r of rows) (memo.byClass[COMBO_CLASS[r.i]] ??= []).push(r);
                }
                return memo.byClass;
            },

            /**
             * One section of the table as a flat list: each row with its combos, its share of the range and its equity against
             * the other range, and, under a row that is open, its finer rows.
             */
            flatRows(defs, group) {
                const rows = this.rows;
                const total = rows.reduce((s, r) => s + r.w, 0);
                this.eq.rev;
                const eqs = eqCombo[this.side];
                const acc = new Map();
                const add = (key, r) => {
                    const a = acc.get(key) ?? { combos: 0, en: 0, ed: 0 };
                    a.combos += r.w;
                    if (eqs && eqs[r.i] >= 0) { a.en += r.w * eqs[r.i]; a.ed += r.w; }
                    acc.set(key, a);
                };
                const fineOf = group === 'made' ? 'made' : 'draw', rowOf = group === 'made' ? 'madeRow' : 'drawRow';
                for (const r of rows) {
                    const fine = r[fineOf];
                    if (fine === null) continue;
                    add(fine, r);
                    if (r[rowOf] !== fine) add(r[rowOf], r);
                }
                const line = (item, depth, kids) => {
                    const a = acc.get(item.key);
                    return a ? {
                        key: item.key, label: item.label, group, depth, hasKids: kids > 0, open: !!this.openRows[item.key],
                        combos: a.combos, percent: total ? (100 * a.combos) / total : 0, equity: a.ed > 0 ? (100 * a.en) / a.ed : null,
                    } : null;
                };
                const out = [];
                for (const def of defs) {
                    const kids = (def.kids ?? []).filter((k) => acc.has(k.key));
                    const head = line(def, 0, kids.length);
                    if (!head) continue;
                    out.push(head);
                    if (head.open) for (const k of kids) out.push(line(k, 1, 0));
                }
                return out;
            },
            get tableSections() {
                const sections = [{ id: 'made', title: 'Made hands', group: 'made', rows: this.flatRows(MADE_ROWS, 'made') }];
                if (this.board.length < 5) {
                    sections.push({ id: 'draw', title: 'Draws', group: 'draw', rows: this.flatRows(DRAW_ROWS, 'draw') });
                    sections.push({ id: 'combo', title: 'Combinations', group: 'draw', rows: this.flatRows(COMBO_ROWS, 'draw') });
                }
                return sections;
            },
            toggleOpen(key) { this.openRows = { ...this.openRows, [key]: !this.openRows[key] }; },

            // ---------- filtering by rows: ✓ keeps only those hands, ✗ leaves them out ----------
            get selActive() { return this.selMade.length + this.selDraw.length + this.exMade.length + this.exDraw.length > 0; },
            rowState(group, key) {
                const [inc, exc] = group === 'made' ? [this.selMade, this.exMade] : [this.selDraw, this.exDraw];
                return inc.includes(key) ? 'in' : (exc.includes(key) ? 'out' : null);
            },
            setRow(group, key, state) {
                const inc = group === 'made' ? 'selMade' : 'selDraw', exc = group === 'made' ? 'exMade' : 'exDraw';
                const current = this.rowState(group, key);
                this[inc] = this[inc].filter((k) => k !== key);
                this[exc] = this[exc].filter((k) => k !== key);
                if (current !== state) { const list = state === 'in' ? inc : exc; this[list] = [...this[list], key]; }
            },
            /** ✓ or ✗ on a whole section: 'in' / 'out' when every one of its rows is in that state, otherwise null. */
            sectionState(section) {
                const rows = section.rows.filter((r) => r.depth === 0);
                if (!rows.length) return null;
                const states = rows.map((r) => this.rowState(section.group, r.key));
                return states.every((s) => s === 'in') ? 'in' : (states.every((s) => s === 'out') ? 'out' : null);
            },
            /** Marks every row of a section, or, when they all already are, clears them. */
            setSection(section, state) {
                const clearing = this.sectionState(section) === state;
                for (const r of section.rows.filter((row) => row.depth === 0)) {
                    if (clearing || this.rowState(section.group, r.key) !== state) this.setRow(section.group, r.key, state);
                }
            },
            /** Does a hand pass the filters? Within one list ✓ rows add up (any of them), ✗ rows always leave a hand out. */
            matches(r) {
                const pass = (inc, exc, fine, row) => {
                    if (exc.includes(fine) || exc.includes(row)) return false;
                    return inc.length === 0 || inc.includes(fine) || inc.includes(row);
                };
                return pass(this.selMade, this.exMade, r.made, r.madeRow) && pass(this.selDraw, this.exDraw, r.draw, r.drawRow);
            },
            clearSelection() { this.selMade = []; this.selDraw = []; this.exMade = []; this.exDraw = []; },
            get liveTotal() {
                let n = 0;
                const dead = this.dead;
                for (let i = 0; i < COMBOS.length; i++) if (!dead.has(COMBOS[i][0]) && !dead.has(COMBOS[i][1])) n++;
                return n;
            },
            get rangeCombos() { return this.rows.reduce((s, r) => s + r.w, 0); },
            get selectedCombos() { return this.rows.filter((r) => this.matches(r)).reduce((s, r) => s + r.w, 0); },
            get selectedPercent() {
                const total = this.rangeCombos;
                return total ? (100 * this.selectedCombos) / total : 0;
            },
            /** Narrow the range to the selected categories (or drop them): the way to follow a range to the next street. */
            keepSelected() { this.filterRange(true); },
            dropSelected() { this.filterRange(false); },
            filterRange(keep) {
                if (!this.selActive) return;
                this.snapshot();
                const w = emptyRange();
                for (const r of this.rows) if (this.matches(r) === keep) w[r.i] = r.w;
                ranges[this.side] = w;
                this.clearSelection();
                this.changed();
            },

            // ---------- hover details ----------
            get detail() {
                this.rev; this.eq.rev;
                const k = this.hoverClass;
                if (k === null || !this.ready) return null;
                const rows = (this.rowsByClass()[k] || []).slice().sort((a, b) => b.w - a.w);
                const eqs = eqCombo[this.side];
                return {
                    name: CLASS_NAMES[k],
                    combos: rows.map((r) => ({
                        name: comboName(r.i), w: r.w, made: madeLabel(r.made), draw: r.draw ? drawLabel(r.draw) : '',
                        equity: eqs && eqs[r.i] >= 0 ? (100 * eqs[r.i]).toFixed(1) : null,
                    })),
                };
            },
            useAsMyHand(name) { this.myText = name; this.scheduleEquity(); this.persist(); },

            // ---------- "your hand": two exact cards against the opponent range ----------
            get my() {
                this.rev; this.eq.rev;
                const text = this.myText.trim();
                if (!text) return { state: 'empty' };
                let cards;
                try { cards = parseCards(text); } catch (e) { cards = []; }
                if (cards.length !== 2 || cards[0] === cards[1]) return { state: 'error', message: 'Enter two different cards, like AhKd.' };
                if (cards.some((c) => this.dead.has(c))) return { state: 'error', message: 'One of those cards is on the board or marked dead.' };
                const info = this.ready ? classify(cards, this.board) : null;
                return {
                    state: 'ok', cards, name: cards.map(cardName).join(''), index: comboIndex(cards[0], cards[1]),
                    made: info ? madeLabel(info.made) : null,
                    draw: info && info.draw ? drawLabel(info.draw) : null,
                };
            },

            // ---------- equity ----------
            scheduleEquity() {
                clearTimeout(eqTimer);
                eqTimer = setTimeout(() => { this.runEquity(); this.runHandEquity(); if (this.analysisTab === 'hotness') this.runHotness(); }, 250);
            },
            runEquity() {
                const token = ++jobToken;
                eqCombo.hero = eqCombo.villain = null;
                const empty = (side) => rangeTotals(ranges[side], this.dead).combos === 0;
                if (!this.ready || empty('hero') || empty('villain')) {
                    job = null;
                    this.eq = { running: false, progress: 0, hero: null, villain: null, rev: this.eq.rev + 1 };
                    return;
                }
                job = createEquityJob(ranges.hero, ranges.villain, this.board, 11, this.deadCards);
                let lastPublish = 0;
                const publish = (running) => {
                    const r = job.result();
                    eqCombo.hero = r.hero.equity; eqCombo.villain = r.villain.equity;
                    this.eq = { running, progress: job.progress, hero: r.hero.overall, villain: r.villain.overall, rev: this.eq.rev + 1 };
                };
                const tick = () => {
                    if (token !== jobToken) return;
                    const finished = job.step(25);
                    const now = performance.now();
                    if (finished || now - lastPublish > 150) { publish(!finished); lastPublish = now; }
                    if (!finished) setTimeout(tick, 0);
                };
                tick();
            },
            runHandEquity() {
                const token = ++handToken;
                const my = this.my;
                if (my.state !== 'ok' || !this.ready || rangeTotals(ranges.villain, this.dead).combos === 0) {
                    this.myEq = { running: false, value: null };
                    return;
                }
                const single = emptyRange();
                single[my.index] = 1;
                handJob = createEquityJob(single, ranges.villain, this.board, 5, this.deadCards);
                this.myEq = { running: true, value: null };
                const tick = () => {
                    if (token !== handToken) return;
                    const finished = handJob.step(25);
                    this.myEq = { running: !finished, value: handJob.result().hero.overall };
                    if (!finished) setTimeout(tick, 0);
                };
                tick();
            },

            // ---------- more analysis (always about the range on show) ----------
            setAnalysisTab(tab) {
                this.analysisTab = tab;
                if (tab === 'hotness') this.runHotness();
            },
            get viewedEquity() { this.eq.rev; return this.side === 'hero' ? this.eq.hero : this.eq.villain; },

            /** Equity matrix: the average equity of each grid cell's live combos against the other range. */
            get matrixCells() {
                this.rev; this.eq.rev;
                const eqs = eqCombo[this.side];
                const acc = Array.from({ length: CLASS_COUNT }, () => ({ w: 0, ew: 0 }));
                for (const r of this.rows) {
                    const a = acc[COMBO_CLASS[r.i]];
                    a.w += r.w;
                    if (eqs && eqs[r.i] >= 0) a.ew += r.w * eqs[r.i];
                }
                return acc.map((a, k) => ({ k, name: CLASS_NAMES[k], eq: a.w > 0 && eqs ? (100 * a.ew) / a.w : null }));
            },
            matrixStyle(cell) {
                if (cell.eq === null) return { backgroundColor: '#f1f5f9', color: '#cbd5e1' };
                const t = Math.min(1, Math.max(0, (cell.eq - 20) / 70));
                return { backgroundColor: `rgba(202,138,4,${0.2 + 0.8 * t})`, color: t > 0.6 ? '#422006' : '#713f12' };
            },

            /** Hotness: the equity of the range with each card that can come next, against the equity it has now. */
            get nextStreet() { return this.board.length === 3 ? 'turn' : (this.board.length === 4 ? 'river' : null); },
            hotCell(rank, suit) {
                this.hot.rev; this.eq.rev;
                const c = parseCard(rank + suit);
                if (this.dead.has(c)) return { state: 'dead' };
                const v = this.hot.values[c];
                if (v === undefined) return { state: 'none' };
                const base = this.viewedEquity === null ? null : 100 * this.viewedEquity;
                return { state: base !== null && v > base + 0.05 ? 'up' : 'down', value: Math.round(v) };
            },
            runHotness() {
                const token = ++hotToken;
                const mine = ranges[this.side], other = ranges[this.otherSide];
                const empty = (w) => rangeTotals(w, this.dead).combos === 0;
                if (!this.ready || this.board.length >= 5 || empty(mine) || empty(other)) {
                    this.hot = { running: false, progress: 0, values: {}, rev: this.hot.rev + 1 };
                    return;
                }
                const cards = [];
                for (let c = 0; c < 52; c++) if (!this.dead.has(c)) cards.push(c);
                const values = {};
                let index = 0, current = null;
                this.hot = { running: true, progress: 0, values: {}, rev: this.hot.rev + 1 };
                const tick = () => {
                    if (token !== hotToken) return;
                    const start = performance.now();
                    while (index < cards.length && performance.now() - start < 25) {
                        if (!current) current = createEquityJob(mine, other, [...this.board, cards[index]], 3, this.deadCards);
                        if (!current.step(25)) break;
                        const overall = current.result().hero.overall;
                        if (overall !== null) values[cards[index]] = 100 * overall;
                        current = null;
                        index++;
                    }
                    this.hot = { running: index < cards.length, progress: index / cards.length, values: { ...values }, rev: this.hot.rev + 1 };
                    if (index < cards.length) setTimeout(tick, 0);
                };
                tick();
            },

            /** Equity chart: one stepped line per range, its hands sorted by equity. */
            curve(side) {
                this.rev; this.eq.rev;
                const eqs = eqCombo[side];
                if (!eqs || !this.ready) return '';
                const dead = this.dead, w = ranges[side], items = [];
                for (let i = 0; i < w.length; i++) {
                    if (w[i] <= 0 || eqs[i] < 0) continue;
                    const [a, b] = COMBOS[i];
                    if (dead.has(a) || dead.has(b)) continue;
                    items.push([eqs[i], w[i]]);
                }
                return stepsPath(curveSteps(items), { left: 44, width: 560, bottom: 270, height: 250 });
            },

            /** Groups: how the range on show splits into five kinds of hand. */
            get groupSlices() {
                this.rev;
                const rows = this.rows;
                const total = rows.reduce((s, r) => s + r.w, 0);
                const sums = Object.fromEntries(GROUPS.map((g) => [g.key, 0]));
                for (const r of rows) sums[groupOf(r.made, r.draw)] += r.w;
                return GROUPS.map((g) => ({ ...g, combos: sums[g.key], percent: total ? (100 * sums[g.key]) / total : 0 }));
            },
            get groupGradient() { return conicGradient(this.groupSlices); },

            // ---------- small helpers ----------
            pct(n, d = 1) { return n === null || n === undefined ? '–' : Number(n).toFixed(d); },
            num(n) { return Number(n).toFixed(n % 1 ? 1 : 0); },
            weightLabel(w) { return w >= 0.995 ? '' : ' · ' + Math.round(w * 100) + '%'; },
        };
    });
}
