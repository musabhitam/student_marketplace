<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) {
    echo "You must login first.";
    exit();
}

$reporter_id = $_SESSION['student_id'];

// Cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c) {
        $cart_count += $c['quantity'];
    }
}

// Unread notification count
$unread_count = 0;
$unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE StudentID = $reporter_id AND IsRead = 0";
$unread_result = $conn->query($unread_sql);
if ($unread_result && $unread_result->num_rows > 0) {
    $unread_count = $unread_result->fetch_assoc()['count'];
}

/* =========================
   HANDLE FORM SUBMISSION
========================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $reported_id = $_POST['reported_id'];
    $item_id = $_POST['item_id'];
    $reason = trim($_POST['reason']);

    if (empty($reason)) {

        echo "<script>alert('Reason cannot be empty');</script>";

    } else {

        // Prevent duplicate report
        $check = "SELECT * FROM reports 
                  WHERE ReporterStudentID=? 
                  AND ReportedStudentID=? 
                  AND ItemID=?";

        $stmt = $conn->prepare($check);
        $stmt->bind_param("iii", $reporter_id, $reported_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            echo "<script>
            alert('You already reported this seller for this item.');
            window.location.href='dashboard.php';
            </script>";
            exit();
        }

        // Insert report
        $sql = "INSERT INTO reports 
                (Reason, Status, CreatedAt, ReporterStudentID, ReportedStudentID, ItemID) 
                VALUES (?, 'Pending', NOW(), ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siii", $reason, $reporter_id, $reported_id, $item_id);

        if ($stmt->execute()) {

            echo "<script>
            alert('Report submitted successfully');
            window.location.href='dashboard.php';
            </script>";
            exit();

        } else {

            echo "Error submitting report.";

        }
    }
}

/* =========================
   GET DATA FOR FORM
========================= */
if (!isset($_GET['reported_id']) || !isset($_GET['item_id'])) {
    echo "Invalid access.";
    exit();
}

$reported_id = $_GET['reported_id'];
$item_id = $_GET['item_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Seller | Student Marketplace</title>

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

/* Report Container */
.report-container {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    overflow: hidden;
}

.report-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--bg);
}

.report-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}

/* Warning Box */
.warning-box {
    background: #fef2f2;
    border-left: 4px solid var(--red);
    padding: 16px 20px;
    margin: 20px 24px;
    border-radius: var(--radius-sm);
    color: #991b1b;
    font-size: 0.8rem;
    line-height: 1.5;
}

/* Form */
.report-form {
    padding: 0 24px 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--text);
    margin-bottom: 8px;
}

.form-group textarea {
    width: 100%;
    min-height: 160px;
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
    background: var(--red);
    color: white;
    border: none;
    border-radius: 40px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-submit:hover {
    background: #b91c1c;
    transform: translateY(-2px);
}

.btn-back {
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

.btn-back:hover {
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
    .button-group { flex-direction: column; }
    .btn-submit, .btn-back { justify-content: center; }
    .warning-box { margin: 16px 20px; }
    .report-form { padding: 0 20px 20px; }
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
            <h2>Report Seller</h2>
            <p>Submit a report if you found suspicious or misleading activity</p>
        </div>
        <div class="hero-emoji">🚨</div>
    </div>

    <!-- Report Container -->
    <div class="report-container">
        <div class="report-header">
            <h3>Report Form</h3>
        </div>

        <div class="warning-box">
            ⚠️ Please provide a clear and honest reason for your report. 
            False reports may affect marketplace trust and user experience.
        </div>

        <form method="POST" class="report-form">
            <input type="hidden" name="reported_id" value="<?php echo $reported_id; ?>">
            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">

            <div class="form-group">
                <label>📝 Reason for Report</label>
                <textarea 
                    name="reason" 
                    placeholder="Explain why you are reporting this seller..."
                    required
                ></textarea>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-submit">
                    📤 Submit Report
                </button>
                <a class="btn-back" href="item_details.php?id=<?php echo $item_id; ?>">
                    ⬅ Back to Item
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
</script>

</body>
</html>