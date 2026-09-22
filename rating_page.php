<?php
session_start();
include("connection.php");

$reviewerID = $_SESSION['student_id'] ?? 0;
$reviewedID = isset($_GET['reviewed_id']) ? intval($_GET['reviewed_id']) : 0;

// Cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c) {
        $cart_count += $c['quantity'];
    }
}

// Unread notification count
$unread_count = 0;
if ($reviewerID > 0) {
    $unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE StudentID = $reviewerID AND IsRead = 0";
    $unread_result = $conn->query($unread_sql);
    if ($unread_result && $unread_result->num_rows > 0) {
        $unread_count = $unread_result->fetch_assoc()['count'];
    }
}

// If no user is logged in
if ($reviewerID == 0) {
    echo "<script>
            alert('Please login to rate users!');
            window.location='login.php';
          </script>";
    exit();
}

// If no reviewed ID is provided
if ($reviewedID == 0) {
    echo "<script>
            alert('Invalid user!');
            window.location='dashboard.php';
          </script>";
    exit();
}

// Get the user being rated
$userQuery = "SELECT FullName, Email FROM students WHERE StudentID = '$reviewedID'";
$userResult = mysqli_query($conn, $userQuery);
$reviewedUser = mysqli_fetch_assoc($userResult);

if (!$reviewedUser) {
    echo "<script>
            alert('User not found!');
            window.location='dashboard.php';
          </script>";
    exit();
}

// Check if already reviewed
$checkQuery = "SELECT * FROM reviews WHERE ReviewerStudentID = '$reviewerID' AND ReviewedStudentID = '$reviewedID'";
$checkResult = mysqli_query($conn, $checkQuery);
$alreadyReviewed = mysqli_num_rows($checkResult) > 0;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Prevent self rating
    if ($reviewerID == $reviewedID) {
        echo "<script>
                alert('You cannot rate yourself!');
                window.location='profile.php?id=$reviewedID';
              </script>";
        exit();
    }

    // Check again for duplicate (in case of double submit)
    if ($alreadyReviewed) {
        echo "<script>
                alert('You have already reviewed this user!');
                window.location='profile.php?id=$reviewedID';
              </script>";
        exit();
    }

    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        echo "<script>
                alert('Please select a valid rating (1-5 stars)!');
                window.location='rating_page.php?reviewed_id=$reviewedID';
              </script>";
        exit();
    }

    // Escape comment to prevent SQL injection
    $comment_escaped = mysqli_real_escape_string($conn, $comment);

    $sql = "INSERT INTO reviews 
            (Rating, Comment, ReviewerStudentID, ReviewedStudentID, CreatedAt)
            VALUES 
            ('$rating', '$comment_escaped', '$reviewerID', '$reviewedID', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
                alert('✅ Review submitted successfully!');
                window.location='profile.php?id=$reviewedID&success=rated';
              </script>";
        exit();
    } else {
        echo "<script>
                alert('Error: " . mysqli_error($conn) . "');
                window.location='rating_page.php?reviewed_id=$reviewedID';
              </script>";
        exit();
    }
}

// If already reviewed, redirect back
if ($alreadyReviewed) {
    echo "<script>
            alert('You have already reviewed this user!');
            window.location='profile.php?id=$reviewedID';
          </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rate User | Student Marketplace</title>

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
    max-width: 800px;
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

/* Rating Container */
.rating-container {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    overflow: hidden;
}

.rating-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--bg);
}

.rating-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}

/* User Info Card */
.user-info-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    background: var(--bg);
    margin: 0;
    border-bottom: 1px solid var(--border);
}

.user-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
    font-weight: 700;
}

.user-details h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
}

.user-details p {
    font-size: 0.8rem;
    color: var(--muted);
}

/* Warning Box */
.warning-box {
    background: #fee2e2;
    border-left: 4px solid var(--red);
    padding: 14px 20px;
    margin: 20px 24px;
    border-radius: var(--radius-sm);
    color: #991b1b;
    font-size: 0.8rem;
}

/* Form */
.rating-form {
    padding: 24px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--text);
    margin-bottom: 12px;
}

/* Star Rating */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-start;
    gap: 10px;
}

.star-rating input {
    display: none;
}

.star-rating label {
    font-size: 2.5rem;
    color: var(--border);
    cursor: pointer;
    transition: color 0.2s;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: var(--amber);
}

.rating-hint {
    font-size: 0.7rem;
    color: var(--muted);
    margin-top: 8px;
}

/* Textarea */
.form-group textarea {
    width: 100%;
    padding: 14px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 0.85rem;
    color: var(--text);
    resize: vertical;
    transition: all 0.2s;
}

.form-group textarea:focus {
    outline: none;
    border-color: var(--amber);
    box-shadow: 0 0 0 3px rgba(245,158,11,0.1);
}

/* Button Group */
.button-group {
    display: flex;
    gap: 15px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.btn-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: var(--amber);
    color: var(--navy);
    border: none;
    border-radius: 40px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-submit:hover {
    background: var(--amber-dk);
    transform: translateY(-2px);
}

.btn-cancel {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: transparent;
    color: var(--muted);
    border: 1.5px solid var(--border);
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-cancel:hover {
    background: var(--bg);
    color: var(--text);
    transform: translateY(-2px);
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
}

@media (max-width: 560px) {
    .hero-strip { text-align: center; flex-direction: column; }
    .hero-strip h2 { font-size: 1.3rem; }
    .user-info-card { flex-direction: column; text-align: center; }
    .star-rating label { font-size: 2rem; }
    .button-group { flex-direction: column; }
    .btn-submit, .btn-cancel { justify-content: center; }
    .rating-form { padding: 20px; }
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
            <h2>Rate This User</h2>
            <p>Share your experience and help others in the community</p>
        </div>
        <div class="hero-emoji">⭐</div>
    </div>

    <!-- Rating Container -->
    <div class="rating-container">
        <div class="rating-header">
            <h3>Rate User</h3>
        </div>

        <!-- User Info Card -->
        <div class="user-info-card">
            <div class="user-avatar">
                <?php echo strtoupper(substr($reviewedUser['FullName'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <h3><?php echo htmlspecialchars($reviewedUser['FullName']); ?></h3>
                <p><?php echo htmlspecialchars($reviewedUser['Email']); ?></p>
            </div>
        </div>

        <!-- Warning for self-rating -->
        <?php if ($reviewerID == $reviewedID): ?>
            <div class="warning-box">
                ⚠️ You cannot rate yourself. Please go back to the previous page.
            </div>
        <?php endif; ?>

        <!-- Rating Form -->
        <form method="POST" class="rating-form">
            <div class="form-group">
                <label>⭐ Your Rating</label>
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5">★</label>
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4">★</label>
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3">★</label>
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2">★</label>
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1">★</label>
                </div>
                <div class="rating-hint">
                    Click on a star to rate (1 = Poor, 5 = Excellent)
                </div>
            </div>

            <div class="form-group">
                <label>💬 Your Review (Optional)</label>
                <textarea 
                    name="comment" 
                    rows="5" 
                    placeholder="Share your experience with this user... How was the transaction? Was the item as described? Would you recommend them?"
                ></textarea>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-submit">
                    Submit Review
                </button>
                <a class="btn-cancel" href="profile.php?id=<?php echo $reviewedID; ?>">
                    Cancel
                </a>
            </div>
        </form>
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

// Star rating click handler
document.querySelectorAll('.star-rating input').forEach(star => {
    star.addEventListener('change', function() {
        console.log('Selected rating: ' + this.value);
    });
});
</script>

</body>
</html>