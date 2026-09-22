<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "student_marketplace");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch real data for dashboard cards
$total_users = 0;
$total_users_result = $conn->query("SELECT COUNT(*) as count FROM students");
if ($total_users_result && $row = $total_users_result->fetch_assoc()) {
    $total_users = $row['count'];
}

$total_items = 0;
$total_items_result = $conn->query("SELECT COUNT(*) as count FROM items");
if ($total_items_result && $row = $total_items_result->fetch_assoc()) {
    $total_items = $row['count'];
}

$pending_reports = 0;
$pending_reports_result = $conn->query("SELECT COUNT(*) as count FROM reports WHERE Status = 'Pending'");
if ($pending_reports_result && $row = $pending_reports_result->fetch_assoc()) {
    $pending_reports = $row['count'];
}

// Handle search
$search_term = "";
$search_results = [];
$show_search_results = false;

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $show_search_results = true;
    $search_term = trim($_GET['search']);
    $search_like = "%" . $conn->real_escape_string($search_term) . "%";
    
    // Search in students, items, and reports
    $search_students = $conn->prepare("SELECT 'student' as type, StudentID as id, FullName as name, Email as detail, IsVerified as status, CreatedAt as date FROM students WHERE FullName LIKE ? OR Email LIKE ? OR MatricNumber LIKE ?");
    $search_students->bind_param("sss", $search_like, $search_like, $search_like);
    $search_students->execute();
    $students_results = $search_students->get_result();
    
    $search_items = $conn->prepare("SELECT 'item' as type, ItemID as id, Title as name, CONCAT('RM ', Price) as detail, Status as status, CreatedAt as date FROM items WHERE Title LIKE ? OR Description LIKE ?");
    $search_items->bind_param("ss", $search_like, $search_like);
    $search_items->execute();
    $items_results = $search_items->get_result();
    
    $search_reports = $conn->prepare("SELECT 'report' as type, ReportID as id, Reason as name, Status as detail, Status as status, CreatedAt as date FROM reports WHERE Reason LIKE ? OR Status LIKE ?");
    $search_reports->bind_param("ss", $search_like, $search_like);
    $search_reports->execute();
    $reports_results = $search_reports->get_result();
    
    // Combine results
    while ($row = $students_results->fetch_assoc()) {
        $search_results[] = $row;
    }
    while ($row = $items_results->fetch_assoc()) {
        $search_results[] = $row;
    }
    while ($row = $reports_results->fetch_assoc()) {
        $search_results[] = $row;
    }
    
    $search_students->close();
    $search_items->close();
    $search_reports->close();
}

// Fetch recent users (last 5)
$recent_users = [];
$recent_users_result = $conn->query("SELECT StudentID, FullName, Email, IsVerified, CreatedAt FROM students ORDER BY CreatedAt DESC LIMIT 5");
if ($recent_users_result) {
    while ($row = $recent_users_result->fetch_assoc()) {
        $recent_users[] = $row;
    }
}

