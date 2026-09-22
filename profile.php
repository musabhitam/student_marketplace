<?php
session_start();
include("connection.php");

// Logged-in user
$myID = $_SESSION['student_id'] ?? 0;

// Profile to view (if no ID provided, show own profile)
$viewID = $_GET['id'] ?? $myID;

// Check if viewing own profile
$isOwnProfile = ($viewID == $myID);

// =====================
// GET USER INFO
// =====================
$userQuery = "SELECT * FROM students WHERE StudentID = '$viewID'";
$userResult = mysqli_query($conn, $userQuery);
$user = mysqli_fetch_assoc($userResult);

if (!$user) {
    die("User not found");
}

$username = $user['FullName'] ?? "Unknown";
$email = $user['Email'] ?? "No Email";

// =====================
// GET AVERAGE RATING
// =====================
$ratingQuery = "SELECT AVG(Rating) AS avgRating, COUNT(*) as totalReviews FROM reviews WHERE ReviewedStudentID = '$viewID'";
$ratingResult = mysqli_query($conn, $ratingQuery);
$ratingData = mysqli_fetch_assoc($ratingResult);
$avgRating = round($ratingData['avgRating'], 1);
$totalReviews = $ratingData['totalReviews'] ?? 0;

// =====================
// GET REVIEWS
// =====================
$reviewQuery = "
    SELECT r.*, s.FullName 
    FROM reviews r
    JOIN students s ON r.ReviewerStudentID = s.StudentID
    WHERE r.ReviewedStudentID = '$viewID'
    ORDER BY r.CreatedAt DESC
";
$reviewResult = mysqli_query($conn, $reviewQuery);

// =====================
// GET ITEMS POSTED BY USER
// =====================
$itemsQuery = "
    SELECT items.*, 
           (SELECT ImagePath FROM item_images 
            WHERE item_images.ItemID = items.ItemID 
            LIMIT 1) AS ImagePath,
           categories.CategoryName
    FROM items
    JOIN categories ON items.CategoryID = categories.CategoryID
    WHERE items.StudentID = '$viewID'
    AND items.Status = 'Available'
    ORDER BY items.CreatedAt DESC
";
$itemsResult = mysqli_query($conn, $itemsQuery);
$totalItems = mysqli_num_rows($itemsResult);

// =====================
// CHECK IF CURRENT USER HAS ALREADY REVIEWED THIS USER
// =====================
$alreadyReviewed = false;
if (!$isOwnProfile && $myID > 0) {
    $checkQuery = "SELECT * FROM reviews WHERE ReviewerStudentID = '$myID' AND ReviewedStudentID = '$viewID'";
    $checkResult = mysqli_query($conn, $checkQuery);
    $alreadyReviewed = mysqli_num_rows($checkResult) > 0;
}

// Cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c) {
        $cart_count += $c['quantity'];
    }
}

