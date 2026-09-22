<?php
session_start();

// Save cart to database before logout
if (isset($_SESSION['student_id']) && isset($_SESSION['cart'])) {
    include("connection.php");
    $student_id = $_SESSION['student_id'];
    
    // Delete existing cart items for this user
    $conn->query("DELETE FROM saved_carts WHERE StudentID = $student_id");
    
    // Save current cart items to database
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item_id => $item) {
            $quantity = $item['quantity'];
            $conn->query("INSERT INTO saved_carts (StudentID, ItemID, Quantity) VALUES ($student_id, $item_id, $quantity)");
        }
    }
    $conn->close();
}

// Destroy session
session_destroy();
header("Location: login.php");
exit();
?>