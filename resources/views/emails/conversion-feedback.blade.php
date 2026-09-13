<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:#1e293b; line-height:1.6;">
    <h2 style="margin:0 0 16px;">PT4 import issue reported</h2>

    <p style="margin:0 0 4px;"><strong>From:</strong> {{ $reporter->name }} &lt;{{ $reporter->email }}&gt;</p>
    <p style="margin:0 0 4px;"><strong>File:</strong> {{ $conversion->original_filename }}</p>
    <p style="margin:0 0 4px;"><strong>Hands:</strong> {{ number_format($conversion->hand_count) }} ·
        <strong>Warnings:</strong> {{ $conversion->warning_count }} ·
        <strong>Converted:</strong> {{ $conversion->created_at->toDayDateTimeString() }}</p>
    <p style="margin:0 0 16px;"><strong>View it:</strong>
        <a href="{{ route('conversions.show', $conversion) }}">{{ route('conversions.show', $conversion) }}</a>
    </p>

    <div style="white-space:pre-wrap; border-left:3px solid #d97706; padding:8px 0 8px 16px; margin:0 0 16px;">{{ $body }}</div>

    <p style="font-size:12px; color:#64748b; margin:0;">Reply directly to this email to answer {{ $reporter->name }}.</p>
</body>
</html>
