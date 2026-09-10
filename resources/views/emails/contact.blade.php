<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:#1e293b; line-height:1.6;">
    <h2 style="margin:0 0 16px;">New contact form message</h2>

    <p style="margin:0 0 4px;"><strong>From:</strong> {{ $senderName }} &lt;{{ $senderEmail }}&gt;</p>
    <p style="margin:0 0 16px;"><strong>Subject:</strong> {{ $subjectLine }}</p>

    <div style="white-space:pre-wrap; border-left:3px solid #6366f1; padding:8px 0 8px 16px; margin:0 0 16px;">{{ $body }}</div>

    <p style="font-size:12px; color:#64748b; margin:0;">Reply directly to this email to answer {{ $senderName }}.</p>
</body>
</html>
