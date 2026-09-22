<?php
session_start();
include("connection.php");

$query = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$response = ['items' => [], 'students' => []];

if (strlen($query) >= 2) {
    
    // Search items (only available items from verified sellers)
    $item_sql = "
        SELECT items.ItemID, items.Title, items.Price, categories.CategoryName
        FROM items
        JOIN categories ON items.CategoryID = categories.CategoryID
        JOIN students ON items.StudentID = students.StudentID
        WHERE students.IsVerified = 1
        AND items.Status = 'Available'
        AND (
            items.Title LIKE '%$query%'
            OR items.Description LIKE '%$query%'
            OR categories.CategoryName LIKE '%$query%'
            OR items.Condition LIKE '%$query%'
            OR items.Campus LIKE '%$query%'
        )
        LIMIT 5
    ";
    $item_result = $conn->query($item_sql);
    if ($item_result && $item_result->num_rows > 0) {
        while($row = $item_result->fetch_assoc()) {
            $response['items'][] = $row;
        }
    }
    
    // Search students (only verified/active students)
    $student_sql = "
        SELECT StudentID, FullName, Email, MatricNumber
        FROM students
        WHERE IsVerified = 1
        AND (
            FullName LIKE '%$query%'
            OR Email LIKE '%$query%'
            OR MatricNumber LIKE '%$query%'
        )
        LIMIT 5
    ";
    $student_result = $conn->query($student_sql);
    if ($student_result && $student_result->num_rows > 0) {
        while($row = $student_result->fetch_assoc()) {
            $response['students'][] = $row;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>