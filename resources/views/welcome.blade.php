@php($trialDays = $plans->max(fn ($p) => $p->effectiveTrialDays()) ?? 0)

<x-marketing-layout>

    {{-- ============================ HERO ============================ --}}
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="pointer-events-none absolute inset-0 opacity-[0.15]"
             style="background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:48px 48px;"></div>
        <div class="pointer-events-none absolute -top-40 left-1/2 h-96 w-[42rem] -translate-x-1/2 rounded-full bg-indigo-600/30 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-20 lg:py-28">
            <div class="grid items-center gap-14 lg:grid-cols-2">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-medium text-indigo-200">
                        Cash games &amp; tournaments · HM3 · PT4 · Hand2Note
                    </span>
                    <h1 class="mt-5 text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl">
                        Your CoinPoker hands,<br>
                        <span class="text-indigo-400">readable by your tracker.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-lg text-slate-300">
                        CoinPoker exports hand histories that Hold'em Manager and PokerTracker&nbsp;4 refuse to import.
                        PokerCoinverter rewrites them in seconds into a format PokerTracker&nbsp;4 reads &mdash; header,
                        currency, timezone and stakes, all fixed automatically.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-indigo-600 px-6 py-3.5 text-center text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 hover:bg-indigo-500">
                                Open the converter
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3.5 text-center text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 hover:bg-indigo-500">
                                @if ($trialDays > 0) Start your {{ $trialDays }}-day free trial @else Create your account @endif
                            </a>
                        @endauth
                        <a href="#how" class="rounded-xl border border-white/15 px-6 py-3.5 text-center text-sm font-semibold text-white hover:bg-white/5">
                            See how it works
                        </a>
                    </div>

                    <p class="mt-4 text-xs text-slate-400">
                        @if ($trialDays > 0) No card required for the trial. @endif Cancel anytime.
                    </p>
                </div>

                {{-- before / after card --}}
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-2 shadow-2xl backdrop-blur">
                    <div class="flex items-center gap-1.5 px-3 py-2">
                        <span class="h-3 w-3 rounded-full bg-red-400/80"></span>
                        <span class="h-3 w-3 rounded-full bg-amber-400/80"></span>
                        <span class="h-3 w-3 rounded-full bg-emerald-400/80"></span>
                        <span class="ml-3 text-xs text-slate-400">HH20240310.txt</span>
                    </div>
                    <pre class="overflow-x-auto rounded-xl bg-slate-950 p-4 text-[11px] leading-relaxed sm:text-xs"><code><span class="text-red-400">- CoinPoker Hand #130114200045: NLH (₮0.01/₮0.02) 2026/09/09 12:01:21 CEST</span>
