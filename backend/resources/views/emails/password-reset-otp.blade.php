<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - {{ $appName }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .otp-box {
            background-color: #f0f0f0;
            border: 2px dashed #667eea;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin: 20px 0;
        }
        .otp-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .otp-code {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning strong {
            color: #856404;
        }
        .steps {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 10px;
            color: #555;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
            text-align: center;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }
        .divider {
            border-top: 1px solid #eee;
            margin: 20px 0;
        }
        .security-note {
            background-color: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
        }
        .security-note strong {
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Hello <strong>{{ $user->name }}</strong>,</p>
                <p>We received a request to reset your password for your The Bold East Africa account. If you didn't make this request, you can safely ignore this email.</p>
            </div>

            <div class="otp-box">
                <div class="otp-label">Your One-Time Password (OTP)</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <div class="warning">
                <strong>⏰ This code expires in {{ $expiresIn }}</strong>
                <p>Do not share this code with anyone. Our team will never ask for it.</p>
            </div>

            <div class="steps">
                <p><strong>How to reset your password:</strong></p>
                <ol>
                    <li>Go to the password reset page on The Bold East Africa platform</li>
                    <li>Enter your email address ({{ $user->email }})</li>
                    <li>Enter the OTP code: <strong>{{ $otp }}</strong></li>
                    <li>Create a new, strong password</li>
                    <li>Confirm your new password</li>
                </ol>
            </div>

            <a href="{{ $appUrl }}/forgot-password" class="cta-button">Reset Password</a>

            <div class="security-note">
                <strong>🔒 Security Tip:</strong> Always use a strong password with at least 8 characters, including uppercase and lowercase letters, numbers, and special characters.
            </div>

            <div class="divider"></div>

            <p><strong>Didn't request a password reset?</strong></p>
            <p>If you didn't request this password reset, your account may have been compromised. Please:</p>
            <ul>
                <li>Change your password immediately if you have access</li>
                <li>Contact our support team immediately</li>
                <li>Review your account activity for any unauthorized changes</li>
            </ul>

            <p>Best regards,<br><strong>The Bold East Africa Security Team</strong></p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email. Contact support@belfortech.dev for assistance.</p>
        </div>
    </div>
</body>
</html>
