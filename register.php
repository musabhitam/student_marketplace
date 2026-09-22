<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Student Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --accent: #3b82f6;
            --bg-color: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            transition: all 0.25s ease;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .brand-logo {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        h2 {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--text-main);
            letter-spacing: -0.5px;
            margin: 0 0 30px 0;
            text-transform: uppercase;
            text-align: center;
        }

        .register-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
        }

        h3 {
            margin: 0 0 25px 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-main);
        }

        .input-group label .required {
            color: #dc2626;
        }

        input {
            width: 100%;
            padding: 12px 16px;
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

        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            text-align: center;
            border-left: 4px solid #10b981;
        }

        .file-upload-box {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fcfdfe;
        }

        .file-upload-box:hover {
            border-color: var(--primary);
            background: #eff6ff;
        }

        .file-upload-box .upload-icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 8px;
        }

        .file-upload-box .upload-text {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .file-upload-box .file-name {
            margin-top: 8px;
            font-size: 0.8rem;
            color: var(--primary);
            font-weight: 600;
        }

        input[type="file"] {
            display: none;
        }

        .row-2cols {
            display: flex;
            gap: 15px;
        }

        .row-2cols .input-group {
            flex: 1;
        }

        .action-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
            gap: 20px;
        }

        .btn-register {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 0;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.25);
        }

        .btn-register:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(30, 64, 175, 0.3);
        }

        .back-link {
            font-size: 0.9rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        .info-text {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="register-card">
        <h3>Register New Account</h3>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'email_exists'): ?>
            <div class="error-message">
                ❌ Email already registered! Please use another email.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'phone_exists'): ?>
            <div class="error-message">
                ❌ Phone number already registered! Please use another number.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'upload_failed'): ?>
            <div class="error-message">
                ❌ Student card upload failed. Please try again.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'registered'): ?>
            <div class="success-message">
                ✅ Registration successful! Your account is pending admin approval.
            </div>
        <?php endif; ?>

        <form action="process_register.php" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="name" placeholder="Enter your full name" required>
            </div>

            <div class="row-2cols">
                <div class="input-group">
                    <label>Student ID (Matric No) <span class="required">*</span></label>
                    <input type="text" name="matric" placeholder="e.g. CB21xxx" required>
                </div>

                <div class="input-group">
                    <label>Phone Number <span class="required">*</span></label>
                    <input type="tel" name="phone" placeholder="e.g. 012-3456789" required>
                </div>
            </div>

            <div class="input-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" name="email" placeholder="student@umpsa.edu.my" required>
            </div>

            <div class="input-group">
                <label>Create Password <span class="required">*</span></label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="input-group">
                <label>Student Card / Matric Card <span class="required">*</span></label>
                <div class="file-upload-box" onclick="document.getElementById('studentCard').click()">
                    <span class="upload-icon">🪪</span>
                    <span class="upload-text">Click to upload your student card image</span>
                    <input type="file" id="studentCard" name="student_card" accept="image/jpeg,image/png,image/jpg" required>
                    <div id="fileName" class="file-name"></div>
                </div>
                <div class="info-text">Please upload a clear photo of your student ID / Matric card for verification.</div>
            </div>

            <div class="action-area">
                <button type="submit" class="btn-register">Create Account</button>
                <a href="login.php" class="back-link">← Back to login</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('studentCard').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || '';
            document.getElementById('fileName').textContent = fileName ? `📎 ${fileName}` : '';
        });
    </script>

</body>
</html>