// Fetch recent reports (last 5)
$recent_reports = [];
$recent_reports_result = $conn->query("SELECT r.ReportID, r.Reason, r.Status, r.CreatedAt, i.Title as item_title FROM reports r LEFT JOIN items i ON r.ItemID = i.ItemID ORDER BY r.CreatedAt DESC LIMIT 5");
if ($recent_reports_result) {
    while ($row = $recent_reports_result->fetch_assoc()) {
        $recent_reports[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | Student Marketplace</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========================================
   ADMIN COLOR SCHEME - SLATE / BLUE
   (Only colors changed, structure identical)
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

/* Search */
.search-wrap {
    flex: 1;
    max-width: 520px;
    position: relative;
}

.search-wrap form {
    display: flex;
    gap: 8px;
}

.search-wrap input {
    flex: 1;
    background: rgba(255,255,255,.1);
    border: 1.5px solid rgba(255,255,255,.18);
    border-radius: 999px;
    padding: 0 18px;
    font-family: inherit;
    font-size: 0.875rem;
    color: #fff;
    height: 40px;
    outline: none;
    transition: all 0.2s;
}

.search-wrap input:focus {
    background: rgba(255,255,255,.15);
    border-color: var(--admin-accent);
}

.search-wrap input::placeholder { color: rgba(255,255,255,.45); }

.search-wrap button {
    background: var(--admin-accent);
    color: white;
    border: none;
    padding: 0 20px;
    border-radius: 999px;
    font-family: inherit;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    height: 40px;
    transition: background .2s;
}

.search-wrap button:hover { background: var(--admin-accent-dk); }

.search-wrap .clear-btn {
    background: rgba(255,255,255,.15);
    color: rgba(255,255,255,.7);
    padding: 0 20px;
    border-radius: 999px;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    transition: background .2s;
}

.search-wrap .clear-btn:hover {
    background: rgba(255,255,255,.25);
    color: white;
}

/* Icon row */
.icon-section {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.icon-btn {
    position: relative;
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,.1);
    color: rgba(255,255,255,.75);
    text-decoration: none;
    font-size: 1.15rem;
    transition: all .2s;
}

.icon-btn:hover {
    background: rgba(255,255,255,.2);
    color: #fff;
}

/* ========================================
   NAVIGATION BAR
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

.nav-bar .logout:hover {
    background: var(--admin-danger-lt) !important;
    color: var(--admin-danger) !important;
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
    background: linear-gradient(120deg, var(--admin-primary) 0%, var(--admin-primary-mid) 100%);
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
    background: radial-gradient(circle, rgba(59,130,246,.15) 0%, transparent 70%);
    pointer-events: none;
}

.hero-strip .eyebrow {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--admin-accent);
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

/* Dashboard Cards */
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.card {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    padding: 24px;
    transition: all 0.2s;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
}

.card h4 {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted);
    margin-bottom: 12px;
}

.card p {
    font-size: 2rem;
    font-weight: 800;
    color: var(--admin-primary);
}

/* Section Header */
.section-header {
    display: flex;
    align-items: baseline;
    gap: 10px;
    margin-bottom: 16px;
}

.section-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}

.count-pill {
    background: var(--bg);
    color: var(--muted);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 20px;
    border: 1px solid var(--border);
}

/* Tables */
.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
}

table th {
    background: var(--bg);
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted);
    border-bottom: 1px solid var(--border);
}

table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    font-size: 0.85rem;
}

table tr:last-child td {
    border-bottom: none;
}