// Unread notification count
$unread_count = 0;
if ($myID > 0) {
    $unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE StudentID = $myID AND IsRead = 0";
    $unread_result = $conn->query($unread_sql);
    if ($unread_result && $unread_result->num_rows > 0) {
        $unread_count = $unread_result->fetch_assoc()['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Marketplace | Profile</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========================================
   GLOBAL VARIABLES - EXACT MATCH DASHBOARD
   ======================================== */
:root {
    --navy:      #1a2744;
    --navy-mid:  #243460;
    --amber:     #f59e0b;
    --amber-dk:  #d97706;
    --amber-lt:  #fef3c7;
    --green:     #059669;
    --green-lt:  #d1fae5;
    --red:       #dc2626;
    --surface:   #ffffff;
    --bg:        #f1f5f9;
    --border:    #e2e8f0;
    --text:      #0f172a;
    --muted:     #64748b;
    --radius-sm: 8px;
    --radius-md: 14px;
    --radius-lg: 20px;
    --shadow-card: 0 2px 8px rgba(26,39,68,.07), 0 8px 24px rgba(26,39,68,.06);
    --shadow-hover: 0 6px 20px rgba(26,39,68,.13), 0 2px 6px rgba(26,39,68,.08);
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

/* ========================================
   TOP BAR - EXACT MATCH DASHBOARD
   ======================================== */
.top-bar {
    position: sticky;
    top: 0;
    z-index: 200;
    background: var(--navy);
    padding: 0 36px;
    height: 64px;
    display: flex;
    align-items: center;
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
    color: var(--amber);
}

.logo-text .sub {
    font-size: 0.62rem;
    font-weight: 500;
    color: rgba(255,255,255,.55);
    text-transform: uppercase;
    letter-spacing: .06em;
}

/* Search */
.search-wrap {
    flex: 1;
    max-width: 520px;
    margin: 0 auto;
    position: relative;
}

.search-wrap form {
    display: flex;
    background: rgba(255,255,255,.1);
    border: 1.5px solid rgba(255,255,255,.18);
    border-radius: 999px;
    overflow: hidden;
    transition: border-color .2s, background .2s;
}

.search-wrap form:focus-within {
    background: rgba(255,255,255,.15);
    border-color: var(--amber);
}

.search-wrap input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    padding: 0 18px;
    font-family: inherit;
    font-size: 0.875rem;
    color: #fff;
    height: 40px;
}

.search-wrap input::placeholder { color: rgba(255,255,255,.45); }

.search-wrap button {
    background: var(--amber);
    color: var(--navy);
    border: none;
    padding: 0 20px;
    font-family: inherit;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    height: 40px;
    transition: background .2s;
    letter-spacing: .03em;
}

.search-wrap button:hover { background: var(--amber-dk); }

/* Search Dropdown */
.search-results-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0; right: 0;
    background: var(--surface);
    border-radius: var(--radius-md);
    box-shadow: 0 12px 40px rgba(0,0,0,.18);
    border: 1px solid var(--border);
    z-index: 300;
    max-height: 380px;
    overflow-y: auto;
    display: none;
}

.search-results-dropdown.show { display: block; }

.search-result-section { padding: 8px; }

.search-result-section h4 {
    padding: 6px 10px 4px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--muted);
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 10px;
    text-decoration: none;
    color: var(--text);
    border-radius: var(--radius-sm);
    transition: background .15s;
}

.search-result-item:hover { background: var(--bg); }

.search-result-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--navy);
    color: white;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.search-result-info .name { font-weight: 700; font-size: 0.875rem; }
.search-result-info .detail { font-size: 0.72rem; color: var(--muted); margin-top: 1px; }

/* Icon row */
.icon-section {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
    flex-shrink: 0;
}

.icon-btn {
    position: relative;
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%;
    color: rgba(255,255,255,.75);
    text-decoration: none;
    font-size: 1.15rem;
    transition: background .2s, color .2s;
}

.icon-btn:hover {
    background: rgba(255,255,255,.12);
    color: #fff;
}

.badge-dot {
    position: absolute;
    top: 4px; right: 4px;
    background: var(--red);
    color: white;
    font-size: 0.58rem;
    font-weight: 800;
    min-width: 16px; height: 16px;
    border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px;
    border: 1.5px solid var(--navy);
}

@keyframes bellShake {
    0%,100% { transform: rotate(0); }
    20%      { transform: rotate(12deg); }
    40%      { transform: rotate(-12deg); }
    60%      { transform: rotate(6deg); }
    80%      { transform: rotate(-4deg); }
}
.has-notifications { animation: bellShake .5s ease-in-out; }

/* ========================================
   NAVIGATION BAR - EXACT MATCH DASHBOARD
   ======================================== */
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
    transition: background .15s, color .15s;
    letter-spacing: .01em;
}

.nav-bar a:hover, .nav-bar a.active {
    background: var(--bg);
    color: var(--navy);
}

.nav-bar .logout {
    margin-left: auto;
    color: var(--red) !important;
}

.nav-bar .logout:hover {
    background: #fef2f2 !important;
    color: var(--red) !important;
}

/* ========================================
   MAIN CONTAINER
   ======================================== */
.main-container {
    max-width: 1200px;
    margin: 28px auto;
    padding: 0 24px 60px;
}

