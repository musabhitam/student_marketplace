<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Check if user is suspended
$check_suspended = "SELECT IsVerified FROM students WHERE StudentID = $student_id";
$suspended_result = $conn->query($check_suspended);
$is_suspended = false;
if ($suspended_result && $suspended_result->num_rows > 0) {
    $row = $suspended_result->fetch_assoc();
    $is_suspended = ($row['IsVerified'] == 0);
}

// Get user's listings with images
$stmt = $conn->prepare("
    SELECT DISTINCT items.*, 
           (SELECT ImagePath FROM item_images 
            WHERE item_images.ItemID = items.ItemID 
            LIMIT 1) AS ImagePath
    FROM items
    WHERE items.StudentID = ?
    ORDER BY items.ItemID DESC
");

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c) {
        $cart_count += $c['quantity'];
    }
}

// Unread notification count
$unread_count = 0;
$unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE StudentID = $student_id AND IsRead = 0";
$unread_result = $conn->query($unread_sql);
if ($unread_result && $unread_result->num_rows > 0) {
    $unread_count = $unread_result->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Listings | Student Marketplace</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========================================
   GLOBAL VARIABLES - EXACT MATCH
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
   TOP BAR - EXACT MATCH
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
   NAVIGATION BAR - EXACT MATCH
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
   MAIN CONTAINER - EXACT MATCH
   ======================================== */
.main-container {
    max-width: 1200px;
    margin: 28px auto;
    padding: 0 24px 60px;
}

/* Hero strip - EXACT MATCH */
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

/* Section header */
.section-meta {
    display: flex;
    align-items: baseline;
    gap: 10px;
    margin-bottom: 20px;
}

.section-meta h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}

.count-pill {
    background: var(--bg);
    color: var(--muted);
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    border: 1px solid var(--border);
}

/* Suspended Banner */
.suspended-banner {
    background: #fef2f2;
    border-left: 4px solid var(--red);
    padding: 14px 20px;
    border-radius: var(--radius-sm);
    margin-bottom: 24px;
    color: #991b1b;
    font-size: 0.85rem;
    font-weight: 500;
}

/* Post Button */
.post-btn {
    display: inline-block;
    margin-bottom: 28px;
    padding: 10px 24px;
    background: var(--amber);
    color: var(--navy);
    font-weight: 700;
    font-size: 0.85rem;
    border-radius: 40px;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.post-btn:hover {
    background: var(--amber-dk);
    transform: translateY(-2px);
}

.post-btn.disabled {
    background: var(--muted);
    cursor: not-allowed;
    pointer-events: none;
    color: white;
}

/* ========================================
   ITEM GRID - EXACT MATCH WITH DASHBOARD
   ======================================== */
.item-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.item-card {
    background: var(--surface);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    transition: transform .2s, box-shadow .2s;
    overflow: hidden;
}

.item-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
}

.image-box {
    width: 100%;
    aspect-ratio: 4/3;
    overflow: hidden;
    position: relative;
    background: var(--bg);
}

.image-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .35s ease;
}

.item-card:hover .image-box img {
    transform: scale(1.04);
}

.condition-ribbon {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 4px 12px;
    color: white;
    letter-spacing: .02em;
}

.card-body {
    padding: 14px 16px 16px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.cat-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    background: #eff6ff;
    color: #1d4ed8;
    margin-bottom: 8px;
    align-self: flex-start;
    letter-spacing: .02em;
}