<span class="text-emerald-400">+ ...Hand #130114200045:  Hold'em No Limit ($0.01/$0.02 USD) - 2026/09/09 12:01:21 CET [... ET]</span>
<span class="text-red-400">- Seat 5: Hero (₮2 in chips)</span>
<span class="text-emerald-400">+ Seat 5: Hero ($2 in chips)</span>
<span class="text-red-400">- Dealt to 3d1b2c99</span>
<span class="text-red-400">- Dealt to d0077f71</span>
<span class="text-slate-500">  Dealt to Hero [Th 2s]</span>
<span class="text-red-400">- 3d2ba04f: raises ₮0.04 to ₮0.06</span>
<span class="text-emerald-400">+ 3d2ba04f: raises $0.04 to $0.06</span>
<span class="text-red-400">- 3d2ba04f: RETURN ₮0.04</span>
<span class="text-emerald-400">+ Uncalled bet ($0.04) returned to 3d2ba04f</span>
<span class="text-red-400">- Seat 3: 3d2ba04f won (₮0.05)</span>
<span class="text-emerald-400">+ Seat 3: 3d2ba04f (button) collected ($0.05)</span></code></pre>
                    <p class="px-3 pb-1 pt-2 text-xs text-slate-500">Header, game code, currency and timezone rewritten to what PokerTracker&nbsp;4 expects.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ TRUST BAR ============================ --}}
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-6 py-8">
            <p class="text-center text-xs font-semibold uppercase tracking-widest text-slate-400">
                Formatted for the trackers you already use
            </p>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-sm font-semibold text-slate-500">
                <span>Hold'em Manager 3</span>
                <span>PokerTracker 4</span>
                <span>Hand2Note</span>
                <span>DriveHUD</span>
                <span>PokerBankroll trackers</span>
            </div>
        </div>
    </section>

    {{-- ============================ PROBLEM ============================ --}}
    <section class="mx-auto max-w-7xl px-6 py-20">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                CoinPoker's export is <span class="text-red-500">almost</span> right &mdash; and "almost" breaks your HUD
            </h2>
            <p class="mt-4 text-lg text-slate-600">
                The layout looks familiar, but four small differences are enough for trackers to reject the file
                or import it with wrong numbers.
            </p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['The header', 'Files start with "CoinPoker Hand #" and use codes like "NLH". PokerTracker 4 looks for a different header and the full game name "Hold\'em No Limit".'],
                ['The ₮ sign', 'Amounts use the USDT tether sign (₮0.02) with no currency code. The tracker expects $0.02 and a code like USD.'],
                ['Extra "Dealt to" lines', 'CoinPoker prints a "Dealt to" line for every player. The tracker only wants the hero\'s cards — the rest confuse the parser.'],
                ['Timezone & noise', 'Times are CEST, not the CET/ET stamp the tracker expects, plus "Hand was run once" and "Game ended:" lines that do not belong in the format.'],
            ] as [$t, $d])
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <div class="text-sm font-semibold text-red-500">{{ $t }}</div>
                    <p class="mt-2 text-sm text-slate-600">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================ HOW IT WORKS ============================ --}}
    <section id="how" class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Three steps, about ten seconds</h2>
                <p class="mt-4 text-lg text-slate-600">Log in, upload, download. The conversion runs in your account &mdash; nothing to install.</p>
            </div>

            <div class="mt-14 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['1', 'Upload your file', 'Drop in the .txt CoinPoker exported. Cash games and tournaments, single hands or full sessions.'],
                    ['2', 'We reformat it', 'Header and game code, the ₮ sign &rarr; $, the timezone stamp, the "Dealt to" noise and the summary lines are all rewritten to what PokerTracker 4 reads. Tournament chip counts stay untouched.'],
                    ['3', 'Import and review', 'Download the converted .txt, point PokerTracker 4 at it, and your HUD lights up. Past conversions stay in your history to re-download.'],
                ] as [$n, $t, $d])
                    <div class="relative rounded-2xl border border-slate-200 bg-white p-8">
                        <div class="grid h-10 w-10 place-items-center rounded-full bg-indigo-600 text-sm font-bold text-white">{{ $n }}</div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $t }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{!! $d !!}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                @auth
                    <a href="{{ route('convert.create') }}" class="inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-500">Go to the converter</a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-500">Create an account to start</a>
                @endauth
            </div>
        </div>
    </section>

    {{-- ============================ FEATURES ============================ --}}
    <section id="features" class="mx-auto max-w-7xl px-6 py-20">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Built for real hand histories</h2>
            <p class="mt-4 text-lg text-slate-600">Not a find-and-replace. It understands the structure of a poker hand.</p>
        </div>

        <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Cash &amp; tournaments', 'Detects the format per hand. Cash gets dollar amounts; tournaments keep bare chip counts, the way PokerTracker 4 expects a tourney.'],
                ['Accurate money fixes', 'Blinds, antes, straddles, bets, raises, uncalled bets, collected pots, rake and the summary line &mdash; each amount fixed in context, never double-prefixed.'],
                ['Timezone your way', 'Output the European dual stamp (local time + "[… ET]"), a single Eastern-time stamp, or leave CoinPoker\'s time untouched. You choose per upload.'],
                ['Honest warnings', 'Run-it-twice boards, unparseable timestamps and stray blocks are flagged &mdash; the converter never silently guesses.'],
                ['Conversion history', 'Every file you convert is kept in your account with hand counts and warnings, ready to re-download.'],
                ['Your data stays yours', 'Files are processed for your account only and never shared. Card details go straight to Stripe &mdash; we never see them.'],
            ] as [$t, $d])
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-50 text-indigo-600">
                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-slate-900">{!! $t !!}</h3>
                    <p class="mt-2 text-sm text-slate-600">{!! $d !!}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================ PRICING ============================ --}}
    <section id="pricing" class="bg-slate-50 py-20">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Simple pricing</h2>
                <p class="mt-4 text-lg text-slate-600">
                    Unlimited conversions on every plan.
                    @if ($trialDays > 0) Start with a {{ $trialDays }}-day free trial. @endif
                </p>
            </div>

            <div class="mx-auto mt-12 grid max-w-3xl gap-6 sm:grid-cols-2">
                @forelse ($plans as $plan)
                    <div class="flex flex-col rounded-2xl border bg-white p-8 {{ $plan->is_highlighted ? 'border-indigo-600 ring-1 ring-indigo-600' : 'border-slate-200' }}">
                        @if ($plan->is_highlighted)
                            <span class="mb-3 inline-flex w-max rounded-full bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">Best value</span>
                        @endif
                        <div class="text-sm font-semibold text-slate-900">{{ $plan->name }}</div>
                        <div class="mt-2">
                            <span class="text-4xl font-extrabold text-slate-900">{{ $plan->priceLabel() }}</span>
                            @unless ($plan->isFree())<span class="text-slate-500">/ {{ $plan->interval }}</span>@endunless
                        </div>
                        @if ($plan->stakesText())
                            <div class="mt-2 inline-flex w-max rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $plan->stakesText() }}</div>
                        @endif
                        <p class="mt-3 text-sm text-slate-600">{{ $plan->description }}</p>
                        <ul class="mt-5 flex-1 space-y-2 text-sm text-slate-700">
                            @forelse ($plan->featureList() as $feature)
                                <li>&#10003; {{ $feature }}</li>
                            @empty
                                <li>&#10003; Unlimited CoinPoker &rarr; PokerTracker&nbsp;4 conversions</li>
                            @endforelse
                        </ul>
                        @auth
                            <a href="{{ route('subscription.plans') }}" class="mt-6 rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500">Choose {{ $plan->name }}</a>
                        @else
                            <a href="{{ route('register') }}" class="mt-6 rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500">
                                @if ($plan->effectiveTrialDays() > 0) Start free trial @else Get started @endif
                            </a>
                        @endauth
                    </div>
                @empty
                    <p class="col-span-full text-center text-slate-500">Packages are being set up — check back soon.</p>
                @endforelse
            </div>
            <p class="mt-6 text-center text-xs text-slate-400">Payments processed by Stripe.</p>
        </div>
    </section>

    {{-- ============================ FAQ ============================ --}}
    <section id="faq" class="mx-auto max-w-3xl px-6 py-20">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Questions</h2>
        <div class="mt-10 divide-y divide-slate-200 border-y border-slate-200">
            @foreach ([
                ['Does this work with PokerTracker 4 (and Hold\'em Manager 3)?', 'Yes. The output is the standard hand-history text these trackers bulk-import. Point PokerTracker 4\'s import at the converted file.'],
                ['Cash games and tournaments both?', 'Both. Each hand is detected individually. Cash hands get dollar amounts added; tournament hands keep bare chip counts, the way the tracker expects a tourney.'],
                ['What about the timezone / my time-based stats?', 'You pick per upload: keep the local time and add the "[… ET]" stamp (default), output a single Eastern-time stamp, or leave CoinPoker\'s time untouched. Nothing changes without you choosing it.'],
                ['Is my hand history data safe?', 'Files are converted for your account only and are never shared or sold. You can re-download or ignore past conversions. Payment details are handled entirely by Stripe.'],
                ['Can I cancel?', 'Anytime, from your dashboard. You keep access until the end of the period you already paid for.'],
                ['A hand didn\'t import cleanly. Now what?', 'The converter flags anything unusual (like run-it-twice boards) as a warning on the result page. Send us the flagged hand and we\'ll tune the rules.'],
            ] as [$q, $a])
                <details class="group py-5" x-data>
                    <summary class="flex cursor-pointer list-none items-center justify-between text-left font-semibold text-slate-900">
                        {{ $q }}
                        <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    </summary>
                    <p class="mt-3 text-sm text-slate-600">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- ============================ CTA ============================ --}}
    <section class="bg-indigo-600">
        <div class="mx-auto max-w-4xl px-6 py-16 text-center">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Stop fighting your import folder</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-indigo-100">
                Get your CoinPoker results into your tracker where they belong.
            </p>
            <div class="mt-8">
                @auth
                    <a href="{{ route('convert.create') }}" class="inline-flex rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Open the converter</a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
                        @if ($trialDays > 0) Start your {{ $trialDays }}-day free trial @else Create your account @endif
                    </a>
                @endauth
            </div>
        </div>
    </section>

</x-marketing-layout>