/* Hero strip */
.hero-strip {
    background: linear-gradient(120deg, var(--navy) 0%, var(--navy-mid) 100%);
    border-radius: var(--radius-lg);
    padding: 32px 40px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    overflow: hidden;
    position: relative;
}

.hero-strip::before {
    content: '';
    position: absolute;
    right: -60px; top: -60px;
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(245,158,11,.18) 0%, transparent 70%);
    pointer-events: none;
}

.hero-strip .eyebrow {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--amber);
    margin-bottom: 6px;
}

.hero-strip h2 {
    font-size: 1.6rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
    margin-bottom: 6px;
}

.hero-strip p {
    font-size: 0.875rem;
    color: rgba(255,255,255,.6);
    max-width: 380px;
}

.hero-emoji {
    font-size: 4rem;
    opacity: .85;
    flex-shrink: 0;
}

/* Profile Container */
.profile-container {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    overflow: hidden;
}

/* Profile Card */
.profile-card {
    display: flex;
    align-items: center;
    gap: 30px;
    padding: 32px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
    flex-shrink: 0;
}

.profile-info h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
}

.profile-info p {
    color: var(--muted);
    font-size: 0.85rem;
    margin-bottom: 8px;
}

.rating-box {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--amber-lt);
    padding: 6px 14px;
    border-radius: 40px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #92400e;
}

/* Button Group for Rate and Message */
.profile-buttons {
    display: flex;
    gap: 12px;
    margin-top: 12px;
    flex-wrap: wrap;
}

/* Rate Button */
.rate-btn {
    display: inline-block;
    padding: 8px 20px;
    background: var(--amber);
    color: var(--navy);
    text-decoration: none;
    border-radius: 40px;
    font-weight: 700;
    font-size: 0.8rem;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.rate-btn:hover {
    background: var(--amber-dk);
    transform: translateY(-2px);
}

/* Message Button */
.message-btn {
    display: inline-block;
    padding: 8px 20px;
    background: transparent;
    color: var(--navy);
    text-decoration: none;
    border-radius: 40px;
    font-weight: 700;
    font-size: 0.8rem;
    transition: all 0.2s;
    border: 1.5px solid var(--navy);
    cursor: pointer;
}

.message-btn:hover {
    background: var(--bg);
    transform: translateY(-2px);
}

.message-box {
    margin-top: 12px;
    padding: 10px 16px;
    border-radius: var(--radius-sm);
    font-size: 0.8rem;
}

.info-message {
    background: #dbeafe;
    color: var(--navy);
}

.success-message {
    background: var(--green-lt);
    color: var(--green);
}

.warning-message {
    background: #fee2e2;
    color: var(--red);
}

/* Section Styles */
.section {
    padding: 24px 32px;
    border-bottom: 1px solid var(--border);
}

.section:last-child {
    border-bottom: none;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.section-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}

.section-header .count {
    background: var(--bg);
    color: var(--muted);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 20px;
}

/* Items Grid */
.items-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.item-card {
    background: var(--surface);
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
    display: block;
}

.item-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
}

.item-image {
    width: 100%;
    height: 160px;
    object-fit: cover;
}

.item-info {
    padding: 12px;
}

.item-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--text);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

.item-price {
    font-weight: 700;
    color: var(--amber);
    font-size: 0.9rem;
    margin-bottom: 4px;
}

.item-category {
    font-size: 0.65rem;
    color: var(--muted);
    background: var(--bg);
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-block;
}

.no-items {
    text-align: center;
    padding: 40px;
    color: var(--muted);
    background: var(--bg);
    border-radius: var(--radius-md);
}

/* Reviews */
.review-card {
    background: var(--bg);
    padding: 16px;
    border-radius: var(--radius-md);
    margin-bottom: 16px;
}

.review-card:last-child {
    margin-bottom: 0;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

.review-name {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--text);
}

.review-rating {
    color: var(--amber);
    font-weight: 600;
    font-size: 0.8rem;
}

.review-comment {
    font-size: 0.8rem;
    color: var(--text);
    line-height: 1.5;
    margin-bottom: 8px;
}

.review-date {
    font-size: 0.65rem;
    color: var(--muted);
}

.no-review {
    text-align: center;
    padding: 40px;
    color: var(--muted);
}

