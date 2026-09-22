<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) { echo 0; exit; }

$myID   = intval($_SESSION['student_id']);
$userID = intval($_GET['user']);

if ($userID <= 0) { echo 0; exit; }

// Check if a chat already exists between these two users
$stmt = $conn->prepare("
    SELECT ChatID FROM chats
    WHERE (Student1ID = ? AND Student2ID = ?)
       OR (Student1ID = ? AND Student2ID = ?)
    LIMIT 1
");
$stmt->bind_param("iiii", $myID, $userID, $userID, $myID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Chat exists, return it
    $row = $result->fetch_assoc();
    echo $row['ChatID'];
} else {
    // Create new chat
    $stmt2 = $conn->prepare("INSERT INTO chats (Student1ID, Student2ID) VALUES (?, ?)");
    $stmt2->bind_param("ii", $myID, $userID);
    $stmt2->execute();
    echo $stmt2->insert_id;
}
?>