<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your PsyClub email address</title>
</head>
<body style="margin: 0; background: #000; color: #f4f4f5; font-family: Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; padding: 40px 24px;">
        <h1 style="margin: 0 0 16px;">Verify your PsyClub email address</h1>
        <p style="color: #a1a1aa;">Hello {{ $user->name }},</p>
        <p style="color: #a1a1aa;">Click the button below to verify your email and activate your PsyClub administrator access.</p>
        <p style="margin: 28px 0;">
            <a href="{{ $verificationUrl }}" style="display: inline-block; padding: 12px 20px; background: #fff; color: #000; text-decoration: none; border-radius: 6px;">Verify email address</a>
        </p>
        <p style="color: #71717a; font-size: 13px;">If you did not create this account, you can ignore this email.</p>
    </div>
</body>
</html>
