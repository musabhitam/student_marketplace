<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

include("connection.php");

// Get current settings or create default
$result = $conn->query("SELECT * FROM admin_settings LIMIT 1");
if ($result->num_rows == 0) {
    $conn->query("INSERT INTO admin_settings (admin_phone, admin_email) VALUES ('+6012-3456789', 'admin@umpsa.edu.my')");
    $result = $conn->query("SELECT * FROM admin_settings LIMIT 1");
}
$settings = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $conn->real_escape_string($_POST['admin_phone']);
    $email = $conn->real_escape_string($_POST['admin_email']);
    $conn->query("UPDATE admin_settings SET admin_phone = '$phone', admin_email = '$email'");
    $_SESSION['success_msg'] = "Contact info updated!";
    header("Location: admin_settings.php");
    exit