.product-name {
    font-weight: 700;
    font-size: 0.95rem;
    line-height: 1.35;
    margin-bottom: 6px;
    color: var(--text);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-desc {
    font-size: 0.75rem;
    color: var(--muted);
    line-height: 1.5;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.price {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 12px;
    letter-spacing: -.01em;
}

/* Card Actions */
.card-actions {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.btn-edit, .btn-delete, .btn-sold {
    flex: 1;
    padding: 8px 0;
    text-align: center;
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 0.8rem;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
    font-family: inherit;
}

.btn-edit {
    background: #eff6ff;
    color: var(--navy);
    border: 1px solid #bfdbfe;
}

.btn-edit:hover {
    background: #dbeafe;
}

.btn-sold {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.btn-sold:hover {
    background: #fde68a;
}

.btn-delete {
    background: #fff1f2;
    color: var(--red);
    border: 1px solid #fecdd3;
}

.btn-delete:hover {
    background: #ffe4e6;
}

/* Empty state */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 72px 20px;
    background: var(--surface);
    border-radius: var(--radius-md);
    border: 1.5px dashed var(--border);
    color: var(--muted);
}

.empty-state .icon { font-size: 3rem; margin-bottom: 12px; }
.empty-state h3 { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.empty-state p { font-size: 0.875rem; }
.empty-state a { color: var(--navy); font-weight: 600; text-decoration: none; }
.empty-state a:hover { text-decoration: underline; }

/* ========================================
   RESPONSIVE - EXACT MATCH
   ======================================== */
@media (max-width: 960px) {
    .top-bar { padding: 0 20px; height: auto; flex-wrap: wrap; padding: 12px 20px; gap: 12px; }
    .search-wrap { max-width: 100%; order: 3; flex-basis: 100%; }
    .hero-strip { padding: 24px; }
    .hero-emoji { display: none; }
    .item-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .main-container { padding: 0 16px 48px; }
    .nav-bar { padding: 0 16px; flex-wrap: wrap; height: auto; padding: 8px 16px; gap: 8px; }
    .nav-bar .logout { margin-left: 0; }
    .post-btn { width: 100%; text-align: center; }
}

@media (max-width: 560px) {
    .item-grid { grid-template-columns: 1fr; }
    .hero-strip h2 { font-size: 1.3rem; }
    .hero-strip { text-align: center; flex-direction: column; }
    .card-actions { flex-direction: column; }
}
</style>
</head>
<body>

<!-- TOP BAR - EXACT MATCH -->
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

<!-- NAVIGATION - EXACT MATCH -->
<nav class="nav-bar">
    <a href="dashboard.php">Home</a>
    <a href="post_item.php">Post Item</a>
    <a href="my_listings.php" class="active">My Listings</a>
    <a href="messages.php">Messages</a>
    <a href="logout.php" class="logout">Logout</a>
</nav>

<!-- MAIN CONTENT -->
<main class="main-container">

    <!-- Hero Strip -->
    <div class="hero-strip">
        <div>
            <div class="eyebrow">UMPSA Student Exchange</div>
            <h2>My Listings</h2>
            <p>Manage your items — edit, delete, or mark as sold</p>
        </div>
        <div class="hero-emoji">📦</div>
    </div>

    <?php if ($is_suspended): ?>
    <div class="suspended-banner">
        ⚠️ Account Suspended - You cannot post new items. Contact admin.
    </div>
    <?php endif; ?>

    <a href="post_item.php" class="post-btn <?php echo $is_suspended ? 'disabled' : ''; ?>">
        + Post New Item
    </a>

    <!-- Section Header -->
    <div class="section-meta">
        <h3>Your Listings</h3>
        <span class="count-pill"><?php echo $result->num_rows; ?></span>
    </div>

    <div class="item-grid" id="itemList">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="item-card">
                    <div class="image-box">
                        <img src="<?php echo !empty($row['ImagePath']) ? htmlspecialchars($row['ImagePath']) : 'images/default.png'; ?>" alt="<?php echo htmlspecialchars($row['Title']); ?>">
                        <span class="condition-ribbon"><?php echo htmlspecialchars($row['Condition']); ?></span>
                    </div>
                    <div class="card-body">
                        <span class="cat-badge">📦 <?php echo htmlspecialchars($row['CategoryID'] == 2 ? 'Books' : ($row['CategoryID'] == 3 ? 'Electronics' : ($row['CategoryID'] == 4 ? 'Clothing' : 'General'))); ?></span>
                        <h3 class="product-name"><?php echo htmlspecialchars($row['Title']); ?></h3>
                        <p class="product-desc"><?php echo htmlspecialchars(substr($row['Description'], 0, 80)); ?>...</p>
                        <div class="price">RM <?php echo number_format($row['Price'], 2); ?></div>
                        
                        <div class="card-actions">
                            <?php if (!$is_suspended): ?>
                                <a href="edit_listing.php?id=<?php echo $row['ItemID']; ?>" class="btn-edit">✏️ Edit</a>
                                <?php if ($row['Status'] != 'Sold'): ?>
                                    <a href="mark_sold.php?id=<?php echo $row['ItemID']; ?>" class="btn-sold" onclick="return confirm('Mark this item as sold? It will no longer appear in the marketplace.')">💰 Mark as Sold</a>
                                <?php endif; ?>
                                <button onclick="deleteListing(<?php echo $row['ItemID']; ?>)" class="btn-delete">🗑️ Delete</button>
                            <?php else: ?>
                                <a href="#" class="btn-edit" style="background:#f3f4f6;color:#9ca3af;cursor:not-allowed;pointer-events:none;">✏️ Edit</a>
                                <a href="#" class="btn-sold" style="background:#f3f4f6;color:#9ca3af;cursor:not-allowed;pointer-events:none;">💰 Mark as Sold</a>
                                <button disabled class="btn-delete" style="background:#f3f4f6;color:#9ca3af;cursor:not-allowed;">🗑️ Delete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>No listings yet</h3>
                <p>You haven't posted any items. <a href="post_item.php">Post your first item</a> to get started!</p>
            </div>
        <?php endif; ?>
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

function deleteListing(itemId) {
    if (confirm("Are you sure you want to delete this listing?")) {
        window.location.href = "delete_listing.php?id=" + itemId;
    }
}
</script>

</body>
</html>