/* Responsive */
@media (max-width: 960px) {
    .top-bar { padding: 0 20px; height: auto; flex-wrap: wrap; padding: 12px 20px; gap: 12px; }
    .search-wrap { max-width: 100%; order: 3; flex-basis: 100%; }
    .hero-strip { padding: 24px; }
    .hero-emoji { display: none; }
    .main-container { padding: 0 16px 48px; }
    .nav-bar { padding: 0 16px; flex-wrap: wrap; height: auto; padding: 8px 16px; gap: 8px; }
    .nav-bar .logout { margin-left: 0; }
    .items-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .profile-card { padding: 24px; gap: 20px; }
    .section { padding: 20px 24px; }
}

@media (max-width: 560px) {
    .hero-strip { text-align: center; flex-direction: column; }
    .hero-strip h2 { font-size: 1.3rem; }
    .profile-card { flex-direction: column; text-align: center; }
    .items-grid { grid-template-columns: 1fr; }
    .section { padding: 16px 20px; }
    .profile-buttons { justify-content: center; }
}
</style>
</head>
<body>

<!-- TOP BAR -->
<header class="top-bar">
    <a href="dashboard.php" class="logo-section">
        <img src="umpsalgobaru.png" alt="UMPSA">
        <div class="logo-text">
            <div class="brand">Student Marketplace</div>
            <div class="sub">UMPSA Campus</div>
        </div>
    </a>

    <div class="search-wrap">
        <form method="GET" action="dashboard.php" id="searchForm">
            <input type="text" id="searchInput" name="search" placeholder="Search items, sellers, categories…" autocomplete="off">
            <button type="submit">Search</button>
        </form>
        <div id="searchDropdown" class="search-results-dropdown"></div>
    </div>

    <div class="icon-section">
        <a href="notifications.php" class="icon-btn notification-bell <?php echo $unread_count > 0 ? 'has-notifications' : ''; ?>" title="Notifications">
            🔔
            <?php if ($unread_count > 0): ?>
                <span class="badge-dot"><?php echo $unread_count > 9 ? '9+' : $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="cart.php" class="icon-btn" title="Cart">
            🛒
            <?php if ($cart_count > 0): ?>
                <span class="badge-dot"><?php echo $cart_count > 9 ? '9+' : $cart_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" class="icon-btn" title="Profile">👤</a>
    </div>
</header>

<!-- NAVIGATION -->
<nav class="nav-bar">
    <a href="dashboard.php">Home</a>
    <a href="post_item.php">Post Item</a>
    <a href="my_listings.php">My Listings</a>
    <a href="messages.php">Messages</a>
    <a href="logout.php" class="logout">Logout</a>
</nav>

