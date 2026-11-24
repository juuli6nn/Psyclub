<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership application received</title>
</head>
<body style="margin: 0; background: #000; color: #f4f4f5; font-family: Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; padding: 40px 24px;">
        <h1 style="margin: 0 0 16px;">Membership application received</h1>
        <p style="color: #a1a1aa;">Hello {{ $application->full_name }},</p>
        <p style="color: #a1a1aa;">We received your PsyClub membership application.</p>
        <p style="color: #a1a1aa;"><strong>Reference number:</strong> {{ $application->reference_no }}</p>
        <p style="color: #a1a1aa;"><strong>Status:</strong> Pending verification</p>
        <p style="color: #71717a; font-size: 13px;">Keep your reference number for your records.</p>
    </div>
</body>
</html>
