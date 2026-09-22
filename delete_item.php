<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$item_id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch item — must belong to this student
$stmt = $conn->prepare("
    SELECT items.*, item_images.ImagePath, item_images.ImageID
    FROM items
    LEFT JOIN item_images ON items.ItemID = item_images.ItemID
    WHERE items.ItemID = ? AND items.StudentID = ?
    LIMIT 1
");
$stmt->bind_param("ii", $item_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Item not found or doesn't 
    header("Location: my_listings.php");
    exit();
}

$item = $result->fetch_assoc();

// If confirmed via POST, perform deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    // Delete image record first (FK constraint)
    if (!empty($item['ImageID'])) {
        $del_img = $conn->prepare("DELETE FROM item_images WHERE ItemID = ?");
        $del_img->bind_param("i", $item_id);
        $del_img->execute();

        // Optionally remove the physical file
        if (!empty($item['ImagePath']) && file_exists($item['ImagePath'])) {
            unlink($item['ImagePath']);
        }
    }

    // Delete the item
    $del_item = $conn->prepare("DELETE FROM items WHERE ItemID = ? AND StudentID = ?");
    $del_item->bind_param("ii", $item_id, $student_id);
    $del_item->execute();

    // Redirect back with success flag
    header("Location: my_listings.php?deleted=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Listing | Student Marketplace</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #1e40af;
    --danger: #e11d48;
    --card-bg: rgba(255,255,255,0.96);
    --header-bg: #e5e7eb;
    --text-main: #1f2937;
}

* { box-sizing: border-box; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    margin: 0;
}

body::before {
    content: "";
    position: fixed;
    width: 100%;
    height: 100%;
    background: url('background.png') center/cover;
    filter: blur(12px);
    transform: scale(1.1);
    z-index: -1;
}

/* TOP BAR */
.top-bar {
    background: var(--header-bg);
    padding: 15px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo-section { display: flex; align-items: center; gap: 10px; }
.logo-section h1 { font-size: 0.9rem; margin: 0; text-transform: uppercase; }

.search-section input {
    width: 350px;
    padding: 8px 15px;
    border: 1px solid #9ca3af;
    border-radius: 4px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.9rem;
}

.icon-section a { font-size: 1.3rem; text-decoration: none; }

/* NAV */
.nav-bar {
    background: var(--header-bg);
    padding: 10px 40px;
    display: flex;
    gap: 25px;
    border-bottom: 2px solid #9ca3af;
}

.nav-bar a {
    text-decoration: none;
    font-weight: 700;
    color: var(--text-main);
    font-size: 0.95rem;
}

.nav-bar a:hover { color: var(--primary); }
.nav-bar a.active { color: var(--primary); }

/* MAIN */
.main-container {
    max-width: 600px;
    margin: 50px auto;
    background: var(--card-bg);
    padding: 40px;
    border-radius: 6px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    text-align: center;
}

/* WARNING ICON */
.warning-icon {
    font-size: 3.5rem;
    margin-bottom: 10px;
}

.main-container h2 {
    color: var(--danger);
    margin: 0 0 8px 0;
    font-size: 1.5rem;
}

.main-container .subtitle {
    color: #6b7280;
    font-size: 0.95rem;
    margin-bottom: 30px;
}

/* ITEM PREVIEW CARD */
.item-preview {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #fff7f7;
    border: 1px solid #fecdd3;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 30px;
    text-align: left;
}

.item-preview img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
    border: 2px solid #fecdd3;
}

.item-preview-info .item-title {
    font-weight: 800;
    font-size: 1rem;
    color: var(--text-main);
    margin: 0 0 4px 0;
}

.item-preview-info .item-desc {
    font-size: 0.82rem;
    color: #9ca3af;
    margin: 0 0 8px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.item-preview-info .item-price {
    font-weight: 800;
    font-size: 0.95rem;
    color: var(--danger);
}

/* WARNING NOTE */
.warning-note {
    background: #fff1f2;
    border-left: 4px solid var(--danger);
    border-radius: 4px;
    padding: 12px 16px;
    text-align: left;
    font-size: 0.88rem;
    color: #9f1239;
    margin-bottom: 30px;
    font-weight: 600;
}

/* ACTIONS */
.action-buttons {
    display: flex;
    gap: 14px;
    justify-content: center;
}

.btn-confirm-delete {
    padding: 12px 30px;
    background: var(--danger);
    color: white;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-confirm-delete:hover { background: #be123c; }

.btn-cancel {
    padding: 12px 30px;
    background: #f3f4f6;
    color: var(--text-main);
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: background 0.2s;
}

.btn-cancel:hover { background: #e5e7eb; }
</style>
</head>
<body>

<!-- TOP BAR -->
<div class="top-bar">
    <div class="logo-section">
        <img src="umpsalogos.png" width="35">
        <h1>Student Marketplace<br>System</h1>
    </div>
    <div class="search-section">
        <input type="text" placeholder="🔍 Search Item...">
    </div>
    <div class="icon-section">
        <a href="profile.php">👤</a>
        <a href="cart.php">🛒</a>
    </div>
</div>

<!-- NAV -->
<div class="nav-bar">
    <a href="dashboard.php">Home</a>
    <a href="post_item.html">Post Item</a>
    <a href="my_listings.php" class="active">My Listings</a>
    <a href="messages.php">Messages</a>
    <a href="logout.php" style="margin-left:auto;color:red;">Logout</a>
</div>

<!-- MAIN -->
<div class="main-container">

    <div class="warning-icon">🗑️</div>
    <h2>Delete Listing?</h2>
    <p class="subtitle">You are about to permanently remove this item from the marketplace.</p>

    <!-- Item Preview -->
    <div class="item-preview">
        <img src="<?php echo !empty($item['ImagePath']) ? htmlspecialchars($item['ImagePath']) : 'https://via.placeholder.com/80x80?text=No+Image'; ?>"
             alt="Item image">
        <div class="item-preview-info">
            <p class="item-title"><?php echo htmlspecialchars($item['Title']); ?></p>
            <p class="item-desc"><?php echo htmlspecialchars($item['Description']); ?></p>
            <div class="item-price">RM <?php echo number_format($item['Price'], 2); ?></div>
        </div>
    </div>

    <!-- Warning Note -->
    <div class="warning-note">
        ⚠️ This action cannot be undone. The listing and its image will be permanently deleted.
    </div>

    <!-- Confirm Form -->
    <form method="POST">
        <div class="action-buttons">
            <button type="submit" name="confirm_delete" class="btn-confirm-delete">Yes, Delete It</button>
            <a href="my_listings.php" class="btn-cancel">Cancel</a>
        </div>
    </form>

</div>

</body>
</html>