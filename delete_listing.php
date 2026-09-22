<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$student_id = $_SESSION['student_id'];

// Delete images first
$conn->query("DELETE FROM item_images WHERE ItemID = $item_id");
// Delete the item
$conn->query("DELETE FROM items WHERE ItemID = $item_id AND StudentID = $student_id");

header("Location: my_listings.php");
exit();
?>