<?php
session_start();
include("connection.php");

if (!isset($_GET['id'])) {
    echo "Invalid item.";
    exit();
}

$item_id = $_GET['id'];

// Cart count for badge
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c) {
        $cart_count += $c['quantity'];
    }
}

// Unread notification count
$unread_count = 0;
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    $unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE StudentID = $student_id AND IsRead = 0";
    $unread_result = $conn->query($unread_sql);
    if ($unread_result && $unread_result->num_rows > 0) {
        $unread_count = $unread_result->fetch_assoc()['count'];
    }
}

/* Get item + seller + category - ONLY if seller is verified and item is available */
$sql = "SELECT items.*, students.FullName, students.Email, students.Phone, students.IsVerified,
               categories.CategoryName
        FROM items
        JOIN students ON items.StudentID = students.StudentID
        JOIN categories ON items.CategoryID = categories.CategoryID
        WHERE items.ItemID = ?
        AND students.IsVerified = 1
        AND items.Status = 'Available'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<h2>Item not available</h2>";
    echo "<p>This item has been removed or the seller is no longer active.</p>";
    echo "<a href='dashboard.php'>← Back to Marketplace</a>";
    exit();
}

$item = $result->fetch_assoc();

/* Get images */
$img_sql = "SELECT * FROM item_images WHERE ItemID = ?";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param("i", $item_id);
$img_stmt->execute();
$images = $img_stmt->get_result();

/* Campus coordinates */
$campus_coords = [
    'Pekan'   => ['lat' => 3.5386, 'lng' => 103.4290, 'label' => 'UMP Pekan Campus'],
    'Gambang' => ['lat' => 3.7480, 'lng' => 103.1623, 'label' => 'UMP Gambang Campus'],
];

$campus = $item['Campus'];
$coords = isset($campus_coords[$campus]) ? $campus_coords[$campus] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($item['Title']); ?> | Student Marketplace</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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

/* Item Detail Container */
.item-detail-container {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    overflow: hidden;
}

/* Image Gallery */
.image-gallery {
    background: var(--bg);
    padding: 20px;
    border-bottom: 1px solid var(--border);
}

.main-image {
    width: 100%;
    max-height: 500px;
    object-fit: contain;
    border-radius: var(--radius-md);
}

/* Item Header */
.item-header {
    padding: 24px 32px;
    border-bottom: 1px solid var(--border);
}

.item-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 12px;
}

.item-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.category-badge {
    display: inline-block;
    background: var(--amber-lt);
    color: #92400e;
    padding: 4px 14px;
    border-radius: 40px;
    font-size: 0.75rem;
    font-weight: 600;
}

.condition-badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 40px;
    font-size: 0.75rem;
    font-weight: 600;
}

