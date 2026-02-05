<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - {{ $appName }}</title>
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
        .details {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .detail-row {
            margin-bottom: 12px;
        }
        .detail-label {
            font-weight: 600;
            color: #667eea;
        }
        .detail-value {
            color: #333;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>You're Invited!</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Hello <strong>{{ $invitation->name }}</strong>,</p>
                <p>You've been invited to join The Bold East Africa platform. We're excited to have you on our team!</p>
            </div>

            <div class="details">
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $invitation->email }}</span>
                </div>
                @if($invitation->role)
                <div class="detail-row">
                    <span class="detail-label">Role:</span>
                    <span class="detail-value">{{ $invitation->role }}</span>
                </div>
                @endif
                @if($invitation->department)
                <div class="detail-row">
                    <span class="detail-label">Department:</span>
                    <span class="detail-value">{{ $invitation->department }}</span>
                </div>
                @endif
            </div>

            <div class="otp-box">
                <div class="otp-label">Your Verification Code (OTP)</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <div class="warning">
                <strong>⏰ This code expires in {{ $expiresIn }}</strong>
                <p>Keep this code safe and don't share it with anyone.</p>
            </div>

            <p><strong>To complete your registration:</strong></p>
            <ol>
                <li>Click the button below or visit The Bold East Africa platform</li>
                <li>Enter the verification code above</li>
                <li>Create a secure password</li>
                <li>Complete your profile setup</li>
            </ol>

            <a href="{{ $appUrl }}/accept-invitation" class="cta-button">Accept Invitation</a>

            <div class="divider"></div>

            <p>If you didn't expect this invitation, you can safely ignore this email.</p>

            <p>Best regards,<br><strong>The Bold East Africa Team</strong></p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
