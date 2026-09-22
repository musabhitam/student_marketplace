<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$title       = $_POST['title'];
$desc        = $_POST['description'];
$price       = $_POST['price'];
$campus      = $_POST['campus'];
$condition   = $_POST['condition'];
$category_id = $_POST['category_id'];
$student_id  = $_SESSION['student_id'];

$status = "Available";

/* ======================
   INSERT ITEM
====================== */
$stmt = $conn->prepare("
    INSERT INTO items (Title, Description, Price, Status, StudentID, CategoryID, Campus, `Condition`, CreatedAt)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param("ssdssiss", $title, $desc, $price, $status, $student_id, $category_id, $campus, $condition);

if ($stmt->execute()) {

    $item_id = $stmt->insert_id;

    /* ======================
       IMAGE UPLOAD
    ====================== */
    $upload_dir = "uploads/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image_name = time() . "_" . basename($_FILES['image']['name']);
    $image_path = $upload_dir . $image_name;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {

        $stmt2 = $conn->prepare("
            INSERT INTO item_images (ImagePath, ItemID)
            VALUES (?, ?)
        ");

        $stmt2->bind_param("si", $image_path, $item_id);
        $stmt2->execute();
    }

    header("Location: my_listings.php");
    exit();

} else {
    echo "Error: " . $stmt->error;
}
?>