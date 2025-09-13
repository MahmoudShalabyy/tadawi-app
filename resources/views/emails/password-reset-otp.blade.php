<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Tadawi</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 10px;
        }
        .otp-container {
            background-color: #f8f9fa;
            border: 2px dashed #dc3545;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #dc3545;
            letter-spacing: 8px;
            margin: 10px 0;
        }
        .warning {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #721c24;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background-color: #dc3545;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏥 Tadawi</div>
            <h1>Password Reset Request</h1>
        </div>

        <p>Hello!</p>
        
        <p>We received a request to reset your password for your Tadawi account. To proceed with resetting your password, please use the verification code below:</p>

        <div class="otp-container">
            <p><strong>Your password reset code is:</strong></p>
            <div class="otp-code">{{ $otp }}</div>
            <p><small>This code will expire in 10 minutes</small></p>
        </div>

        <div class="warning">
            <strong>🔒 Security Notice:</strong>
            <ul>
                <li>Never share this code with anyone</li>
                <li>Tadawi will never ask for your password reset code</li>
                <li>If you didn't request a password reset, please ignore this email</li>
                <li>Your password will remain unchanged until you complete the reset process</li>
            </ul>
        </div>

        <p>If you're having trouble with the code above, you can also copy and paste it directly into the password reset field.</p>

        <p>If you didn't request a password reset, please ignore this email and your account will remain secure.</p>

        <div class="footer">
            <p>This email was sent from Tadawi - Your trusted healthcare companion</p>
            <p>© {{ date('Y') }} Tadawi. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
