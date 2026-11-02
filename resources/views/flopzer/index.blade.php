<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Flopzer</h2>
    </x-slot>

    <div class="py-3 sm:py-4" x-data="flopzer(@js($presets))">
        <div class="w-full px-2 sm:px-3 space-y-3">

            <p class="text-sm text-gray-600">
                See how a range hits a board. Paint your range and the opponent&rsquo;s on the grid, deal the flop, turn and river,
                and read how many combos are top pair, flush draws, sets&hellip; and how much equity each group has.
                Click a row to light those hands up on the grid, or keep only them to follow the range to the next street.
            </p>

            <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,660px)_minmax(0,1fr)]">

                {{-- ============ RANGE ============ --}}
                <div class="space-y-3 min-w-0">
                <div class="pc-card p-3 sm:p-4 space-y-3">
                    <div class="flex gap-2">
                        <template x-for="s in ['hero', 'villain']" :key="s">
                            <div role="button" tabindex="0" @click="switchSide(s)" @keydown.enter.prevent="switchSide(s)" @keydown.space.prevent="switchSide(s)"
                                 class="flex-1 cursor-pointer rounded-lg border px-3 py-2 text-left transition"
                                 :class="side === s ? 'border-indigo-600 bg-indigo-50 ring-1 ring-indigo-600' : 'border-gray-300 bg-white hover:bg-gray-50'">
                                <div class="text-sm font-semibold text-gray-900" x-text="s === 'hero' ? 'Your range' : 'Opponent range'"></div>
                                <div class="text-xs text-gray-500">
                                    <span x-text="num(totalsOf(s).combos)"></span> combos ·
                                    <span x-text="pct(totalsOf(s).percent)"></span>%
                                </div>
                                <button type="button" x-show="presets.length" @click.stop="openPresets(s)"
                                        class="mt-2 inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                    <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4zM4 9.333h16M4 14.667h16M9.333 4v16M14.667 4v16"/></svg>
                                    Load a range
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- the grid with the weights section beside it --}}
                    <div class="flex gap-3">
                        <div class="min-w-0 flex-1" style="max-width: 540px;">
                            <div style="display:grid; grid-template-columns: repeat(13, minmax(0,1fr)); gap:2px; touch-action:none; user-select:none;"
                                 @pointermove="movePaint($event)" @mouseleave="if (!painting) hoverClass = null">
                                <template x-for="k in classIndexes" :key="k">
                                    <button type="button" :data-cell="k"
                                            @pointerdown.prevent="startPaint(k)" @pointerenter="hoverClass = k"
                                            class="aspect-square flex items-center justify-center rounded-sm text-[8px] sm:text-[10px] font-bold"
                                            :style="cellStyle(k)" x-text="classNames[k]"></button>
                                </template>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500" x-show="weightLegend.length">
                                <span class="font-medium">Weights in this range</span>
                                <template x-for="item in weightLegend" :key="item.percent">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="inline-block h-2.5 w-2.5 rounded-sm" :style="'background:' + item.color"></span>
                                        <span class="font-semibold text-gray-700" x-text="item.percent + '%'"></span>
                                        <span x-text="num(item.combos) + ' combos'"></span>
                                    </span>
                                </template>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500" x-show="selActive" x-cloak>
                                <span class="inline-flex items-center gap-1">
                                    <span class="inline-block h-2.5 w-2.5 rounded-sm" style="background:#f59e0b"></span>
                                    <span class="font-medium">Passes the filters</span>
                                    <span class="font-semibold text-gray-700" x-text="num(selectedCombos) + ' combos'"></span>
                                    <span>(the amber part of each hand)</span>
                                </span>
                                <button type="button" @click="clearSelection()" class="font-medium text-indigo-600 hover:underline">Clear filters</button>
                            </div>
                        </div>

                        {{-- weights: the level to paint with --}}
                        <div class="flex w-[104px] shrink-0 flex-col items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-2 text-xs">
                            <p class="font-semibold text-gray-700">Weight</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="brushPercent + '%'"></p>
                            <input type="range" min="1" max="99" step="1" :disabled="activeTier === 0"
                                   :value="brushPercent" @input="setTierWeight($event.target.value)"
                                   aria-label="Weight of the selected level"
                                   class="disabled:opacity-30" style="writing-mode: vertical-lr; direction: rtl; height: 120px; width: 24px;">
                            <button type="button" @click="setTier(0)"
                                    class="w-full rounded-md border px-2 py-1.5 font-semibold"
                                    :class="activeTier === 0 ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'">No weight</button>
                            <div class="grid w-full grid-cols-1 gap-1">
                                <template x-for="n in 3" :key="n">
                                    <button type="button" @click="setTier(n)"
                                            class="flex w-full items-center justify-between gap-1 rounded-md border bg-white px-2 py-1.5 font-semibold"
                                            :class="activeTier === n ? 'border-gray-900 ring-1 ring-gray-900' : 'border-gray-300 hover:bg-gray-100'">
                                        <span class="inline-block h-3 w-3 rounded-sm" :style="'background:' + tierColors[n - 1]"></span>
                                        <span class="text-gray-700" x-text="tierWeights[n - 1] + '%'"></span>
                                    </button>
                                </template>
                            </div>
                            <p class="text-center text-[10px] leading-tight text-gray-400">Pick a weight, then paint the hands. The slider changes the weight of the selected level.</p>
                        </div>
                    </div>
                    {{-- tools --}}
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <button type="button" @click="quick('pocket')" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50">Pocket</button>
                        <button type="button" @click="quick('broadway')" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50">Broadway</button>
                        <button type="button" @click="quick('suited')" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50">Suited</button>
                        <button type="button" @click="fillRange()" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50">All hands</button>
                        <button type="button" @click="clearRange()" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50">Clear</button>
                        <button type="button" @click="undo()" :disabled="!canUndo" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40">Undo</button>
                        <button type="button" @click="copyOver()" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50"
                                x-text="side === 'hero' ? 'Copy to opponent' : 'Copy to you'"></button>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <label for="top" class="font-medium text-gray-600 whitespace-nowrap">Strongest hands</label>
                        <input id="top" type="range" min="1" max="100" step="1" x-model.number="top" @change="applyTop()" class="flex-1">
                        <span class="w-10 text-right font-semibold text-gray-800" x-text="top + '%'"></span>
                    </div>

                    {{-- text --}}
                    <div>
                        <label for="range-text" class="block text-xs font-medium text-gray-600">Range as text</label>
                        <textarea id="range-text" rows="3" x-model="draft" @focus="draftFocus = true" @blur="draftFocus = false"
                                  @keydown.ctrl.enter.prevent="applyDraft()" spellcheck="false"
                                  placeholder="AA-TT, AKs-ATs, KQo, A5s-A2s:50%, AhKh"
                                  class="mt-1 block w-full rounded-md border-gray-300 text-xs font-mono shadow-sm"></textarea>
                        <div class="mt-1 flex items-center gap-2">
                            <button type="button" @click="applyDraft()" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">Apply text</button>
                            <p class="text-xs text-red-600" x-show="draftError" x-text="draftError"></p>
                            <p class="text-xs text-gray-400" x-show="!draftError">Pairs <code>77+</code>, suited <code>ATs+</code>, offsuit <code>KQo</code>, runs <code>A5s-A2s</code>, weights <code>:50%</code>, exact cards <code>AhKh</code>.</p>
                        </div>
                    </div>

                    {{-- saved ranges (kept in this browser) --}}
                    <div class="border-t border-gray-100 pt-3">
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="saveName" @keydown.enter.prevent="saveRange()" maxlength="40" placeholder="Name this range to save it"
                                   class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                            <button type="button" @click="saveRange()" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 whitespace-nowrap">Save</button>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5" x-show="saved.length">
                            <template x-for="item in saved" :key="item.name">
                                <span class="inline-flex items-center overflow-hidden rounded-md border border-gray-300 text-xs">
                                    <button type="button" @click="loadRange(item)" class="bg-white px-2 py-1 font-medium text-gray-700 hover:bg-indigo-50" x-text="item.name" :title="'Load into ' + sideLabel.toLowerCase()"></button>
                                    <button type="button" @click="deleteSaved(item)" class="border-l border-gray-300 bg-white px-1.5 py-1 text-gray-400 hover:text-red-600" aria-label="Delete saved range">&times;</button>
                                </span>
                            </template>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400">Saved ranges stay in this browser only.</p>
                    </div>
                </div>

                {{-- ============ MORE ANALYSIS ============ --}}
                <div class="pc-card p-3 sm:p-4" x-show="ready" x-cloak>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-gray-700">More analysis <span class="font-normal text-gray-400" x-text="'· ' + sideLabel.toLowerCase()"></span></p>
                        <div class="inline-flex overflow-hidden rounded-lg border border-gray-300 text-xs font-medium">
                            <template x-for="tab in [['matrix', 'Equity matrix'], ['hotness', 'Hotness'], ['chart', 'Equity chart'], ['groups', 'Groups']]" :key="tab[0]">
                                <button type="button" @click="setAnalysisTab(tab[0])" x-text="tab[1]"
                                        class="border-l border-gray-300 px-3 py-1.5 first:border-l-0"
                                        :class="analysisTab === tab[0] ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"></button>
                            </template>
                        </div>
                    </div>

                    {{-- equity of each hand --}}
                    <div x-show="analysisTab === 'matrix'" class="mt-3">
                        <p class="text-xs text-gray-500">The equity of each hand in this range against the other range.</p>
                        <div class="mt-2" style="display:grid; grid-template-columns: repeat(13, minmax(0,1fr)); gap:2px;">
                            <template x-for="cell in matrixCells" :key="cell.k">
                                <div class="flex aspect-square flex-col items-center justify-center rounded-sm leading-none" :style="matrixStyle(cell)"
                                     :title="cell.eq === null ? '' : cell.name + ': ' + pct(cell.eq) + '%'">
                                    <span class="text-[7px] font-medium opacity-75 sm:text-[9px]" x-text="cell.eq === null ? '' : cell.name"></span>
                                    <span class="text-[9px] font-bold sm:text-[12px]" x-text="cell.eq === null ? '' : Math.round(cell.eq)"></span>
                                </div>
                            </template>
                        </div>
                        <p class="mt-2 text-xs text-gray-400" x-show="eq.hero === null">Fill both ranges to see their equities.</p>
                    </div>

                    {{-- the next card --}}
                    <div x-show="analysisTab === 'hotness'" x-cloak class="mt-3">
                        <template x-if="nextStreet">
                            <div>
                                <p class="text-xs text-gray-500">
                                    The equity of this range with each card that can come on the <span x-text="nextStreet"></span>:
                                    in amber the cards that raise it, in grey those that lower it.
                                    <span class="text-gray-400" x-show="hot.running" x-text="'Calculating… ' + Math.round(hot.progress * 100) + '%'"></span>
                                </p>
                                <div class="mt-2 space-y-1">
                                    <template x-for="s in suits" :key="s">
                                        <div class="grid gap-1" style="grid-template-columns: repeat(13, minmax(0,1fr));">
                                            <template x-for="r in ranks" :key="r + s">
                                                <div class="flex flex-col items-center justify-center rounded py-1 leading-tight"
                                                     :class="hotCell(r, s).state === 'up' ? 'bg-amber-200 text-amber-950' : (hotCell(r, s).state === 'down' ? 'bg-slate-100 text-slate-600' : 'bg-slate-50 text-slate-300')">
                                                    <span class="text-[9px] font-bold sm:text-[11px]" :style="hotCell(r, s).state === 'dead' ? 'opacity:.35' : 'color:' + ({ s: '#334155', h: '#dc2626', d: '#2563eb', c: '#16a34a' })[s]" x-text="r + symbols[s]"></span>
                                                    <span class="text-[9px] font-semibold sm:text-[11px]" x-text="hotCell(r, s).value !== undefined ? hotCell(r, s).value : '·'"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <p class="mt-2 text-xs text-gray-400" x-show="eq.hero === null">Fill both ranges first.</p>
                            </div>
                        </template>
                        <p class="text-xs text-gray-500" x-show="!nextStreet">There is no card left to come: the board is complete.</p>
                    </div>

                    {{-- one line per range --}}
                    <div x-show="analysisTab === 'chart'" x-cloak class="mt-3">
                        <p class="text-xs text-gray-500">One line per range: its hands, from the one with the least equity (left) to the one with the most (right). At the same height of the axis, the line on top has more equity.</p>
                        <div class="mt-2 flex flex-wrap gap-4 text-xs">
                            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-0.5 w-5" style="background:#ca8a04"></span><span class="font-semibold text-gray-800">Your range</span><span class="text-gray-600" x-text="eq.hero === null ? '' : pct(eq.hero * 100) + '%'"></span></span>
                            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-0.5 w-5" style="background:#3b82f6"></span><span class="font-semibold text-gray-800">Opponent range</span><span class="text-gray-600" x-text="eq.villain === null ? '' : pct(eq.villain * 100) + '%'"></span></span>
                        </div>
                        <svg viewBox="0 0 620 310" class="mt-2 w-full" role="img" aria-label="Equity of the hands of each range, from the weakest to the strongest">
                            <g stroke="#e2e8f0" stroke-width="1">
                                <line x1="44" x2="604" y1="20" y2="20"/><line x1="44" x2="604" y1="82.5" y2="82.5"/><line x1="44" x2="604" y1="145" y2="145"/>
                                <line x1="44" x2="604" y1="207.5" y2="207.5"/><line x1="44" x2="604" y1="270" y2="270"/>
                            </g>
                            <g font-size="11" fill="#64748b" text-anchor="end">
                                <text x="38" y="24">100</text><text x="38" y="86.5">75</text><text x="38" y="149">50</text><text x="38" y="211.5">25</text><text x="38" y="274">0</text>
                            </g>
                            <g font-size="11" fill="#64748b" text-anchor="middle">
                                <text x="44" y="288">0</text><text x="184" y="288">25</text><text x="324" y="288">50</text><text x="464" y="288">75</text><text x="604" y="288">100</text>
                            </g>
                            <text x="324" y="305" font-size="11" fill="#94a3b8" text-anchor="middle">Part of the range, from the worst hand to the best</text>
                            <text x="12" y="145" font-size="11" fill="#94a3b8" text-anchor="middle" transform="rotate(-90 12 145)">Equity</text>
                            <path :d="curve('villain')" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linejoin="round"/>
                            <path :d="curve('hero')" fill="none" stroke="#ca8a04" stroke-width="2.5" stroke-linejoin="round"/>
                        </svg>
                        <p class="text-xs text-gray-400" x-show="eq.hero === null">Fill both ranges to see the lines.</p>
                    </div>

                    {{-- how the range splits --}}
                    <div x-show="analysisTab === 'groups'" x-cloak class="mt-3">
                        <p class="text-xs text-gray-500">How this range splits on this board.</p>
                        <div class="mt-3 flex flex-wrap items-center gap-6">
                            <div class="h-36 w-36 shrink-0 rounded-full" :style="'background:' + groupGradient" role="img" aria-label="Share of each kind of hand"></div>
                            <div class="space-y-1.5 text-sm">
                                <template x-for="g in groupSlices" :key="g.key">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block h-3 w-3 rounded-sm" :style="'background:' + g.color"></span>
                                        <span class="w-40 text-gray-700" x-text="g.label"></span>
                                        <span class="font-semibold text-gray-900" x-text="pct(g.percent) + '%'"></span>
                                        <span class="text-xs text-gray-400" x-text="num(g.combos) + ' combos'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                {{-- ============ BOARD + RESULTS ============ --}}
                <div class="space-y-3 min-w-0">

                    <div class="grid grid-cols-1 items-start gap-3 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                    {{-- board --}}
                    <div class="pc-card p-3 sm:p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-gray-700">Board</p>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600" x-text="street"></span>
                            </div>
                            <div class="flex flex-wrap gap-1.5 text-xs">
                                <button type="button" @click="randomFlop()" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50">Random flop</button>
                                <button type="button" @click="randomCard()" :disabled="board.length < 3 || board.length >= 5" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40">Random turn / river</button>
                                <button type="button" @click="clearBoard()" class="rounded-md border border-gray-300 bg-white px-2 py-1 font-medium text-gray-700 hover:bg-gray-50">Clear</button>
                            </div>
                        </div>

                        <div class="mt-3 flex gap-2">
                            <template x-for="(slot, i) in 5" :key="i">
                                <button type="button" @click="dealt[i] && removeCard(dealt[i].card)" :disabled="!dealt[i]"
                                        class="relative flex h-14 w-11 sm:w-14 flex-col items-center justify-center rounded-lg border-2 text-lg font-bold"
                                        :class="dealt[i] ? 'border-gray-700 bg-white' : 'border-dashed border-gray-300 bg-gray-50 text-gray-300'">
                                    <span x-show="dealt[i]" :style="dealt[i] ? 'color:' + dealt[i].color : ''" x-text="dealt[i] ? dealt[i].rank + symbols[dealt[i].suit] : ''"></span>
                                    <span class="absolute -top-2 left-1 bg-white px-1 text-[9px] font-medium text-gray-400" x-text="['Flop', 'Flop', 'Flop', 'Turn', 'River'][i]"></span>
                                </button>
                            </template>
                        </div>

                        <div class="mt-3 space-y-1">
                            <template x-for="s in suits" :key="s">
                                <div class="flex gap-1">
                                    <template x-for="r in ranks" :key="r + s">
                                        <button type="button" @click="toggleCard(r, s)"
                                                class="flex h-7 flex-1 items-center justify-center rounded text-[11px] font-bold sm:text-xs"
                                                :class="onBoard(r, s) ? 'bg-gray-800 text-white' : (isDead(r, s) ? 'bg-gray-300 text-gray-400 line-through' : 'bg-gray-100 hover:bg-gray-200')"
                                                :style="onBoard(r, s) || isDead(r, s) ? '' : 'color:' + ({ s: '#334155', h: '#dc2626', d: '#2563eb', c: '#16a34a' })[s]"
                                                x-text="r + symbols[s]"></button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- dead cards --}}
                        <div class="mt-3 border-t border-gray-100 pt-3">
                            <button type="button" @click="showDead = !showDead" class="flex w-full items-center justify-between text-left text-xs font-medium text-gray-600">
                                <span>Dead cards <span class="font-normal text-gray-400" x-text="deadCards.length ? '(' + deadCards.length + ' marked)' : '(cards you know are out of the deck)'"></span></span>
                                <span class="text-gray-400" x-text="showDead ? 'Hide' : 'Show'"></span>
                            </button>
                            <div x-show="showDead" x-cloak class="mt-2 space-y-1">
                                <template x-for="s in suits" :key="'d' + s">
                                    <div class="flex gap-1">
                                        <template x-for="r in ranks" :key="'d' + r + s">
                                            <button type="button" @click="toggleDead(r, s)" :disabled="onBoard(r, s)"
                                                    class="flex h-6 flex-1 items-center justify-center rounded text-[10px] font-bold sm:text-[11px]"
                                                    :class="isDead(r, s) ? 'bg-rose-600 text-white' : (onBoard(r, s) ? 'bg-gray-100 text-gray-300' : 'bg-gray-50 hover:bg-rose-50')"
                                                    :style="isDead(r, s) || onBoard(r, s) ? '' : 'color:' + ({ s: '#334155', h: '#dc2626', d: '#2563eb', c: '#16a34a' })[s]"
                                                    x-text="r + symbols[s]"></button>
                                        </template>
                                    </div>
                                </template>
                                <button type="button" @click="clearDead()" x-show="deadCards.length" class="mt-1 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Clear dead cards</button>
                            </div>
                        </div>
                    </div>

                    {{-- equity --}}
                    <div class="pc-card p-3 sm:p-4" x-show="ready">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-700">Equity</p>
                            <p class="text-xs text-gray-400" x-show="eq.running" x-text="'Calculating… ' + Math.round(eq.progress * 100) + '%'"></p>
                        </div>
                        <template x-if="eq.hero !== null">
                            <div class="mt-2">
                                <div class="flex h-9 overflow-hidden rounded-lg text-sm font-bold text-white">
                                    <div class="flex items-center justify-center bg-indigo-600 transition-all" :style="'width:' + (eq.hero * 100) + '%'" x-text="pct(eq.hero * 100) + '%'"></div>
                                    <div class="flex flex-1 items-center justify-center bg-slate-500" x-text="pct(eq.villain * 100) + '%'"></div>
                                </div>
                                <div class="mt-1 flex justify-between text-xs text-gray-500"><span>Your range</span><span>Opponent range</span></div>
                            </div>
                        </template>
                        <p class="mt-2 text-xs text-gray-500" x-show="eq.hero === null && !eq.running">Fill both ranges to see the equity of one against the other on this board.</p>

                        {{-- your exact hand --}}
                        <div class="mt-3 border-t border-gray-100 pt-3">
                            <label for="my-hand" class="block text-xs font-medium text-gray-600">Your hand against the opponent range</label>
                            <div class="mt-1 flex items-center gap-2">
                                <input id="my-hand" type="text" x-model="myText" @input.debounce.300ms="scheduleEquity(); persist()" maxlength="8" placeholder="AhKd" autocomplete="off" spellcheck="false"
                                       class="block w-28 rounded-md border-gray-300 font-mono text-sm shadow-sm">
                                <button type="button" x-show="myText" @click="myText = ''; scheduleEquity(); persist()" class="text-xs text-gray-500 hover:text-gray-800">Clear</button>
                                <p class="text-xs text-gray-400" x-show="!myText">Two cards: calculates their equity on this board.</p>
                            </div>
                            <template x-if="my.state === 'error'"><p class="mt-1 text-xs text-red-600" x-text="my.message"></p></template>
                            <template x-if="my.state === 'ok'">
                                <div class="mt-2 flex flex-wrap items-baseline gap-x-3 gap-y-1 text-sm">
                                    <span class="font-mono font-bold text-gray-900" x-text="my.name"></span>
                                    <span class="text-gray-600" x-text="(my.made || '') + (my.draw ? ' + ' + my.draw.toLowerCase() : '')"></span>
                                    <span class="ml-auto text-xl font-bold text-indigo-700" x-show="myEq.value !== null" x-text="pct(myEq.value * 100) + '%'"></span>
                                    <span class="text-xs text-gray-400" x-show="myEq.running">calculating…</span>
                                    <span class="text-xs text-gray-400" x-show="myEq.value === null && !myEq.running">Needs the flop and an opponent range.</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    </div>

                    <p class="pc-card p-4 text-sm text-gray-500" x-show="!ready">Deal at least the three flop cards to see how the range hits the board.</p>

                    {{-- breakdown: made hands, draws and their combinations, laid out as a table --}}
                    <div class="pc-card p-3 sm:p-4" x-show="ready">
                        <p class="text-sm font-semibold text-gray-700">Statistics <span class="font-normal text-gray-400" x-text="'· ' + sideLabel.toLowerCase()"></span></p>

                        <div class="mt-2 overflow-x-auto">
                            <div style="min-width: 440px;">
                                {{-- column heads, in the same columns as the rows --}}
                                <div class="grid items-end border-b border-gray-200 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400"
                                     style="grid-template-columns: 22px minmax(0,1fr) 84px 52px 58px 62px 60px;">
                                    <span></span><span></span><span class="border-l border-gray-100"></span>
                                    <span class="border-l border-gray-100 pr-2 text-right">Combos</span>
                                    <span class="border-l border-gray-100 pr-2 text-right">Share</span>
                                    <span class="border-l border-gray-100 pr-2 text-right" title="Equity of these hands against the other range">Equity</span>
                                    <span class="border-l border-gray-100 text-center" title="&#10003; keep only these hands, &#10005; leave them out">Filter</span>
                                </div>

                                <template x-for="section in tableSections" :key="section.id">
                                    <div x-show="section.rows.length">
                                        {{-- the section title, with its own tick and cross for the whole block --}}
                                        <div class="grid items-center bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                                             style="grid-template-columns: 22px minmax(0,1fr) 84px 52px 58px 62px 60px;">
                                            <span class="col-span-5 py-1 pl-2" x-text="section.title"></span>
                                            <span class="border-l border-gray-100"></span>
                                            <span class="flex items-center justify-center gap-1 border-l border-gray-100 py-1">
                                                <button type="button" @click="setSection(section, 'in')" :aria-label="'Keep only the hands of ' + section.title"
                                                        class="flex h-5 w-6 items-center justify-center rounded border text-[10px] font-bold"
                                                        :class="sectionState(section) === 'in' ? 'border-amber-500 bg-amber-400 text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-100'">&#10003;</button>
                                                <button type="button" @click="setSection(section, 'out')" :aria-label="'Leave out the hands of ' + section.title"
                                                        class="flex h-5 w-6 items-center justify-center rounded border text-[10px] font-bold"
                                                        :class="sectionState(section) === 'out' ? 'border-rose-500 bg-rose-500 text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-100'">&#10005;</button>
                                            </span>
                                        </div>

                                        <template x-for="row in section.rows" :key="row.key">
                                            <div class="grid items-center border-b border-gray-100 text-xs"
                                                 style="grid-template-columns: 22px minmax(0,1fr) 84px 52px 58px 62px 60px;"
                                                 :class="rowState(row.group, row.key) === 'in' ? 'bg-amber-50' : (rowState(row.group, row.key) === 'out' ? 'bg-rose-50' : '')">
                                                <span class="flex justify-center">
                                                    <button type="button" @click="toggleOpen(row.key)" x-show="row.hasKids" class="flex h-5 w-5 items-center justify-center rounded border border-gray-300 bg-white text-gray-500 hover:bg-gray-50"
                                                            :aria-label="row.open ? 'Hide the finer rows' : 'Show the finer rows'">
                                                        <svg class="h-3 w-3 transition" :class="row.open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                                                    </button>
                                                </span>
                                                <span class="min-w-0 truncate py-1.5 pr-2" :class="row.depth ? 'pl-5 text-gray-600' : 'pl-1 font-medium text-gray-800'" x-text="row.label"></span>
                                                <span class="flex h-full items-center border-l border-gray-100 px-2">
                                                    <span class="relative h-1.5 w-full overflow-hidden rounded bg-gray-100">
                                                        <span class="absolute inset-y-0 left-0 rounded" :class="section.id === 'made' ? 'bg-indigo-400' : (section.id === 'draw' ? 'bg-emerald-400' : 'bg-sky-400')" :style="'width:' + row.percent + '%'"></span>
                                                    </span>
                                                </span>
                                                <span class="border-l border-gray-100 py-1.5 pr-2 text-right text-gray-600" x-text="num(row.combos)"></span>
                                                <span class="border-l border-gray-100 py-1.5 pr-2 text-right font-semibold text-gray-900" x-text="pct(row.percent) + '%'"></span>
                                                <span class="border-l border-gray-100 py-1.5 pr-2 text-right text-indigo-700" x-text="row.equity === null ? '' : pct(row.equity) + '%'"></span>
                                                <span class="flex items-center justify-center gap-1 border-l border-gray-100 py-1">
                                                    <button type="button" @click="setRow(row.group, row.key, 'in')" aria-label="Keep only these hands"
                                                            class="flex h-5 w-6 items-center justify-center rounded border text-[10px] font-bold"
                                                            :class="rowState(row.group, row.key) === 'in' ? 'border-amber-500 bg-amber-400 text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'">&#10003;</button>
                                                    <button type="button" @click="setRow(row.group, row.key, 'out')" aria-label="Leave these hands out"
                                                            class="flex h-5 w-6 items-center justify-center rounded border text-[10px] font-bold"
                                                            :class="rowState(row.group, row.key) === 'out' ? 'border-rose-500 bg-rose-500 text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'">&#10005;</button>
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-3 text-xs text-gray-500">
                            <p>Range: <span class="font-semibold text-gray-700" x-text="num(rangeCombos)"></span> combos
                                (<span x-text="pct(liveTotal ? (100 * rangeCombos) / liveTotal : 0)"></span>% of the live hands)</p>
                            <p x-show="selActive">Passing the filters: <span class="font-semibold text-gray-700" x-text="num(selectedCombos)"></span>
                                (<span x-text="pct(selectedPercent)"></span>%)</p>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-400" x-show="board.length < 5">Backdoor flush draws only count on the flop. A hand is listed once in each section: its made hand, and its draw or draw + pair combination.</p>
                    </div>
                    {{-- selection actions --}}
                    <div class="pc-card flex flex-wrap items-center gap-2 border-amber-300 bg-amber-50 p-3 text-xs" x-show="selActive" x-cloak>
                        <span class="font-semibold text-amber-900"><span x-text="num(selectedCombos)"></span> combos pass the filters (<span x-text="pct(selectedPercent)"></span>% of the range)</span>
                        <span class="text-amber-800">lit in amber on the grid</span>
                        <span class="flex-1"></span>
                        <button type="button" @click="keepSelected()" class="rounded-md bg-indigo-600 px-3 py-1.5 font-medium text-white hover:bg-indigo-500">Keep only these</button>
                        <button type="button" @click="dropSelected()" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">Remove these</button>
                        <button type="button" @click="clearSelection()" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">Clear filters</button>
                    </div>
                    {{-- hover detail --}}
                    <div class="pc-card p-3 sm:p-4" x-show="ready && detail" x-cloak>
                        <p class="text-sm font-semibold text-gray-700" x-text="detail ? detail.name : ''"></p>
                        <div class="mt-2 grid gap-x-4 gap-y-1 text-xs sm:grid-cols-2">
                            <template x-for="c in (detail ? detail.combos : [])" :key="c.name">
                                <div class="flex items-baseline gap-2">
                                    <button type="button" @click="useAsMyHand(c.name)" title="Use as your hand" class="font-mono font-semibold text-gray-900 hover:text-indigo-600" x-text="c.name + weightLabel(c.w)"></button>
                                    <span class="text-gray-600" x-text="c.made + (c.draw ? ' + ' + c.draw.toLowerCase() : '')"></span>
                                    <span class="ml-auto text-indigo-700" x-show="c.equity !== null" x-text="c.equity + '%'"></span>
                                </div>
                            </template>
                        </div>
                        <p class="mt-1 text-xs text-gray-400" x-show="detail && detail.combos.length === 0">None of this hand&rsquo;s combos are in the range on this board.</p>
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-400">Study tool only. Equity is calculated in your browser by dealing every possible turn and river card.</p>
        </div>

        {{-- popup: load a range from the Ranges section --}}
        <div x-show="presetOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-label="Load a range">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="presetOpen = false"></div>
            <div class="relative flex max-h-[85vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 p-5">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Load a range</h3>
                        <p class="mt-1 text-sm text-gray-500">Any chart of the Ranges section, loaded into <strong x-text="presetSide === 'hero' ? 'your range' : 'the opponent range'"></strong>.</p>
                    </div>
                    <button type="button" @click="presetOpen = false" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Close">&times;</button>
                </div>
                <div class="overflow-y-auto p-5 space-y-5">
                    <template x-for="study in presets" :key="study.name">
                        <div x-data="{ g: 0 }">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="study.name"></p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <template x-for="(group, gi) in study.groups" :key="group.label">
                                    <button type="button" @click="g = gi"
                                            class="rounded-full border px-3 py-1 text-xs font-semibold"
                                            :class="g === gi ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'">
                                        <span x-text="group.label"></span>
                                    </button>
                                </template>
                            </div>
                            <div class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200">
                                <template x-for="chart in (study.groups[g] ? study.groups[g].charts : [])" :key="chart.position + chart.stack">
                                    <div class="flex flex-wrap items-center gap-2 px-3 py-2">
                                        <div class="w-28 shrink-0">
                                            <div class="text-sm font-semibold text-gray-900" x-text="chart.position"></div>
                                            <div class="text-[11px] text-gray-400" x-text="chart.stack"></div>
                                        </div>
                                        <template x-for="option in chart.options" :key="option.label">
                                            <button type="button" @click="loadPreset(option)"
                                                    class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:border-indigo-500 hover:bg-indigo-50 hover:text-indigo-700">
                                                <span x-text="option.label"></span>
                                                <span class="ml-1 text-gray-400" x-text="option.hands + (option.hands === 1 ? ' hand' : ' hands')"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
