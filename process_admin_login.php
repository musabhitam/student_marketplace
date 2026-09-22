<?php
session_start();
include("connection.php");

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM admin WHERE Email='$email' AND Password='$password'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    $_SESSION['admin_id'] = $row['AdminID'];
    $_SESSION['admin_name'] = $row['AdminName'];

    header("Location: admin_dashboard.php");
    exit();
} else {
    echo "Invalid admin login!";
}
?>