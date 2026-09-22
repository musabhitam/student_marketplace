<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) { exit; }

$myID   = intval($_SESSION['student_id']);
$chatID = intval($_POST['chat_id']);
$text   = trim($_POST['message']);

if (!$chatID || $text === '') { exit; }

// Verify this student is part of this chat
$stmt = $conn->prepare("
    SELECT ChatID FROM chats
    WHERE ChatID = ? AND (Student1ID = ? OR Student2ID = ?)
");
$stmt->bind_param("iii", $chatID, $myID, $myID);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) { exit; }

// Insert message
$stmt2 = $conn->prepare("
    INSERT INTO messages (ChatID, SenderStudentID, MessageText)
    VALUES (?, ?, ?)
");
$stmt2->bind_param("iis", $chatID, $myID, $text);
$stmt2->execute();

echo "ok";
?>