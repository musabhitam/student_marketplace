<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

include("connection.php");

// Check if item ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_msg'] = "Invalid item ID.";
    header("Location: item_management.php");
    exit();
}

$item_id = $_GET['id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'delete';

// Get item details first
$item_sql = "SELECT Title, Status FROM items WHERE ItemID = $item_id";
$item_result = $conn->query($item_sql);

if ($item_result->num_rows == 0) {
    $_SESSION['error_msg'] = "Item not found.";
    header("Location: item_management.php");
    exit();
}

$item = $item_result->fetch_assoc();

// Handle different actions
if ($action == 'delete') {
    // Delete the item permanently
    $delete_sql = "DELETE FROM items WHERE ItemID = $item_id";
    
    if ($conn->query($delete_sql) === TRUE) {
        $_SESSION['success_msg'] = "Item '" . $item['Title'] . "' has been permanently removed!";
    } else {
        $_SESSION['error_msg'] = "Failed to remove item: " . $conn->error;
    }
    
} elseif ($action == 'sold') {
    // Mark item as sold
    $update_sql = "UPDATE items SET Status = 'Sold' WHERE ItemID = $item_id";
    
    if ($conn->query($update_sql) === TRUE) {
        $_SESSION['success_msg'] = "Item '" . $item['Title'] . "' has been marked as SOLD!";
    } else {
        $_SESSION['error_msg'] = "Failed to update item status.";
    }
    
} elseif ($action == 'available') {
    // Mark item as available
    $update_sql = "UPDATE items SET Status = 'Available' WHERE ItemID = $item_id";
    
    if ($conn->query($update_sql) === TRUE) {
        $_SESSION['success_msg'] = "Item '" . $item['Title'] . "' has been marked as AVAILABLE!";
    } else {
        $_SESSION['error_msg'] = "Failed to update item status.";
    }
    
} else {
    $_SESSION['error_msg'] = "Invalid action.";
}

// Redirect back to item management
header("Location: item_management.php");
exit();

$conn->close();
?>