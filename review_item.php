<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

include("connection.php");

// Check if item ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_msg'] = "Invalid item ID.";
    header("Location: item_management.php");
    exit();
}

$item_id = $_GET['id'];

// Get item details with seller information
$sql = "
    SELECT items.*, students.FullName as SellerName, students.Email as SellerEmail
    FROM items
    JOIN students ON items.StudentID = students.StudentID
    WHERE items.ItemID = $item_id
";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $_SESSION['error_msg'] = "Item not found.";
    header("Location: item_management.php");
    exit();
}

$item = $result->fetch_assoc();

// Get all images for this item
$img_sql = "SELECT * FROM item_images WHERE ItemID = $item_id";
$img_result = $conn->query($img_sql);
$images = [];
if ($img_result && $img_result->num_rows > 0) {
    while($img = $img_result->fetch_assoc()) {
        $images[] = $img['ImagePath'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Review Item - Admin Panel</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========================================
   ADMIN COLOR SCHEME - SLATE / BLUE
   ======================================== */
:root {
    --admin-primary:   #0f172a;
    --admin-primary-mid: #1e293b;
    --admin-accent:    #3b82f6;
    --admin-accent-dk: #2563eb;
    --admin-accent-lt: #eff6ff;
    --admin-success:   #10b981;
    --admin-success-lt:#d1fae5;
    --admin-danger:    #ef4444;
    --admin-danger-lt: #fee2e2;
    --admin-warning:   #f59e0b;
    --admin-warning-lt:#fef3c7;
    --surface:   #ffffff;
    --bg:        #f1f5f9;
    --border:    #e2e8f0;
    --text:      #0f172a;
    --muted:     #64748b;
    --radius-sm: 8px;
    --radius-md: 14px;
    --radius-lg: 20px;
    --shadow-card: 0 2px 8px rgba(0,0,0,.05), 0 8px 24px rgba(0,0,0,.04);
    --shadow-hover: 0 6px 20px rgba(0,0,0,.1), 0 2px 6px rgba(0,0,0,.06);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: url('background.png') center/cover;
    filter: blur(12px);
    transform: scale(1.1);
    z-index: -1;
}

/* ========================================
   TOP BAR - ADMIN STYLE
   ======================================== */
.top-bar {
    position: sticky;
    top: 0;
    z-index: 200;
    background: var(--admin-primary);
    padding: 0 36px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,.25);
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
    text-decoration: none;
}

.logo-section img { width: 34px; height: 34px; object-fit: contain; }

.logo-text {
    line-height: 1.15;
}

.logo-text .brand {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--admin-accent);
}

.logo-text .sub {
    font-size: 0.62rem;
    font-weight: 500;
    color: rgba(255,255,255,.55);
    text-transform: uppercase;
    letter-spacing: .06em;
}

.nav-bar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 36px;
    display: flex;
    align-items: center;
    gap: 4px;
    height: 48px;
}

.nav-bar a {
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--muted);
    padding: 6px 14px;
    border-radius: 999px;
}

.nav-bar a:hover {
    background: var(--bg);
    color: var(--admin-primary);
}

.nav-bar a.active {
    background: var(--admin-primary);
    color: white;
}

.nav-bar .logout {
    margin-left: auto;
    color: var(--admin-danger) !important;
}

.main-container {
    max-width: 1000px;
    margin: 28px auto;
    padding: 0 24px 60px;
}

.page-title {
    margin-bottom: 24px;
}

.page-title h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
}

.page-title p {
    font-size: 0.85rem;
    color: var(--muted);
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--muted);
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.8rem;
    margin-bottom: 20px;
    transition: background 0.2s;
}

.btn-back:hover {
    background: #4b5563;
}

.item-detail-card {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    overflow: hidden;
}

.item-header {
    background: var(--admin-primary);
    color: white;
    padding: 20px 24px;
}

.item-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
}

.item-body {
    padding: 24px;
}

/* Image Gallery */
.image-gallery {
    margin-bottom: 24px;
    background: var(--bg);
    border-radius: var(--radius-md);
    padding: 20px;
}

.gallery-title {
    font-weight: 700;
    color: var(--admin-primary);
    margin-bottom: 15px;
    font-size: 0.85rem;
}

.image-container {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.image-item {
    width: 180px;
    height: 180px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    border: 2px solid var(--border);
    background: white;
    box-shadow: var(--shadow-card);
}

.image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    color: var(--muted);
    font-size: 0.85rem;
    padding: 10px 0;
}

/* Info Rows */
.info-row {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    width: 140px;
    font-weight: 600;
    color: var(--admin-primary);
    font-size: 0.85rem;
}

