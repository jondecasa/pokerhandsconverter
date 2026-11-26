<div class="not-prose my-10 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" x-data="evCalculator" data-calculator="ev">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-900">{{ __('Poker EV calculator') }}</h2>
        <div class="inline-flex rounded-lg bg-slate-100 p-1 text-sm" role="tablist">
            <button type="button" role="tab" :aria-selected="mode === 'bet'" @click="mode = 'bet'" :class="mode === 'bet' ? 'bg-white text-slate-900 shadow' : 'text-slate-500 hover:text-slate-700'" class="rounded-md px-3 py-1.5 font-medium">{{ __('Bet or bluff') }}</button>
            <button type="button" role="tab" :aria-selected="mode === 'call'" @click="mode = 'call'" :class="mode === 'call' ? 'bg-white text-slate-900 shadow' : 'text-slate-500 hover:text-slate-700'" class="rounded-md px-3 py-1.5 font-medium">{{ __('Call') }}</button>
        </div>
    </div>

    {{-- Bet or bluff --}}
    <div x-show="mode === 'bet'" class="mt-6 grid gap-6 md:grid-cols-2">
        <div class="grid grid-cols-2 content-start gap-4">
            <x-calc-field :label="__('Pot')" model="pot" :help="__('Before your bet')" />
            <x-calc-field :label="__('Your bet')" model="bet" />
            <x-calc-field :label="__('Villain folds')" model="fold" suffix="%" max="100" />
            <x-calc-field :label="__('Villain raises')" model="raise" suffix="%" max="100" :help="__('You fold to a raise')" />
            <x-calc-field :label="__('Your equity when called')" model="equity" suffix="%" max="100" />
            <x-calc-field :label="__('Your equity if you check')" model="checkEquity" suffix="%" max="100" :help="__('Share of the pot you win checking')" />
        </div>

        <div class="rounded-xl bg-slate-50 p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Best line') }}</div>
            <div class="mt-1 text-2xl font-extrabold" :class="betResult.best === 'bet' ? 'text-indigo-600' : 'text-slate-900'">
                <span x-show="betResult.best === 'bet'">{{ __('Bet') }}</span>
                <span x-show="betResult.best === 'check'" style="display: none">{{ __('Check') }}</span>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-white p-3">
                    <dt class="text-slate-500">{{ __('EV of betting') }}</dt>
                    <dd class="mt-0.5 text-lg font-bold" :class="betResult.ev >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="signed(betResult.ev)"></dd>
                </div>
                <div class="rounded-lg bg-white p-3">
                    <dt class="text-slate-500">{{ __('EV of checking') }}</dt>
                    <dd class="mt-0.5 text-lg font-bold text-slate-900" x-text="signed(betResult.check)"></dd>
                </div>
            </dl>

            <div class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Where the bet EV comes from') }}</div>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                <li class="flex justify-between"><span>{{ __('Villain folds') }}</span><span class="font-medium" x-text="signed(betResult.parts.fold)"></span></li>
                <li class="flex justify-between"><span>{{ __('Villain calls') }} (<span x-text="percent(betResult.callFreq, 0)"></span>)</span><span class="font-medium" x-text="signed(betResult.parts.call)"></span></li>
                <li class="flex justify-between"><span>{{ __('Villain raises') }}</span><span class="font-medium" x-text="signed(betResult.parts.raise)"></span></li>
            </ul>

            <ul class="mt-4 space-y-1 border-t border-slate-200 pt-3 text-sm text-slate-700">
                <li class="flex justify-between gap-3"><span>{{ __('A pure bluff needs folds') }}</span><span class="font-medium" x-text="percent(betResult.bluffBreakEven)"></span></li>
                <li class="flex justify-between gap-3"><span>{{ __('Equity needed when called') }}</span><span class="font-medium" x-text="betResult.neededEquity === null ? '—' : percent(betResult.neededEquity)"></span></li>
            </ul>
        </div>
    </div>

    {{-- Call --}}
    <div x-show="mode === 'call'" style="display: none" class="mt-6 grid gap-6 md:grid-cols-2">
        <div class="grid grid-cols-2 content-start gap-4">
            <x-calc-field :label="__('Pot')" model="callPot" :help="__('Including the bet you face')" />
            <x-calc-field :label="__('Amount to call')" model="toCall" />
            <x-calc-field :label="__('Your equity')" model="callEquity" suffix="%" max="100" />
        </div>

        <div class="rounded-xl bg-slate-50 p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Best line') }}</div>
            <div class="mt-1 text-2xl font-extrabold" :class="callResult.best === 'call' ? 'text-indigo-600' : 'text-slate-900'">
                <span x-show="callResult.best === 'call'">{{ __('Call') }}</span>
                <span x-show="callResult.best === 'fold'" style="display: none">{{ __('Fold') }}</span>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-white p-3">
                    <dt class="text-slate-500">{{ __('EV of calling') }}</dt>
                    <dd class="mt-0.5 text-lg font-bold" :class="callResult.ev >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="signed(callResult.ev)"></dd>
                </div>
                <div class="rounded-lg bg-white p-3">
                    <dt class="text-slate-500">{{ __('Equity needed') }}</dt>
                    <dd class="mt-0.5 text-lg font-bold text-slate-900" x-text="percent(callResult.neededEquity)"></dd>
                </div>
            </dl>
            <ul class="mt-4 space-y-1 text-sm text-slate-700">
                <li class="flex justify-between gap-3"><span>{{ __('Pot odds') }}</span><span class="font-medium" x-text="callResult.potOdds === null ? '—' : format(callResult.potOdds, 2) + ' : 1'"></span></li>
                <li class="flex justify-between gap-3"><span>{{ __('Extra you must win later to break even') }}</span><span class="font-medium" x-text="callResult.impliedNeeded === null ? '—' : format(callResult.impliedNeeded)"></span></li>
            </ul>
        </div>
    </div>

    <p class="mt-5 text-xs text-slate-500">{{ __('Use big blinds or money, as long as every amount is in the same unit. The bet line assumes you fold when raised; the check line assumes you win your check equity of the current pot.') }}</p>
</div>
