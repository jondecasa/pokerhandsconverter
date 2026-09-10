<x-marketing-layout title="Privacy Policy — PokerHandsConverter">
    <section class="mx-auto max-w-3xl px-6 py-16">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Privacy Policy</h1>
        <p class="mt-2 text-sm text-slate-400">Last updated {{ date('F Y') }}</p>

        <div class="prose prose-slate mt-8 max-w-none text-sm leading-relaxed text-slate-600">
            <p><strong>Template notice:</strong> starter wording. Review with a professional and replace the
            bracketed placeholders before publishing.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">What we collect</h2>
            <ul>
                <li><strong>Account data:</strong> your name and email address.</li>
                <li><strong>Uploaded files:</strong> the hand-history files you submit and the converted
                output, stored so you can re-download past conversions.</li>
                <li><strong>Usage metadata:</strong> conversion counts, hand counts and warnings.</li>
                <li><strong>Billing data:</strong> handled by Stripe. We receive subscription status and the
                last four digits / card brand, never full card numbers.</li>
            </ul>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">How we use it</h2>
            <p>To provide the conversion service, keep your history, process subscriptions, provide support,
            and improve conversion accuracy. We do not sell your data or share hand histories with third
            parties.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">Processors</h2>
            <p>Stripe (payments).</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">Retention &amp; deletion</h2>
            <p>Converted files are kept until you delete them or close your account. Email
            {{ config('pokerhandsconverter.contact_email') }} to request deletion of your account and associated files.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">Your rights</h2>
            <p>Depending on your jurisdiction you may have rights to access, correct, export or delete your
            personal data. Contact us to exercise them.</p>

            <h2 class="mt-8 text-lg font-semibold text-slate-900">Contact</h2>
            <p>{{ config('pokerhandsconverter.contact_email') }}</p>
        </div>
    </section>
</x-marketing-layout>
