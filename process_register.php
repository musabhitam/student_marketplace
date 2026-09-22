<?php
session_start();
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $name = $conn->real_escape_string($_POST['name']);
    $matric = $conn->real_escape_string($_POST['matric']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    
    // Check if email already exists
    $check_query = "SELECT Email FROM students WHERE Email = '$email'";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows > 0) {
        // Email exists
        echo "<script>
            alert('Email already registered! Please use another email.');
            window.location.href = 'register.php';
        </script>";
        exit();
    }
    
    // Check if phone already exists (optional)
    $check_phone = "SELECT Phone FROM students WHERE Phone = '$phone'";
    $phone_result = $conn->query($check_phone);
    if ($phone_result->num_rows > 0) {
        echo "<script>
            alert('Phone number already registered! Please use another number.');
            window.location.href = 'register.php';
        </script>";
        exit();
    }
    
    // Handle student card image upload
    $student_card_path = null;
    if (isset($_FILES['student_card']) && $_FILES['student_card']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $mime = mime_content_type($_FILES['student_card']['tmp_name']);
        
        if (in_array($mime, $allowed)) {
            $ext = pathinfo($_FILES['student_card']['name'], PATHINFO_EXTENSION);
            $filename = 'uploads/student_cards/' . uniqid('card_', true) . '.' . $ext;
            
            // Create directory if not exists
            if (!file_exists('uploads/student_cards')) {
                mkdir('uploads/student_cards', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['student_card']['tmp_name'], $filename)) {
                $student_card_path = $filename;
            }
        }
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert student with IsVerified = 0 (PENDING APPROVAL) - NOW INCLUDING PHONE
    if ($student_card_path) {
        $sql = "INSERT INTO students (FullName, MatricNumber, Email, Phone, Password, IsVerified, StudentCardImage, CreatedAt) 
                VALUES ('$name', '$matric', '$email', '$phone', '$hashed_password', 0, '$student_card_path', NOW())";
    } else {
        $sql = "INSERT INTO students (FullName, MatricNumber, Email, Phone, Password, IsVerified, CreatedAt) 
                VALUES ('$name', '$matric', '$email', '$phone', '$hashed_password', 0, NOW())";
    }
    
    if ($conn->query($sql) === TRUE) {
        // Registration success - pending approval
        echo "<script>
            alert('Registration successful! Your account is pending admin approval. You will be able to login once approved.');
            window.location.href = 'login.php';
        </script>";
    } else {
        // Registration failed
        echo "<script>
            alert('Registration failed: " . $conn->error . "');
            window.location.href = 'register.php';
        </script>";
    }
}

$conn->close();
?>