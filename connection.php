<?php
$conn = new mysqli("localhost", "root", "", "student_marketplace");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>