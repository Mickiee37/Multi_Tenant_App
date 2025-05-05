<!DOCTYPE html>
<html>
<head>
    <title>Tenant Application Received</title>
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
            background-color: #f8f9fa;
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
        <h2>Thank You for Your Application!</h2>
    </div>

    <div class="content">
        <p>Dear {{ $name }},</p>

        <p>We have received your tenant application for {{ $company }}. Our administrative team will review your application shortly.</p>

        <p>What happens next?</p>
        <ul>
            <li>Our team will review your application</li>
            <li>You will receive another email with our decision</li>
            <li>If approved, we will provide you with login credentials</li>
        </ul>

        <p>If you have any questions in the meantime, please don't hesitate to contact our support team.</p>

        <p>Best regards,<br>
        Your Multi-Tenant Team</p>
    </div>

    <div class="footer">
        <p>This is an automated message, please do not reply directly to this email.</p>
    </div>
</body>
</html> 