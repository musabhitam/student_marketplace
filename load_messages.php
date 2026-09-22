<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) { exit; }

$myID   = intval($_SESSION['student_id']);
$chatID = intval($_GET['chat_id']);

if (!$chatID) { exit; }

// Verify this student is part of the chat
$stmt = $conn->prepare("
    SELECT ChatID FROM chats
    WHERE ChatID = ? AND (Student1ID = ? OR Student2ID = ?)
");
$stmt->bind_param("iii", $chatID, $myID, $myID);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) { exit; }

// Load messages
$stmt2 = $conn->prepare("
    SELECT MessageText, SenderStudentID, SentAt
    FROM messages
    WHERE ChatID = ?
    ORDER BY SentAt ASC
");
$stmt2->bind_param("i", $chatID);
$stmt2->execute();
$result = $stmt2->get_result();

if ($result->num_rows === 0) {
    echo '<p style="text-align:center;color:#9ca3af;font-size:0.85rem;margin-top:20px;">No messages yet. Say hi! 👋</p>';
    exit;
}

while ($row = $result->fetch_assoc()):
    $isMe = ($row['SenderStudentID'] == $myID);
    $side  = $isMe ? 'me' : 'them';
    $time  = date('h:i A', strtotime($row['SentAt']));
?>
<div class="msg-row <?php echo $side; ?>">
    <div class="bubble <?php echo $side; ?>">
        <?php echo htmlspecialchars($row['MessageText']); ?>
    </div>
    <div class="msg-time"><?php echo $time; ?></div>
</div>
<?php endwhile; ?>