/* Status Badges */
.status-active {
    display: inline-block;
    background: var(--admin-success-lt);
    color: var(--admin-success);
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.status-inactive {
    display: inline-block;
    background: var(--admin-danger-lt);
    color: var(--admin-danger);
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.status-pending {
    display: inline-block;
    background: var(--admin-warning-lt);
    color: #92400e;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.status-resolved {
    display: inline-block;
    background: var(--admin-success-lt);
    color: var(--admin-success);
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

/* Badges for search results */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

.badge-student {
    background: #dbeafe;
    color: var(--admin-primary);
}

.badge-item {
    background: #dcfce7;
    color: #166534;
}

.badge-report {
    background: var(--admin-warning-lt);
    color: #92400e;
}

/* Search Summary */
.search-summary {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px 24px;
    margin-bottom: 24px;
}

.search-summary strong {
    color: var(--text);
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    background: var(--surface);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border);
    color: var(--muted);
}

/* Responsive */
@media (max-width: 960px) {
    .top-bar { padding: 0 20px; height: auto; flex-wrap: wrap; padding: 12px 20px; gap: 12px; }
    .search-wrap { max-width: 100%; order: 3; flex-basis: 100%; }
    .hero-strip { padding: 24px; }
    .hero-emoji { display: none; }
    .dashboard-cards { grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .main-container { padding: 0 16px 48px; }
    .nav-bar { padding: 0 16px; flex-wrap: wrap; height: auto; padding: 8px 16px; gap: 8px; }
    .nav-bar .logout { margin-left: 0; }
}

@media (max-width: 560px) {
    .dashboard-cards { grid-template-columns: 1fr; }
    .hero-strip { text-align: center; flex-direction: column; }
    .hero-strip h2 { font-size: 1.3rem; }
}
</style>
</head>
<body>

<!-- TOP BAR -->
<header class="top-bar">
    <a href="admin_dashboard.php" class="logo-section">
        <img src="umpsalgobaru.png" alt="UMPSA">
        <div class="logo-text">
            <div class="brand">Student Marketplace</div>
            <div class="sub">Admin Panel</div>
        </div>
    </a>

    <div class="search-wrap">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search students, items, reports…" value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit">Search</button>
            <?php if ($show_search_results): ?>
                <a href="admin_dashboard.php" class="clear-btn">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="icon-section">
       
    </div>
</header>

<!-- NAVIGATION -->
<nav class="nav-bar">
    <a href="admin_dashboard.php" class="active">Dashboard</a>
    <a href="student_management.php">Student Management</a>
    <a href="item_management.php">Item Management</a>
    <a href="report_management.php">Report Management</a>
    <a href="logout.php" class="logout">Logout</a>
</nav>

<!-- MAIN CONTENT -->
<main class="main-container">

    <!-- Hero Strip -->
    <div class="hero-strip">
        <div>
            <div class="eyebrow">Admin Control Panel</div>
            <h2>Dashboard</h2>
            <p>Overview and monitoring of the Student Marketplace System</p>
        </div>
        <div class="hero-emoji">📊</div>
    </div>

    <?php if ($show_search_results): ?>
        <!-- Search Results View -->
        <div class="search-summary">
            <strong>🔍 Search results for: "<?php echo htmlspecialchars($search_term); ?>"</strong> 
            (<?php echo count($search_results); ?> found)
        </div>
        
        <?php if (count($search_results) > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name / Title</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $result): ?>
                            <tr>
                                <td>
                                    <?php if ($result['type'] == 'student'): ?>
                                        <span class="badge badge-student">Student</span>
                                    <?php elseif ($result['type'] == 'item'): ?>
                                        <span class="badge badge-item">Item</span>
                                    <?php else: ?>
                                        <span class="badge badge-report">Report</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($result['name']); ?></td>
                                <td><?php echo htmlspecialchars($result['detail']); ?></td>
                                <td>
                                    <?php if ($result['type'] == 'student'): ?>
                                        <span class="<?php echo $result['status'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $result['status'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    <?php elseif ($result['type'] == 'item'): ?>
                                        <span class="status-<?php echo strtolower($result['status']); ?>">
                                            <?php echo htmlspecialchars($result['status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-<?php echo strtolower($result['detail']); ?>">
                                            <?php echo htmlspecialchars($result['detail']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($result['date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p>😕 No results found for "<?php echo htmlspecialchars($search_term); ?>"</p>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Normal Dashboard View -->
        
        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <h4>Total Users</h4>
                <p><?php echo number_format($total_users); ?></p>
            </div>
            <div class="card">
                <h4>Total Items</h4>
                <p><?php echo number_format($total_items); ?></p>
            </div>
            <div class="card">
                <h4>Pending Reports</h4>
                <p><?php echo number_format($pending_reports); ?></p>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="section-header">
            <h3>Recent Users</h3>
            <span class="count-pill"><?php echo count($recent_users); ?></span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_users) > 0): ?>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['FullName']); ?></td>
                                <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                <td>
                                    <span class="<?php echo $user['IsVerified'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['IsVerified'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($user['CreatedAt'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <td><td colspan="4" class="no-results" style="text-align:center;">No users found</td</tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Reports -->
        <div class="section-header" style="margin-top: 32px;">
            <h3>Recent Reports</h3>
            <span class="count-pill"><?php echo count($recent_reports); ?></span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Reason</th>
                        <th>Related Item</th>
                        <th>Status</th>
                        <th>Reported On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_reports) > 0): ?>
                        <?php foreach ($recent_reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(substr($report['Reason'], 0, 50)) . (strlen($report['Reason']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars($report['item_title'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($report['Status']); ?>">
                                        <?php echo htmlspecialchars($report['Status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($report['CreatedAt'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="no-results" style="text-align:center;">No reports found</td</tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

</body>
</html>

<?php $conn->close(); ?>