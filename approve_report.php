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

// Get report details with all related info
$report_sql = "
    SELECT reports.*, 
           r.FullName AS ReporterName,
           r.StudentID AS ReporterID,
           t.FullName AS ReportedName,
           t.StudentID AS ReportedID,
           t.WarningCount,
           t.IsVerified,
           items.Title AS ItemTitle,
           items.ItemID,
           items.Status AS ItemStatus
    FROM reports
    JOIN students r ON reports.ReporterStudentID = r.StudentID
    JOIN students t ON reports.ReportedStudentID = t.StudentID
    LEFT JOIN items ON reports.ItemID = items.ItemID
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

$success = true;
$was_suspended = false;

try {
    
    // 1. Update report status to 'Resolved'
    $update_report = "UPDATE reports SET Status = 'Resolved' WHERE ReportID = $report_id";
    if (!$conn->query($update_report)) {
        $success = false;
    }
    
    // 2. Increase warning count for reported student
    $current_warning = isset($report['WarningCount']) ? $report['WarningCount'] : 0;
    $new_warning = $current_warning + 1;
    $update_warnings = "UPDATE students SET WarningCount = $new_warning WHERE StudentID = " . $report['ReportedID'];
    if ($conn->query($update_warnings)) {
        
        // Log warning history if table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'warning_history'");
        if ($table_check->num_rows > 0) {
            $escaped_reason = $conn->real_escape_string($report['Reason']);
            $history_sql = "INSERT INTO warning_history (StudentID, ReportID, WarningNumber, Reason, CreatedAt) 
                            VALUES ('{$report['ReportedID']}', '$report_id', '$new_warning', '$escaped_reason', NOW())";
            $conn->query($history_sql);
        }
        
        // 3. ONLY suspend user and remove items on 3rd warning
        if ($new_warning >= 3) {
            $was_suspended = true;
            
            // Create suspension reason
            $suspension_reason = "Suspended due to 3 warnings. Latest report: " . $report['Reason'];
            $escaped_reason = $conn->real_escape_string($suspension_reason);
            
            // Suspend the user with reason
            $suspend_student = "UPDATE students SET IsVerified = 0, SuspendedReason = '$escaped_reason' WHERE StudentID = " . $report['ReportedID'];
            $conn->query($suspend_student);
            
            // Remove the reported item
            if ($report['ItemID']) {
                $remove_item = "UPDATE items SET Status = 'Removed' WHERE ItemID = " . $report['ItemID'];
                $conn->query($remove_item);
            }
            
            // Hide all other items from suspended user
            $hide_all_items = "UPDATE items SET Status = 'Removed' WHERE StudentID = " . $report['ReportedID'];
            $conn->query($hide_all_items);
        }
    }
    
    // 4. Prepare notifications
    
    // Notification for accused user
    if ($was_suspended) {
        $accused_message = "Your account has been suspended due to 3 warnings.\n\nReason: {$report['Reason']}\n\nContact admin: admin@umpsa.edu.my or call +6012-3456789";
    } elseif ($new_warning == 2) {
        $accused_message = "FINAL WARNING! One more report and your account will be suspended.\n\nReason: {$report['Reason']}";
    } else {
        $accused_message = "Warning issued for: {$report['Reason']}\n\nFurther violations may lead to suspension.";
    }
    
    // Notification for reporter
    if ($was_suspended) {
        $reporter_message = "Your report against {$report['ReportedName']} has been approved. User has been suspended and item removed. Thank you.";
    } elseif ($new_warning == 2) {
        $reporter_message = "Your report against {$report['ReportedName']} has been approved. This is their FINAL WARNING.";
    } else {
        $reporter_message = "Your report against {$report['ReportedName']} has been approved. Warning issued to user.";
    }
    
    // Send notification to accused
    $escaped_accused_msg = $conn->real_escape_string($accused_message);
    $notify_accused = "INSERT INTO notifications (StudentID, Message, IsRead, CreatedAt) 
                       VALUES ('{$report['ReportedID']}', '$escaped_accused_msg', 0, NOW())";
    $conn->query($notify_accused);
    
    // Send notification to reporter
    $escaped_reporter_msg = $conn->real_escape_string($reporter_message);
    $notify_reporter = "INSERT INTO notifications (StudentID, Message, IsRead, CreatedAt) 
                        VALUES ('{$report['ReporterID']}', '$escaped_reporter_msg', 0, NOW())";
    $conn->query($notify_reporter);
    
    if ($success) {
        $conn->commit();
        
        // SHORT NATURAL SUCCESS MESSAGES
        if ($was_suspended) {
            $_SESSION['success_msg'] = "✅ " . $report['ReportedName'] . " suspended. Item removed.";
        } elseif ($new_warning == 2) {
            $_SESSION['success_msg'] = "⚠️ Final warning sent to " . $report['ReportedName'];
        } else {
            $_SESSION['success_msg'] = "⚠️ Warning sent to " . $report['ReportedName'];
        }
        
    } else {
        throw new Exception("Database error");
    }
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_msg'] = "❌ Failed to process report.";
}

header("Location: report_management.php");
exit();

$conn->close();
?>