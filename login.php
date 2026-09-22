<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Check for error message from login attempt
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marketplace | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --accent: #3b82f6;
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            transition: all 0.25s ease;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Background with Blur */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('bgump.png'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(10px); 
            transform: scale(1.1);
            z-index: -1; 
            background-color: rgba(0, 0, 0, 0.3);
            background-blend-mode: darken;
        }

        /* Logo Section */
        .brand-logo {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.3));
            position: relative;
        }

        h2 {
            font-weight: 800;
            font-size: 1.5rem;
            color: #ffffff;
            letter-spacing: -0.5px;
            margin: 0 0 30px 0;
            text-transform: uppercase;
            text-align: center;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            position: relative;
        }

        /* Login Card */
        .login-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            position: relative;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .btn-admin {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            text-decoration: none;
            background: #f8fafc;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .btn-admin:hover {
            background: white;
            color: var(--primary);
            border-color: var(--primary);
        }

        /* Error Message */
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            text-align: center;
            border-left: 4px solid #dc2626;
        }

        /* Inputs */
        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-main);
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            background: #fcfdfe;
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        /* Button */
        .login-action {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-login {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 60px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.25);
            width: 100%;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(30, 64, 175, 0.3);
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <img src="umpsalgobaru.png" alt="UMPSA Logo" class="brand-logo">
    
    <h2>Student Marketplace</h2>

    <div class="login-card">
        <div class="card-header">
            <h3>Student Login</h3>
            <a href="admin_login.php" class="btn-admin">ADMIN ACCESS</a>
        </div>

        <?php if ($error == 'invalid'): ?>
            <div class="error-message">
                ❌ Invalid password! Please try again.
            </div>
        <?php elseif ($error == 'not_found'): ?>
            <div class="error-message">
                ❌ Email not found! Please register first.
            </div>
        <?php elseif ($error == 'pending'): ?>
            <div class="error-message">
                ⏳ Your account is pending admin approval. Please wait for verification.
            </div>
        <?php endif; ?>

        <form action="process_login.php" method="POST">
            <div class="input-group">
                <label>ID or Email</label>
                <input type="text" name="email" placeholder="Enter your student ID or email" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="login-action">
                <button type="submit" class="btn-login">Login to Dashboard</button>
            </div>
        </form>

        <div class="footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>

</body>
</html>