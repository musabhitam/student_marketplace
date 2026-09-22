<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

include("connection.php");

// Check if report ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_msg'] = "Invalid report ID.";
    header("Location: report_management.php");
    exit();
}

$report_id = $_GET['id'];

// Get report details
$report_sql = "
    SELECT reports.*, 
           r.FullName AS ReporterName,
           r.StudentID AS ReporterID,
           t.FullName AS ReportedName,
           t.StudentID AS ReportedID
    FROM reports
    JOIN students r ON reports.ReporterStudentID = r.StudentID
    JOIN students t ON reports.ReportedStudentID = t.StudentID
    WHERE reports.ReportID = $report_id
";

$report_result = $conn->query($report_sql);

if ($report_result->num_rows == 0) {
    $_SESSION['error_msg'] = "Report not found.";
    header("Location: report_management.php");
    exit();
}

$report = $report_result->fetch_assoc();

// Start transaction
$conn->begin_transaction();

try {
    
    // 1. Update report status to 'Dismissed'
    $update_report = "UPDATE reports SET Status = 'Dismissed' WHERE ReportID = $report_id";
    $conn->query($update_report);
    
    // 2. Prepare notification for REPORTER (tell them report was dismissed)
    $reporter_message = "ℹ️ REPORT UPDATE:\n\n" .
                       "Your report against '{$report['ReportedName']}' has been reviewed and DISMISSED.\n\n" .
                       "After investigation, no violation was found.\n\n" .
                       "If you have additional evidence, please submit a new report.\n\n" .
                       "Thank you for your understanding.";
    
    // Insert notification for REPORTER
    $escaped_reporter_msg = $conn->real_escape_string($reporter_message);
    $notify_reporter = "INSERT INTO notifications (StudentID, Message, IsRead, CreatedAt) 
                        VALUES ('{$report['ReporterID']}', '$escaped_reporter_msg', 0, NOW())";
    $conn->query($notify_reporter);
    
    $conn->commit();
    
    $_SESSION['success_msg'] = "✅ Report dismissed! Reporter has been notified.";
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_msg'] = "❌ Failed to dismiss report: " . $e->getMessage();
}

header("Location: report_management.php");
exit();

$conn->close();
?>