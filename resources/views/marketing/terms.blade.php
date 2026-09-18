<x-marketing-layout
    title="Terms of Service — PokerHandsConverter"
    description="The terms governing use of PokerHandsConverter's CoinPoker hand-history conversion service.">
    <section class="mx-auto max-w-3xl px-6 py-16">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Terms of Service</h1>
        <p class="mt-2 text-sm text-slate-400">Last updated {{ date('F Y') }}</p>

        <div class="prose prose-slate mt-8 max-w-none text-sm leading-relaxed text-slate-600">
            <p><strong>Template notice:</strong> this is starter wording. Have it reviewed by a lawyer and
            replace the bracketed placeholders before going live.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">1. Service</h2>
            <p>PokerHandsConverter ("the Service", "we") converts poker hand-history
            text files from CoinPoker's export format into the layout that PokerTracker 4 and similar
            tracking software import. We are not affiliated with CoinPoker, PokerTracker, Hold'em Manager
            or any tracker vendor.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">2. Accounts</h2>
            <p>You are responsible for activity under your account and for keeping your credentials secure.
            You must be of legal age to form a contract in your jurisdiction.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">3. Subscriptions and billing</h2>
            <p>Paid features require an active subscription billed through Stripe on a recurring basis until
            cancelled. Cancelling stops future renewals; access continues until the end of the paid period.
            Fees are exclusive of taxes where applicable. Refund requests are considered within 14 days of a
            charge where the Service did not work for your files and we were unable to resolve it.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">4. Acceptable use</h2>
            <p>Do not upload content you have no right to process, attempt to disrupt the Service, or resell
            access without permission. We may suspend accounts that abuse the Service.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">5. No warranty</h2>
            <p>Conversions are provided "as is". Hand-history formats change and edge cases exist; you are
            responsible for verifying imported data. We are not liable for decisions made based on converted
            files, to the maximum extent permitted by law.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">6. Changes</h2>
            <p>We may update these Terms. Material changes will be announced in-app or by email. Continued use
            after changes take effect constitutes acceptance.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">7. Contact</h2>
            <p>Questions: {{ config('pokerhandsconverter.contact_email') }}.</p>
        </div>
    </section>
</x-marketing-layout>
