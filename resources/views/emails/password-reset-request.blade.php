<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; font-family:Arial, sans-serif; color:#0f172a;">
    <div style="max-width:620px; margin:0 auto; padding:32px 18px;">
        <div style="background:#ffffff; border:1px solid #e5edf7; border-radius:16px; overflow:hidden;">
            <div style="padding:24px 28px; background:#eef6ff; border-bottom:1px solid #e5edf7;">
                <h2 style="margin:0; color:#0f3b7a;">Reset Your Password</h2>
            </div>

            <div style="padding:28px;">
                <p style="font-size:15px; line-height:1.7; margin-top:0;">
                    Hello {{ $user->name }},
                </p>

                <p style="font-size:15px; line-height:1.7;">
                    We received a request to reset the password for your InnovatEdge LMS account.
                    Click the button below to choose a new password.
                </p>

                <p style="text-align:center; margin:30px 0;">
                    <a href="{{ $resetUrl }}"
                       style="display:inline-block; background:#0f3b7a; color:#ffffff; text-decoration:none; padding:13px 24px; border-radius:10px; font-weight:bold;">
                        Reset Password
                    </a>
                </p>

                <p style="font-size:13px; line-height:1.7; color:#64748b;">
                    This link will expire at {{ $expiresAt }}. If you did not request this reset, you can ignore this email and your password will remain unchanged.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
