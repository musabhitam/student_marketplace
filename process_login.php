<?php
session_start();
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Query to check if student exists using BOTH Email AND MatricNumber
    $sql = "SELECT StudentID, FullName, Email, MatricNumber, Password, IsVerified, WarningCount, SuspendedReason 
            FROM students 
            WHERE Email = '$email' OR MatricNumber = '$email'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $student = $result->fetch_assoc();
        
        // Check if account is suspended (IsVerified = 0 AND has 3 or more warnings)
        if ($student['IsVerified'] == 0 && $student['WarningCount'] >= 3) {
            $reason = $student['SuspendedReason'] ?: "Suspended due to 3 warnings";
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Account Suspended | Student Marketplace</title>
                <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
                <style>
                    * {
                        box-sizing: border-box;
                        margin: 0;
                        padding: 0;
                    }
                    body {
                        font-family: 'Plus Jakarta Sans', sans-serif;
                        margin: 0;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        min-height: 100vh;
                        padding: 20px;
                        position: relative;
                        overflow-x: hidden;
                    }
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
                    .container {
                        background: rgba(255, 255, 255, 0.95);
                        padding: 40px;
                        border-radius: 24px;
                        max-width: 500px;
                        width: 100%;
                        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
                        text-align: center;
                    }
                    .logo { width: 100px; margin-bottom: 20px; }
                    .suspended-box {
                        background: #fee2e2;
                        border-left: 4px solid #dc2626;
                        padding: 20px;
                        border-radius: 8px;
                        margin-bottom: 20px;
                        text-align: left;
                    }
                    .suspended-box h3 { color: #991b1b; margin-bottom: 10px; }
                    .suspended-box p { margin: 5px 0; }
                    hr { margin: 15px 0; border-color: #fecaca; }
                    .contact-info {
                        background: #f8fafc;
                        padding: 12px;
                        border-radius: 8px;
                        margin-top: 10px;
                    }
                    .back-link {
                        display: inline-block;
                        margin-top: 20px;
                        color: #1e40af;
                        text-decoration: none;
                        font-weight: 600;
                    }
                    .back-link:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <div class="container">
                    <img src="umpsalgobaru.png" alt="UMPSA Logo" class="logo">
                    <div class="suspended-box">
                        <h3>⛔ Account Suspended</h3>
                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($reason); ?></p>
                        <hr>
                        <div class="contact-info">
                            <p style="font-weight: 700;">📞 Contact Administrator:</p>
                            <p>• Phone / WhatsApp: <strong>+6012-3456789</strong></p>
                            <p>• Email: <strong>admin@studentmarketplace.com</strong></p>
                        </div>
                        <p style="margin-top: 15px; font-size: 0.85rem; color: #6b7280;">
                            If you believe this is a mistake, please contact the admin.
                        </p>
                    </div>
                    <a href="login.php" class="back-link">← Back to Login</a>
                </div>
            </body>
            </html>
            <?php
            exit();
        }
        
        // Check if account is pending approval (IsVerified = 0 but no warnings)
        if ($student['IsVerified'] == 0) {
            header("Location: login.php?error=pending");
            exit();
        }
        
        // Verify password
        if (password_verify($password, $student['Password'])) {
            // Login successful
            $_SESSION['student_id'] = $student['StudentID'];
            $_SESSION['student_name'] = $student['FullName'];
            $_SESSION['student_email'] = $student['Email'];
            $_SESSION['student_matric'] = $student['MatricNumber'];
            
            // ========== LOAD SAVED CART FROM DATABASE ==========
            $_SESSION['cart'] = [];
            $load_cart = $conn->query("SELECT ItemID, Quantity FROM saved_carts WHERE StudentID = " . $student['StudentID']);
            if ($load_cart && $load_cart->num_rows > 0) {
                while ($row = $load_cart->fetch_assoc()) {
                    $item_sql = $conn->query("SELECT Title, Price FROM items WHERE ItemID = " . $row['ItemID']);
                    if ($item_sql && $item_sql->num_rows > 0) {
                        $item = $item_sql->fetch_assoc();
                        $_SESSION['cart'][$row['ItemID']] = [
                            'item_id'  => $row['ItemID'],
                            'title'    => $item['Title'],
                            'price'    => $item['Price'],
                            'quantity' => $row['Quantity']
                        ];
                    }
                }
            }
            
            header("Location: dashboard.php");
            exit();
        } else {
            // Wrong password
            header("Location: login.php?error=invalid");
            exit();
        }
    } else {
        // User not found
        header("Location: login.php?error=not_found");
        exit();
    }
}

$conn->close();
?>