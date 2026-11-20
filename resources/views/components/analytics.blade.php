{{-- Cloudflare Web Analytics: cookie-less, aggregate visit statistics. Off until CLOUDFLARE_ANALYTICS_TOKEN is set. --}}
@if (filled($token = config('services.cloudflare.analytics_token')))
    <script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon="{{ json_encode(['token' => $token]) }}"></script>
@endif
