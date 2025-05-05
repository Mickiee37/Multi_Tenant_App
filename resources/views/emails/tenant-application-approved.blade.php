<!DOCTYPE html>
<html>
<head>
    <title>Tenant Application Approved</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #d4edda;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .credentials {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Congratulations! Your Tenant Application is Approved</h2>
    </div>

    <div class="content">
        <p>Dear {{ $name }},</p>

        <p>Your tenant application has been approved. Here are your login credentials:</p>

        <div class="credentials">
            <h3>Your Login Credentials</h3>
            <ul style='list-style-type: none; padding: 0;'>
                <li><strong>Domain:</strong> {{ $domain }}</li>
                <li><strong>Email:</strong> {{ $email }}</li>
                <li><strong>Password:</strong> {{ $password }}</li>
            </ul>
        </div>

        <p style='color: red; font-weight: bold;'>Please save these credentials and change your password after your first login.</p>

        <p><a href='{{ $domain }}' style='display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Click here to access your domain</a></p>

        <p>Best regards,<br>
        Your Multi-Tenant Team</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please keep this email for your records.</p>
    </div>
</body>
</html> 