.info-value {
    flex: 1;
    color: var(--text);
    font-size: 0.85rem;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.status-available {
    background: var(--admin-success-lt);
    color: var(--admin-success);
}

.status-sold {
    background: var(--admin-warning-lt);
    color: #92400e;
}

/* Description Box */
.description-box {
    background: var(--bg);
    padding: 14px;
    border-radius: var(--radius-sm);
    margin-top: 6px;
    line-height: 1.6;
    font-size: 0.85rem;
}

/* Action Buttons */
.action-buttons {
    margin-top: 24px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.btn-remove {
    background: var(--admin-danger);
    color: white;
    padding: 10px 24px;
    text-decoration: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.2s;
}

.btn-remove:hover {
    background: #b91c1c;
    transform: translateY(-2px);
}

.btn-mark-sold {
    background: var(--admin-warning);
    color: white;
    padding: 10px 24px;
    text-decoration: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.2s;
}

.btn-mark-sold:hover {
    background: #d97706;
    transform: translateY(-2px);
}

.btn-mark-available {
    background: var(--admin-success);
    color: white;
    padding: 10px 24px;
    text-decoration: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.2s;
}

.btn-mark-available:hover {
    background: #059669;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .top-bar { padding: 0 20px; height: auto; flex-wrap: wrap; padding: 12px 20px; gap: 12px; }
    .nav-bar { padding: 0 16px; flex-wrap: wrap; height: auto; padding: 8px 16px; }
    .nav-bar .logout { margin-left: 0; }
    .main-container { padding: 0 16px 40px; margin: 20px; }
    
    .info-row {
        flex-direction: column;
    }
    
    .info-label {
        width: 100%;
        margin-bottom: 6px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-remove, .btn-mark-sold, .btn-mark-available {
        text-align: center;
    }
    
    .image-item {
        width: 120px;
        height: 120px;
    }
}
</style>
</head>
<body>

<header class="top-bar">
    <a href="admin_dashboard.php" class="logo-section">
        <img src="umpsalgobaru.png" alt="UMPSA">
        <div class="logo-text">
            <div class="brand">Student Marketplace</div>
            <div class="sub">Admin Panel</div>
        </div>
    </a>
</header>

<nav class="nav-bar">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="student_management.php">Student Management</a>
    <a href="item_management.php" class="active">Item Management</a>
    <a href="report_management.php">Report Management</a>
    <a href="logout.php" class="logout">Logout</a>
</nav>

<main class="main-container">
    <a href="item_management.php" class="btn-back">← Back to Item Management</a>
    
    <div class="page-title">
        <h2>Review Item</h2>
        <p>View detailed information about this listing</p>
    </div>

    <div class="item-detail-card">
        <div class="item-header">
            <h3><?php echo htmlspecialchars($item['Title']); ?></h3>
        </div>
        
        <div class="item-body">
            <!-- IMAGE GALLERY -->
            <div class="image-gallery">
                <div class="gallery-title">📷 Item Images</div>
                <div class="image-container">
                    <?php if (count($images) > 0): ?>
                        <?php foreach ($images as $img_path): ?>
                            <div class="image-item">
                                <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Item image">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-image">No images uploaded for this item.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Item ID:</div>
                <div class="info-value"><?php echo $item['ItemID']; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Price:</div>
                <div class="info-value">RM <?php echo number_format($item['Price'], 2); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <?php if ($item['Status'] == 'Available'): ?>
                        <span class="status-badge status-available">✅ Available</span>
                    <?php else: ?>
                        <span class="status-badge status-sold">❌ Sold / Removed</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Condition:</div>
                <div class="info-value"><?php echo htmlspecialchars($item['Condition'] ?? 'Not specified'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Campus:</div>
                <div class="info-value"><?php echo htmlspecialchars($item['Campus'] ?? 'Not specified'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Category ID:</div>
                <div class="info-value"><?php echo $item['CategoryID'] ?? 'N/A'; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Description:</div>
                <div class="info-value">
                    <div class="description-box">
                        <?php echo nl2br(htmlspecialchars($item['Description'] ?? 'No description provided.')); ?>
                    </div>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Seller Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($item['SellerName']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Seller Email:</div>
                <div class="info-value"><?php echo htmlspecialchars($item['SellerEmail']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Date Posted:</div>
                <div class="info-value"><?php echo date('d F Y, h:i A', strtotime($item['CreatedAt'])); ?></div>
            </div>
            
            <div class="action-buttons">
                <?php if ($item['Status'] == 'Available'): ?>
                    <a href="remove_item.php?id=<?php echo $item['ItemID']; ?>&action=sold" class="btn-mark-sold" onclick="return confirm('Mark this item as SOLD?')">
                        🔴 Mark as Sold
                    </a>
                <?php else: ?>
                    <a href="remove_item.php?id=<?php echo $item['ItemID']; ?>&action=available" class="btn-mark-available" onclick="return confirm('Mark this item as AVAILABLE again?')">
                        🟢 Mark as Available
                    </a>
                <?php endif; ?>
                
                <a href="remove_item.php?id=<?php echo $item['ItemID']; ?>&action=delete" class="btn-remove" onclick="return confirm('WARNING: Delete this item permanently? This action cannot be undone.')">
                    🗑️ Remove Item
                </a>
            </div>
        </div>
    </div>
</main>

</body>
</html>

<?php $conn->close(); ?>