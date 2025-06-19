<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .channel-info {
            background-color: #f1f5f9;
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
        }
        .channel-banner {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .channel-details {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .channel-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .channel-name {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        .content {
            padding: 40px 30px;
        }
        .content-title {
            font-size: 22px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 20px;
            text-align: center;
        }
        .verification-box {
            background-color: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s ease;
        }
        .verify-button:hover {
            transform: translateY(-1px);
        }
        .security-note {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 0 6px 6px 0;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 30px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .expires-note {
            color: #ef4444;
            font-size: 14px;
            margin-top: 15px;
        }
        @media (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .header, .content {
                padding: 30px 20px;
            }
            .verification-box {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔓 Verify Your Email</h1>
            <p>One click and you'll have access to exclusive content!</p>
        </div>

        <!-- Channel Information -->
        <div class="channel-info">
            @if($channelBanner)
            <img src="{{ $channelBanner }}" alt="Channel Banner" class="channel-banner">
            @endif
            
            <div class="channel-details">
                @if($channelAvatar)
                <img src="{{ $channelAvatar }}" alt="{{ $channelName }}" class="channel-avatar">
                @endif
                <h3 class="channel-name">{{ $channelName }}</h3>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <h2 class="content-title">{{ $content->title }}</h2>
            
            <p>Hi there! 👋</p>
            
            <p>You've requested access to exclusive content from <strong>{{ $channelName }}</strong>. To verify your email address and get instant access, simply click the button below:</p>

            <div class="verification-box">
                <p style="margin: 0 0 15px; font-size: 18px; font-weight: 600;">Ready to access your content?</p>
                <a href="{{ $verificationUrl }}" class="verify-button">✨ Verify Email & Get Access</a>
                <p class="expires-note">⏰ This link expires at {{ $expiresAt->format('M j, Y g:i A') }}</p>
            </div>

            <div class="security-note">
                <strong>🔒 Security Note:</strong> This verification link was sent because someone (hopefully you!) requested access to content from {{ $channelName }}. If this wasn't you, you can safely ignore this email.
            </div>

            <p><strong>What happens next?</strong></p>
            <ul style="padding-left: 20px;">
                <li>✅ Your email will be verified</li>
                <li>📧 You'll be added to {{ $channelName }}'s email list</li>
                <li>🎯 You'll get the required tag for this content</li>
                <li>🚀 Immediate access to "{{ $content->title }}"</li>
                <li>🍪 A secure cookie so you won't need to verify again</li>
            </ul>

            @if($utmContent)
            <p style="color: #64748b; font-size: 14px; margin-top: 30px;">
                📺 <em>You came here from a YouTube video. Thanks for watching!</em>
            </p>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This email was sent by {{ config('app.name') }}</p>
            <p>Having trouble? Copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #667eea; font-size: 12px;">{{ $verificationUrl }}</p>
            
            <p style="margin-top: 25px;">
                <strong>{{ $channelName }}</strong><br>
                Building amazing content for awesome people like you! 🎉
            </p>
        </div>
    </div>
</body>
</html> 