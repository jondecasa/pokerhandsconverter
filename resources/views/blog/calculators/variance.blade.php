<div class="not-prose my-10 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" x-data="varianceCalculator" data-calculator="variance">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-900">{{ __('Poker variance calculator') }}</h2>
        <button type="button" @click="simulate()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:border-indigo-400 hover:text-indigo-700">{{ __('Simulate again') }}</button>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-calc-field :label="__('Win rate')" model="winRate" :min="null" :suffix="__('bb/100')" />
        <x-calc-field :label="__('Standard deviation')" model="stdDev" :suffix="__('bb/100')" />
        <x-calc-field :label="__('Hands')" model="hands" step="1000" max="10000000" />
        <x-calc-field :label="__('Bankroll')" model="bankroll" :suffix="__('bb')" />
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
        <svg :viewBox="`0 0 ${chart.width} ${chart.height}`" viewBox="0 0 640 260" class="block h-auto w-full" role="img" aria-label="{{ __('Expected result with 68% and 95% ranges and five simulated runs') }}">
            <polygon :points="plot.band95" fill="#e0e7ff" />
            <polygon :points="plot.band68" fill="#c7d2fe" />
            <line :x1="plot.left" :x2="plot.right" :y1="plot.zeroY" :y2="plot.zeroY" stroke="#94a3b8" stroke-dasharray="4 4" />
            <path :d="plot.runs[0]?.d ?? ''" fill="none" stroke="#0ea5e9" stroke-width="1.2" opacity="0.85" />
            <path :d="plot.runs[1]?.d ?? ''" fill="none" stroke="#f59e0b" stroke-width="1.2" opacity="0.85" />
            <path :d="plot.runs[2]?.d ?? ''" fill="none" stroke="#10b981" stroke-width="1.2" opacity="0.85" />
            <path :d="plot.runs[3]?.d ?? ''" fill="none" stroke="#ef4444" stroke-width="1.2" opacity="0.85" />
            <path :d="plot.runs[4]?.d ?? ''" fill="none" stroke="#8b5cf6" stroke-width="1.2" opacity="0.85" />
            <path :d="plot.expected" fill="none" stroke="#312e81" stroke-width="2" />
            <text :x="plot.left - 6" :y="plot.ticks[0].y + 4" text-anchor="end" font-size="11" fill="#64748b" x-text="plot.ticks[0].label"></text>
            <text :x="plot.left - 6" :y="plot.ticks[1].y + 4" text-anchor="end" font-size="11" fill="#64748b" x-text="plot.ticks[1].label"></text>
            <text :x="plot.left - 6" :y="plot.ticks[2].y + 4" text-anchor="end" font-size="11" fill="#64748b" x-text="plot.ticks[2].label"></text>
            <text :x="plot.left" :y="plot.bottom + 18" font-size="11" fill="#64748b">0</text>
            <text :x="plot.right" :y="plot.bottom + 18" text-anchor="end" font-size="11" fill="#64748b"><tspan x-text="format(inputs.hands, 0)"></tspan> {{ __('hands') }}</text>
        </svg>
        <div class="flex flex-wrap gap-x-4 gap-y-1 border-t border-slate-200 bg-slate-50 px-4 py-2 text-xs text-slate-600">
            <span class="inline-flex items-center gap-1.5"><span class="h-0.5 w-4 bg-indigo-900"></span>{{ __('Expected (bb)') }}</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-4 rounded-sm bg-indigo-200"></span>{{ __('68% of results') }}</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-4 rounded-sm bg-indigo-100"></span>{{ __('95% of results') }}</span>
            <span>{{ __('Coloured lines: five random runs with the same win rate') }}</span>
        </div>
    </div>

    <dl class="mt-6 grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
        <div class="rounded-lg bg-slate-50 p-3">
            <dt class="text-slate-500">{{ __('Expected result') }}</dt>
            <dd class="mt-0.5 text-lg font-bold text-slate-900"><span x-text="signed(result.mean, 0)"></span> <span class="text-xs font-medium text-slate-500">{{ __('bb') }}</span></dd>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <dt class="text-slate-500">{{ __('68% of the time between') }}</dt>
            <dd class="mt-0.5 whitespace-nowrap text-sm font-bold text-slate-900 sm:text-lg" x-text="signed(result.range68.low, 0) + ' … ' + signed(result.range68.high, 0)"></dd>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <dt class="text-slate-500">{{ __('95% of the time between') }}</dt>
            <dd class="mt-0.5 whitespace-nowrap text-sm font-bold text-slate-900 sm:text-lg" x-text="signed(result.range95.low, 0) + ' … ' + signed(result.range95.high, 0)"></dd>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <dt class="text-slate-500">{{ __('Chance of being down') }}</dt>
            <dd class="mt-0.5 text-lg font-bold" :class="result.lossChance > 0.2 ? 'text-rose-600' : 'text-slate-900'" x-text="percent(result.lossChance)"></dd>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <dt class="text-slate-500">{{ __('Hands to be 95% sure you win') }}</dt>
            <dd class="mt-0.5 text-lg font-bold text-slate-900" x-text="result.handsForConfidence === null ? '—' : format(result.handsForConfidence, 0)"></dd>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <dt class="text-slate-500">{{ __('Risk of ruin') }}</dt>
            <dd class="mt-0.5 text-lg font-bold" :class="(result.riskOfRuin ?? 0) > 0.05 ? 'text-rose-600' : 'text-slate-900'" x-text="result.riskOfRuin === null ? '—' : percent(result.riskOfRuin, 2)"></dd>
        </div>
        <div class="col-span-2 rounded-lg bg-slate-50 p-3">
            <dt class="text-slate-500">{{ __('Bankroll for a 5% risk of ruin') }}</dt>
            <dd class="mt-0.5 text-lg font-bold text-slate-900">
                <span x-text="result.bankrollFor5 === null ? '—' : format(result.bankrollFor5, 0)"></span>
                <span class="text-xs font-medium text-slate-500" x-show="result.bankrollFor5 !== null">{{ __('bb') }} (<span x-text="result.bankrollFor5 === null ? '' : format(result.bankrollFor5 / 100, 0)"></span> {{ __('buy-ins') }})</span>
            </dd>
        </div>
    </dl>

    <p class="mt-5 text-xs text-slate-500">{{ __('Assumes your true win rate and standard deviation are known and results follow a normal distribution. Risk of ruin is the chance of ever losing the whole bankroll at a constant win rate.') }}</p>
</div>
