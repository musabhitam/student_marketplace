<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

include("connection.php");

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $student_id = $_GET['id'];
    
    // Update student to verified (approved)
    $sql = "UPDATE students SET IsVerified = 1 WHERE StudentID = $student_id";
    
    if ($conn->query($sql) === TRUE) {
        // Get student name for message
        $name_query = "SELECT FullName FROM students WHERE StudentID = $student_id";
        $name_result = $conn->query($name_query);
        $student = $name_result->fetch_assoc();
        
        $_SESSION['success_msg'] = "Student '" . $student['FullName'] . "' has been APPROVED successfully!";
    } else {
        $_SESSION['error_msg'] = "Failed to approve student.";
    }
}

header("Location: student_management.php");
exit();
?>