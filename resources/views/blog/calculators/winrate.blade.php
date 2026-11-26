<div class="not-prose my-10 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" x-data="winrateCalculator" data-calculator="winrate">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-900">{{ __('Poker win rate calculator') }}</h2>
        <div class="inline-flex rounded-lg bg-slate-100 p-1 text-sm">
            <button type="button" @click="unit = 'bb'" :class="unit === 'bb' ? 'bg-white text-slate-900 shadow' : 'text-slate-500 hover:text-slate-700'" class="rounded-md px-3 py-1.5 font-medium">{{ __('Big blinds') }}</button>
            <button type="button" @click="unit = 'money'" :class="unit === 'money' ? 'bg-white text-slate-900 shadow' : 'text-slate-500 hover:text-slate-700'" class="rounded-md px-3 py-1.5 font-medium">{{ __('Money') }}</button>
        </div>
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <div class="grid grid-cols-2 content-start gap-4">
            <x-calc-field :label="__('Net result')" model="net" :min="null" :help="__('Negative if you lost')" />
            <x-calc-field :label="__('Hands played')" model="hands" step="1" />
            <div x-show="unit === 'money'" style="display: none">
                <x-calc-field :label="__('Big blind')" model="bigBlind" :help="__('e.g. 0.50 at NL50')" />
            </div>
            <x-calc-field :label="__('Standard deviation')" model="stdDev" :suffix="__('bb/100')" :help="__('From your tracker; 80–100 is typical in 6-max NLHE')" />
            <x-calc-field :label="__('Hands per hour')" model="handsPerHour" step="1" :placeholder="__('Optional')" :help="__('All tables together')" />
        </div>

        <div class="rounded-xl bg-slate-50 p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Your win rate') }}</div>
            <div class="mt-1 text-3xl font-extrabold" :class="(result?.bb100 ?? 0) >= 0 ? 'text-indigo-600' : 'text-rose-600'">
                <span x-text="result ? signed(result.bb100) : '—'"></span>
                <span class="text-base font-semibold text-slate-500">{{ __('bb/100') }}</span>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-white p-3" x-show="unit === 'money'" style="display: none">
                    <dt class="text-slate-500">{{ __('Money per 100 hands') }}</dt>
                    <dd class="mt-0.5 text-lg font-bold text-slate-900" x-text="result?.money100 == null ? '—' : signed(result.money100)"></dd>
                </div>
                <div class="rounded-lg bg-white p-3" x-show="unit === 'money'" style="display: none">
                    <dt class="text-slate-500">{{ __('Net in big blinds') }}</dt>
                    <dd class="mt-0.5 text-lg font-bold text-slate-900" x-text="result ? signed(result.netBb, 0) : '—'"></dd>
                </div>
                <div class="rounded-lg bg-white p-3">
                    <dt class="text-slate-500">{{ __('Per hour') }}</dt>
                    <dd class="mt-0.5 text-lg font-bold text-slate-900">
                        <span x-text="result?.hourlyBb == null ? '—' : signed(result.hourlyBb)"></span>
                        <span class="text-xs font-medium text-slate-500" x-show="result?.hourlyBb != null">{{ __('bb') }}</span>
                    </dd>
                    <dd class="text-xs text-slate-500" x-show="unit === 'money' && result?.hourlyMoney != null" x-text="signed(result?.hourlyMoney)"></dd>
                </div>
                <div class="rounded-lg bg-white p-3">
                    <dt class="text-slate-500">{{ __('95% confidence range') }}</dt>
                    <dd class="mt-0.5 text-lg font-bold text-slate-900" x-text="result?.interval ? signed(result.interval.low, 1) + ' … ' + signed(result.interval.high, 1) : '—'"></dd>
                </div>
            </dl>

            <p class="mt-4 rounded-lg p-3 text-sm" x-show="verdict" :class="{ 'bg-emerald-50 text-emerald-800': verdict === 'winner', 'bg-rose-50 text-rose-800': verdict === 'loser', 'bg-amber-50 text-amber-800': verdict === 'unclear' }">
                <span x-show="verdict === 'winner'">{{ __('The whole range is above zero: at 95% confidence you are a winning player at these stakes.') }}</span>
                <span x-show="verdict === 'loser'" style="display: none">{{ __('The whole range is below zero: at 95% confidence you are losing at these stakes.') }}</span>
                <span x-show="verdict === 'unclear'" style="display: none">{{ __('The range still crosses zero: this sample cannot yet tell a winner from a losing player. Keep playing and recheck.') }}</span>
            </p>
        </div>
    </div>
</div>
