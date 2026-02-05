<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $appName }}</title>
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
        .password-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .password-warning strong {
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
            <h1>Welcome to {{ $appName }}</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Hello <strong>{{ $user->name }}</strong>,</p>
                <p>Welcome to The Bold East Africa! We're excited to have you on board. Your account has been successfully created.</p>
            </div>

            <div class="details">
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $user->email }}</span>
                </div>
                @if($user->role)
                <div class="detail-row">
                    <span class="detail-label">Role:</span>
                    <span class="detail-value">{{ $user->role }}</span>
                </div>
                @endif
                @if($user->department)
                <div class="detail-row">
                    <span class="detail-label">Department:</span>
                    <span class="detail-value">{{ $user->department }}</span>
                </div>
                @endif
            </div>

            @if($temporaryPassword)
            <div class="password-warning">
                <strong>Important Security Note:</strong>
                <p>A temporary password has been generated for you: <code>{{ $temporaryPassword }}</code></p>
                <p>Please change this password immediately after your first login for security reasons.</p>
            </div>
            @endif

            <p>You can now access The Bold East Africa platform and start creating amazing content!</p>

            <a href="{{ $appUrl }}/login" class="cta-button">Go to Platform</a>

            <div class="divider"></div>

            <p><strong>Getting Started:</strong></p>
            <ul>
                <li>Complete your profile with a profile picture</li>
                <li>Explore the dashboard to familiarize yourself with the platform</li>
                <li>Start creating or editing content based on your role</li>
                <li>Check out our help documentation if you have any questions</li>
            </ul>

            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

            <p>Best regards,<br><strong>The Bold East Africa Team</strong></p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
