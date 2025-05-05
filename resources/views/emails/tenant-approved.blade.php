<!DOCTYPE html>
<html>
<head>
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
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }
        .content {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            margin-top: 20px;
        }
        .credentials {
            background: #fff;
            padding: 15px;
            border-left: 4px solid #4CAF50;
            margin: 20px 0;
        }
        .warning {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Congratulations! Your Tenant Application is Approved</h2>
    </div>
    
    <div class="content">
        <p>Dear {{ $name }},</p>
        
        <p>Your tenant application has been approved. You can now access your domain and start using the system.</p>
        
        <div class="credentials">
            <h3>Your Login Credentials</h3>
            <p><strong>Domain:</strong> {{ $domain }}</p>
            <p><strong>Email:</strong> {{ $email }}</p>
            <p><strong>Password:</strong> {{ $password }}</p>
        </div>
        
        <div class="warning">
            <strong>Important:</strong> Please save these credentials and change your password after your first login.
        </div>
        
        <a href="{{ $domain }}" class="button">Access Your Domain</a>
        
        <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
        
        <p>Best regards,<br>Your Multi-Tenant Team</p>
    </div>
</body>
</html> 