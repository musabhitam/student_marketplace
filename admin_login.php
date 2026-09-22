<?php
session_start();

// If already logged in, redirect to admin dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}


$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Student Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --accent: #3b82f6;
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --admin-theme: #dc2626;
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
            background-color: rgba(0, 0, 0, 0.4);
            background-blend-mode: darken;
        }

        .brand-logo {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.3));
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
            color: var(--admin-theme);
        }

        .btn-back {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            text-decoration: none;
            background: #f8fafc;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .btn-back:hover {
            background: white;
            color: var(--primary);
            border-color: var(--primary);
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
            border-color: var(--admin-theme);
            background: white;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }

        /* Error Message */
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
            border-left: 4px solid #dc2626;
        }

        /* Button */
        .login-action {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-login {
            background: var(--admin-theme);
            color: white;
            border: none;
            padding: 14px 60px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
            width: 100%;
        }

        .btn-login:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(220, 38, 38, 0.3);
        }
    </style>
</head>
<body>

    <img src="umpsalgobaru.png" alt="UMPSA Logo" class="brand-logo">
    
    <h2>Student Marketplace</h2>

    <div class="login-card">
        <div class="card-header">
            <h3>Admin Portal</h3>
            <a href="login.php" class="btn-back">STUDENT LOGIN</a>
        </div>

        <?php if ($error == 'invalid'): ?>
            <div class="error-message">
                ❌ Invalid email or password. Please try again.
            </div>
        <?php elseif ($error == 'not_found'): ?>
            <div class="error-message">
                ❌ Admin account not found.
            </div>
        <?php endif; ?>

        <form action="process_admin_login.php" method="POST">
            <div class="input-group">
                <label>Admin Email</label>
                <input type="email" name="email" placeholder="admin@studentmarketplace.com" required>
            </div>

            <div class="input-group">
                <label>Security Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="login-action">
                <button type="submit" class="btn-login">Verify & Login</button>
            </div>
        </form>
    </div>

</body>
</html>