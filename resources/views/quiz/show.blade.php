<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quiz — {{ $study->name }}</h2>
            <a href="{{ route('quiz.index') }}" class="text-sm text-gray-600">All studies</a>
        </div>
    </x-slot>

    @if (empty($questions))
        <div class="py-6 sm:py-12">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="pc-card p-6 text-sm text-gray-500">This study has no scenarios available for the quiz yet.</div>
            </div>
        </div>
    @else
        <script>
            function rangeQuiz(questions) {
                return {
                    questions: questions,
                    index: 0,
                    chosen: null,
                    answers: [],
                    finished: false,

                    get total() { return this.questions.length; },
                    get current() { return this.questions[this.index]; },
                    get answered() { return this.chosen !== null; },
                    get isCorrect() { return this.answered && this.chosen === this.current.correct; },
                    get isLast() { return this.index + 1 >= this.total; },
                    get score() { return this.answers.filter(function (a) { return a.ok; }).length; },
                    get percent() { return Math.round(this.score / this.total * 100); },
                    get progress() { return (this.index + (this.answered ? 1 : 0)) / this.total * 100; },
                    get mistakes() { return this.answers.filter(function (a) { return ! a.ok; }); },

                    get byGroup() {
                        var groups = {};
                        this.answers.forEach(function (a) {
                            var g = groups[a.question.group] || (groups[a.question.group] = { group: a.question.group, total: 0, correct: 0 });
                            g.total++;
                            if (a.ok) { g.correct++; }
                        });

                        return Object.values(groups).map(function (g) {
                            g.missed = g.total - g.correct;
                            g.pct = Math.round(g.correct / g.total * 100);
                            return g;
                        }).sort(function (a, b) { return a.pct - b.pct || b.missed - a.missed || a.group.localeCompare(b.group); });
                    },

                    // The group with the most misses; on a tie, the lower accuracy.
                    get weakest() {
                        var withMisses = this.byGroup.filter(function (g) { return g.missed > 0; });
                        if (! withMisses.length) { return null; }

                        return withMisses.slice().sort(function (a, b) { return b.missed - a.missed || a.pct - b.pct; })[0];
                    },

                    barColor(pct) { return pct >= 80 ? '#22c55e' : (pct >= 50 ? '#f59e0b' : '#ef4444'); },

                    labelOf(question, key) {
                        var option = question.options.find(function (o) { return o.key === key; });
                        return option ? option.label : key;
                    },

                    choose(key) {
                        if (this.answered || this.finished) { return; }
                        this.chosen = key;
                        this.answers.push({ question: this.current, chosen: key, ok: key === this.current.correct });
                    },

                    next() {
                        if (! this.answered) { return; }
                        this.chosen = null;
                        if (this.isLast) { this.finished = true; } else { this.index++; }
                    },

                    optionClass(option) {
                        if (! this.answered) { return 'border-gray-200 bg-white text-gray-800 hover:border-indigo-400'; }
                        if (option.key === this.current.correct) { return 'border-green-500 bg-green-50 text-green-800'; }
                        if (option.key === this.chosen) { return 'border-red-500 bg-red-50 text-red-800'; }
                        return 'border-gray-200 bg-white text-gray-400 opacity-60';
                    },

                    onKey(event) {
                        if (this.finished || event.ctrlKey || event.metaKey || event.altKey) { return; }

                        var digit = parseInt(event.key, 10);
                        if (! this.answered && digit >= 1 && digit <= this.current.options.length) {
                            this.choose(this.current.options[digit - 1].key);
                            return;
                        }

                        // A focused button already handles Enter/Space as a click.
                        if (this.answered && (event.key === 'Enter' || event.key === ' ') && event.target.tagName !== 'BUTTON') {
                            event.preventDefault();
                            this.next();
                        }
                    },
                };
            }
        </script>

        <div class="py-6 sm:py-12" x-data="rangeQuiz(@js($questions))" @keydown.window="onKey($event)">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                {{-- Playing --}}
                <div x-show="! finished" class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span>Question <span class="font-semibold text-gray-800" x-text="index + 1"></span> of <span x-text="total"></span></span>
                            <span>Correct so far: <span class="font-semibold text-gray-800" x-text="score"></span></span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-200">
                            <div class="h-2 rounded-full bg-indigo-600 transition-all" :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>

                    <div class="pc-card p-6 sm:p-8 text-center">
                        <div class="flex flex-wrap items-center justify-center gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-slate-900 px-3 py-1 text-white" x-text="current.group"></span>
                            <span class="rounded-full px-3 py-1 text-slate-800" :style="{ backgroundColor: current.row_color + '40' }" x-text="current.row"></span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700" x-text="current.button"></span>
                        </div>

                        <div class="mt-6 flex justify-center gap-3">
                            <template x-for="(card, i) in current.cards" :key="index + '-' + i">
                                <div class="flex h-24 w-16 flex-col items-center justify-center rounded-lg border border-gray-300 bg-white shadow-sm sm:h-28 sm:w-20"
                                     :class="card.red ? 'text-red-600' : 'text-slate-900'">
                                    <span class="text-3xl font-bold leading-none sm:text-4xl" x-text="card.rank"></span>
                                    <span class="mt-1 text-2xl leading-none sm:text-3xl" x-text="card.suit"></span>
                                </div>
                            </template>
                        </div>
                        <p class="mt-3 text-sm font-medium text-gray-500" x-text="current.hand"></p>

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <template x-for="(option, i) in current.options" :key="index + '-' + option.key">
                                <button type="button" @click="choose(option.key)" :disabled="answered"
                                        class="flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-3 text-sm font-semibold transition"
                                        :class="optionClass(option)">
                                    <span class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: option.color }"></span>
                                    <span x-text="option.label"></span>
                                    <span class="text-[10px] font-normal text-gray-400" x-text="i + 1"></span>
                                </button>
                            </template>
                        </div>

                        <div x-show="answered" x-cloak class="mt-5" aria-live="polite">
                            <p class="text-sm font-semibold" :class="isCorrect ? 'text-green-700' : 'text-red-700'"
                               x-text="isCorrect ? 'Correct!' : 'Not quite — the right action is ' + labelOf(current, current.correct) + '.'"></p>
                            <button type="button" @click="next()"
                                    class="mt-3 inline-flex items-center px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500"
                                    x-text="isLast ? 'See results' : 'Next question'"></button>
                            <p class="mt-2 text-xs text-gray-400">Tip: use the number keys to answer and Enter for the next one.</p>
                        </div>
                    </div>
                </div>

                {{-- Results --}}
                <div x-show="finished" x-cloak class="space-y-4">
                    <div class="pc-card p-6 sm:p-8 text-center">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Your result</p>
                        <p class="mt-2 text-5xl font-extrabold text-gray-900">
                            <span x-text="score"></span><span class="text-gray-400">/<span x-text="total"></span></span>
                        </p>
                        <p class="mt-1 text-sm text-gray-500"><span x-text="percent"></span>% correct</p>

                        <template x-if="weakest">
                            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-left">
                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Where you missed the most</p>
                                <p class="mt-1 text-xl font-bold text-amber-900" x-text="weakest.group"></p>
                                <p class="text-sm text-amber-800">
                                    <span x-text="weakest.missed"></span> missed of <span x-text="weakest.total"></span>
                                    (<span x-text="weakest.correct"></span>/<span x-text="weakest.total"></span> correct)
                                </p>
                            </div>
                        </template>
                        <template x-if="! weakest">
                            <p class="mt-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800">Perfect round — no mistakes!</p>
                        </template>
                    </div>

                    <div class="pc-card p-6">
                        <h3 class="text-sm font-semibold text-gray-900">By situation</h3>
                        <div class="mt-3 space-y-3">
                            <template x-for="g in byGroup" :key="g.group">
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-gray-800" x-text="g.group"></span>
                                        <span class="text-gray-500"><span x-text="g.correct"></span>/<span x-text="g.total"></span> · <span x-text="g.pct"></span>%</span>
                                    </div>
                                    <div class="mt-1 h-2 rounded-full bg-gray-200">
                                        <div class="h-2 rounded-full" :style="{ width: g.pct + '%', backgroundColor: barColor(g.pct) }"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="pc-card p-6" x-show="mistakes.length">
                        <h3 class="text-sm font-semibold text-gray-900">Review your mistakes</h3>
                        <ul class="mt-3 divide-y divide-gray-100 text-sm">
                            <template x-for="m in mistakes" :key="m.question.id">
                                <li class="py-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-semibold text-gray-900" x-text="m.question.hand"></span>
                                        <span class="text-xs text-gray-500" x-text="m.question.group + ' · ' + m.question.row + ' · ' + m.question.button"></span>
                                    </div>
                                    <p class="mt-0.5 text-xs">
                                        You chose <span class="font-semibold text-red-700" x-text="labelOf(m.question, m.chosen)"></span>,
                                        correct was <span class="font-semibold text-green-700" x-text="labelOf(m.question, m.question.correct)"></span>
                                    </p>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('quiz.show', $study) }}" class="inline-flex items-center px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">New quiz</a>
                        <a href="{{ route('quiz.index') }}" class="text-sm text-gray-600">Choose another study</a>
                        <a href="{{ route('ranges.show', $study) }}" class="text-sm text-gray-600">Review the ranges</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
