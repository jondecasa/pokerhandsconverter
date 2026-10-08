<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9;">
    <tr>
        <td align="center" style="padding:32px 16px;">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px;">

                {{-- Brand header --}}
                <tr>
                    <td style="background-color:#0f172a; border-radius:14px 14px 0 0; padding:22px 32px; border-bottom:4px solid #4f46e5;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="vertical-align:middle; padding-right:12px;">
                                    <img src="{{ url('images/icons/icon-192.png') }}" width="40" height="40" alt="" style="display:block; border-radius:10px;">
                                </td>
                                <td style="vertical-align:middle; font-size:20px; font-weight:800; letter-spacing:-0.3px; color:#ffffff;">
                                    PokerHands<span style="color:#818cf8;">Converter</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Message --}}
                <tr>
                    <td style="background-color:#ffffff; padding:36px 32px 32px; border-radius:0 0 14px 14px;">
                        <h1 style="margin:0 0 20px; font-size:24px; line-height:1.3; font-weight:700; color:#0f172a;">{{ $heading }}</h1>

                        @foreach ($paragraphs as $paragraph)
                            <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#334155;">{!! $paragraph !!}</p>
                        @endforeach

                        @if ($withButton)
                            <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:28px auto 8px;">
                                <tr>
                                    <td style="background-color:#4f46e5; border-radius:10px;">
                                        <a href="{{ $buttonUrl }}" style="display:inline-block; padding:13px 28px; font-size:16px; font-weight:600; color:#ffffff; text-decoration:none;">See the plans</a>
                                    </td>
                                </tr>
                            </table>
                        @endif

                        <p style="margin:28px 0 0; font-size:16px; line-height:1.65; color:#334155;">
                            Best,<br>
                            <strong style="color:#0f172a;">The PokerHandsConverter team</strong>
                        </p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:24px 16px 0; text-align:center; font-size:12px; line-height:1.6; color:#64748b;">
                        You're receiving this because you have an account at
                        <a href="{{ url('/') }}" style="color:#4f46e5; text-decoration:none;">pokerhandsconverter.com</a>.<br>
                        Just reply to this email if you'd rather not hear from us again.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
