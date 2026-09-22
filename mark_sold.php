<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$student_id = $_SESSION['student_id'];

// Update item status to 'Sold' (capital S - matches your database)
$sql = "UPDATE items SET Status = 'Sold' WHERE ItemID = $item_id AND StudentID = $student_id";
$conn->query($sql);

header("Location: my_listings.php");
exit();
?>