.condition-New { background: #dcfce7; color: #16a34a; }
.condition-Like-New { background: #d1fae5; color: #059669; }
.condition-Good { background: #fef9c3; color: #ca8a04; }
.condition-Fair { background: #ffedd5; color: #ea580c; }
.condition-Poor { background: #fee2e2; color: #dc2626; }

.price {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--amber);
    margin-top: 16px;
}

/* Item Body */
.item-body {
    padding: 24px 32px;
    border-bottom: 1px solid var(--border);
}

.section-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 12px;
    letter-spacing: 0.3px;
}

.item-description {
    font-size: 0.85rem;
    line-height: 1.6;
    color: var(--muted);
}

/* Info Table */
.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table td {
    padding: 10px 0;
    font-size: 0.85rem;
    border-bottom: 1px solid var(--border);
}

.info-table td:first-child {
    font-weight: 600;
    color: var(--muted);
    width: 140px;
}

.info-table tr:last-child td {
    border-bottom: none;
}

/* Seller Box */
.seller-box {
    background: var(--bg);
    border-radius: var(--radius-md);
    padding: 20px;
}

.seller-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--text);
    margin-bottom: 8px;
}

.seller-name a {
    color: var(--navy);
    text-decoration: none;
}

.seller-name a:hover {
    color: var(--amber);
    text-decoration: underline;
}

.seller-detail {
    font-size: 0.8rem;
    color: var(--muted);
    margin-bottom: 6px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.btn-message {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: var(--amber);
    color: var(--navy);
    border: none;
    border-radius: 40px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-message:hover {
    background: var(--amber-dk);
    transform: translateY(-2px);
}

.btn-report {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: transparent;
    color: var(--red);
    border: 1.5px solid var(--red);
    border-radius: 40px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-report:hover {
    background: #fee2e2;
    transform: translateY(-2px);
}

/* Back Button */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 24px;
    padding: 10px 24px;
    background: var(--navy);
    color: white;
    text-decoration: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s;
}

.back-btn:hover {
    background: var(--navy-mid);
    transform: translateY(-2px);
}

/* Map */
.map-container {
    padding: 24px 32px;
    border-bottom: 1px solid var(--border);
}

.map-label {
    font-size: 0.8rem;
    color: var(--muted);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

#campusMap {
    width: 100%;
    height: 300px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    z-index: 0;
}

/* Responsive */
@media (max-width: 960px) {
    .top-bar { padding: 0 20px; height: auto; flex-wrap: wrap; padding: 12px 20px; gap: 12px; }
    .search-wrap { max-width: 100%; order: 3; flex-basis: 100%; }
    .main-container { padding: 0 16px 48px; }
    .nav-bar { padding: 0 16px; flex-wrap: wrap; height: auto; padding: 8px 16px; gap: 8px; }
    .nav-bar .logout { margin-left: 0; }
    .item-header, .item-body, .map-container { padding: 20px 24px; }
    .price { font-size: 1.5rem; }
    .item-title { font-size: 1.3rem; }
}

@media (max-width: 560px) {
    .action-buttons { flex-direction: column; }
    .btn-message, .btn-report { justify-content: center; }
    .info-table td:first-child { width: 100px; }
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
    <div class="item-detail-container">
        <!-- Image Gallery -->
        <div class="image-gallery">
            <?php
            if ($images->num_rows > 0) {
                $first_img = $images->fetch_assoc();
                echo '<img class="main-image" src="' . htmlspecialchars($first_img['ImagePath']) . '" alt="' . htmlspecialchars($item['Title']) . '">';
            } else {
                echo '<img class="main-image" src="images/default.png" alt="Default image">';
            }
            ?>
        </div>

        <!-- Item Header -->
        <div class="item-header">
            <h1 class="item-title"><?php echo htmlspecialchars($item['Title']); ?></h1>
            <div class="item-meta">
                <span class="category-badge">📦 <?php echo htmlspecialchars($item['CategoryName']); ?></span>
                <?php
                    $cond = $item['Condition'];
                    $cond_class = 'condition-' . str_replace(' ', '-', $cond);
                ?>
                <span class="condition-badge <?php echo $cond_class; ?>"><?php echo htmlspecialchars($cond); ?></span>
            </div>
            <div class="price">RM <?php echo number_format($item['Price'], 2); ?></div>
        </div>

        <!-- Description -->
        <div class="item-body">
            <div class="section-title">📝 Description</div>
            <div class="item-description">
                <?php echo nl2br(htmlspecialchars($item['Description'])); ?>
            </div>
        </div>

        <!-- Item Details -->
        <div class="item-body">
            <div class="section-title">📋 Item Details</div>
            <table class="info-table">
                <tr><td>Category</td><td><?php echo htmlspecialchars($item['CategoryName']); ?></td></tr>
                <tr><td>Condition</td><td><?php echo htmlspecialchars($item['Condition']); ?></td></tr>
                <tr><td>Campus</td><td><?php echo htmlspecialchars($item['Campus']); ?></td></tr>
                <tr><td>Status</td><td><?php echo htmlspecialchars($item['Status']); ?></td></tr>
                <tr><td>Posted</td><td><?php echo date('d M Y, h:i A', strtotime($item['CreatedAt'])); ?></td></tr>
            </table>
        </div>

        <!-- Map -->
        <?php if ($coords): ?>
        <div class="map-container">
            <div class="section-title">📍 Meetup Location</div>
            <div class="map-label">📌 <?php echo htmlspecialchars($coords['label']); ?> — suggested meetup area</div>
            <div id="campusMap"></div>
        </div>
        <?php endif; ?>

        <!-- Seller Info -->
        <div class="item-body">
            <div class="section-title">👤 Seller Information</div>
            <div class="seller-box">
                <div class="seller-name">
                    <a href="profile.php?id=<?php echo $item['StudentID']; ?>"><?php echo htmlspecialchars($item['FullName']); ?></a>
                </div>
                <div class="seller-detail">📧 <?php echo htmlspecialchars($item['Email']); ?></div>
                <div class="seller-detail">📞 <?php echo htmlspecialchars($item['Phone']); ?></div>
            </div>

            <?php if (isset($_SESSION['student_id']) && $_SESSION['student_id'] != $item['StudentID']): ?>
            <div class="action-buttons">
                <a href="messages.php?user_id=<?php echo $item['StudentID']; ?>" class="btn-message">
                    💬 Message Seller
                </a>
                <a href="report.php?reported_id=<?php echo $item['StudentID']; ?>&item_id=<?php echo $item['ItemID']; ?>" class="btn-report">
                    🚨 Report Seller
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="text-align: center;">
        <a href="dashboard.php" class="back-btn">← Back to Marketplace</a>
    </div>
</main>

<?php if ($coords): ?>
<script>
    const map = L.map('campusMap').setView([<?php echo $coords['lat']; ?>, <?php echo $coords['lng']; ?>], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    L.marker([<?php echo $coords['lat']; ?>, <?php echo $coords['lng']; ?>])
        .addTo(map)
        .bindPopup('<b><?php echo htmlspecialchars($coords['label']); ?></b><br>Suggested meetup location')
        .openPopup();
</script>
<?php endif; ?>

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