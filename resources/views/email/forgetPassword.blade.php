<!-- resources/views/email/forgetPassword.blade.php -->

<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
</head>
<body>
    <h2>Password Reset Request</h2>
    <p>Hello,</p>
    <p>We received a request to reset your password. Click the link below to reset it:</p>

    <a href="{{ url('/reset-password?token=' . $token) }}">Reset Password</a>

    <p>This link will expire in 1 hour.</p>

    <p>If you did not request this, please ignore this email.</p>

    <p>Thank you,<br>Your App Team</p>
</body>
</html>