<!-- MAIN CONTENT -->
<main class="main-container">
    <!-- Hero Strip -->
    <div class="hero-strip">
        <div>
            <div class="eyebrow">UMPSA Student Exchange</div>
            <h2>Student Profile</h2>
            <p>View user information, ratings, and listed items</p>
        </div>
        <div class="hero-emoji">👤</div>
    </div>

    <!-- Profile Container -->
    <div class="profile-container">
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($username); ?></h2>
                <p><?php echo htmlspecialchars($email); ?></p>
                <div class="rating-box">
                    ⭐ <?php echo $avgRating ? $avgRating . " / 5" : "No ratings yet"; ?>
                    <?php if ($totalReviews > 0): ?>
                        (<?php echo $totalReviews; ?> review<?php echo $totalReviews > 1 ? 's' : ''; ?>)
                    <?php endif; ?>
                </div>

                <?php if ($isOwnProfile): ?>
                    <div class="message-box info-message">
                        ℹ️ This is your profile. You cannot rate yourself.
                    </div>
                <?php elseif ($alreadyReviewed): ?>
                    <div class="message-box info-message">
                        ✅ You have already reviewed this user. Thank you for your feedback!
                    </div>
                <?php else: ?>
                    <div class="profile-buttons">
                        <a class="rate-btn" href="rating_page.php?reviewed_id=<?php echo $viewID; ?>">
                            ⭐ Rate This User
                        </a>
                        <a class="message-btn" href="messages.php?user_id=<?php echo $viewID; ?>">
                            💬 Message
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Posted Section -->
        <div class="section">
            <div class="section-header">
                <h3>📦 Items Posted</h3>
                <span class="count"><?php echo $totalItems; ?></span>
            </div>
            
            <?php if ($totalItems > 0): ?>
                <div class="items-grid">
                    <?php while ($item = mysqli_fetch_assoc($itemsResult)): ?>
                        <a href="item_details.php?id=<?php echo $item['ItemID']; ?>" class="item-card">
                            <img class="item-image" 
                                 src="<?php echo !empty($item['ImagePath']) ? htmlspecialchars($item['ImagePath']) : 'images/default.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['Title']); ?>">
                            <div class="item-info">
                                <span class="item-title"><?php echo htmlspecialchars($item['Title']); ?></span>
                                <div class="item-price">RM <?php echo number_format($item['Price'], 2); ?></div>
                                <span class="item-category"><?php echo htmlspecialchars($item['CategoryName']); ?></span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <p>😕 <?php echo htmlspecialchars($username); ?> hasn't posted any items yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviews Section -->
        <div class="section">
            <div class="section-header">
                <h3>📝 User Reviews</h3>
                <span class="count"><?php echo $totalReviews; ?></span>
            </div>

            <?php 
            $reviewQuery2 = "
                SELECT r.*, s.FullName 
                FROM reviews r
                JOIN students s ON r.ReviewerStudentID = s.StudentID
                WHERE r.ReviewedStudentID = '$viewID'
                ORDER BY r.CreatedAt DESC
            ";
            $reviewResult2 = mysqli_query($conn, $reviewQuery2);
            ?>

            <?php if (mysqli_num_rows($reviewResult2) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($reviewResult2)): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-name"><?php echo htmlspecialchars($row['FullName']); ?></div>
                            <div class="review-rating">
                                <?php 
                                $rating = $row['Rating'];
                                for($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? "⭐" : "☆";
                                }
                                echo " $rating/5";
                                ?>
                            </div>
                        </div>
                        <div class="review-comment">
                            <?php echo nl2br(htmlspecialchars($row['Comment'] ?? 'No comment provided.')); ?>
                        </div>
                        <div class="review-date">
                            📅 <?php echo date('d M Y, h:i A', strtotime($row['CreatedAt'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-review">
                    <p>No reviews yet. Be the first to rate this user!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const searchDropdown = document.getElementById('searchDropdown');
let debounceTimer;

if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchDropdown.classList.remove('show');
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetch(`search_suggestions.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.items.length === 0 && data.students.length === 0) {
                        searchDropdown.classList.remove('show');
                        return;
                    }
                    
                    let html = '';
                    
                    if (data.items.length > 0) {
                        html += '<div class="search-result-section"><h4>📦 Items</h4>';
                        data.items.forEach(item => {
                            html += `<a href="item_details.php?id=${item.ItemID}" class="search-result-item">
                                <div class="search-result-avatar">📦</div>
                                <div class="search-result-info">
                                    <div class="name">${escapeHtml(item.Title)}</div>
                                    <div class="detail">RM ${parseFloat(item.Price).toFixed(2)} · ${escapeHtml(item.CategoryName)}</div>
                                </div>
                            </a>`;
                        });
                        html += '</div>';
                    }
                    
                    if (data.students.length > 0) {
                        html += '<div class="search-result-section"><h4>👤 Students</h4>';
                        data.students.forEach(student => {
                            html += `<a href="profile.php?id=${student.StudentID}" class="search-result-item">
                                <div class="search-result-avatar">${escapeHtml(student.FullName.charAt(0))}</div>
                                <div class="search-result-info">
                                    <div class="name">${escapeHtml(student.FullName)}</div>
                                    <div class="detail">${escapeHtml(student.Email)}</div>
                                </div>
                            </a>`;
                        });
                        html += '</div>';
                    }
                    
                    searchDropdown.innerHTML = html;
                    searchDropdown.classList.add('show');
                });
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
            searchDropdown.classList.remove('show');
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

</body>
</html>