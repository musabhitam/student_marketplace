<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

include("connection.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Management</title>

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
    max-width: 1400px;
    margin: 28px auto;
    padding: 0 24px 60px;
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
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

.alert-success {
    background: var(--admin-success-lt);
    color: var(--admin-success);
    padding: 14px 20px;
    border-radius: var(--radius-md);
    margin-bottom: 20px;
    border-left: 4px solid var(--admin-success);
}

.alert-error {
    background: var(--admin-danger-lt);
    color: var(--admin-danger);
    padding: 14px 20px;
    border-radius: var(--radius-md);
    margin-bottom: 20px;
    border-left: 4px solid var(--admin-danger);
}

.stats-summary {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.stat-badge {
    padding: 8px 18px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.8rem;
}

.filter-bar {
    margin-bottom: 24px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-bar input {
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: inherit;
    flex: 1;
    min-width: 200px;
    font-size: 0.85rem;
}

.filter-bar select {
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: inherit;
    background: white;
    font-size: 0.85rem;
}

.filter-bar button {
    background: var(--admin-accent);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: var(--radius-sm);
    font-weight: 600;
    cursor: pointer;
}

.filter-bar .clear-btn {
    background: var(--muted);
    color: white;
    padding: 10px 20px;
    border-radius: var(--radius-sm);
    text-decoration: none;
    font-weight: 600;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface);
    border-radius: var(--radius-lg);
    overflow: hidden;
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
    vertical-align: middle;
}

table tr:last-child td {
    border-bottom: none;
}

.report-reason {
    max-width: 300px;
    word-wrap: break-word;
}

/* Status Badges */
.status-pending {
    display: inline-block;
    background: var(--admin-warning-lt);
    color: #92400e;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.status-resolved {
    display: inline-block;
    background: var(--admin-success-lt);
    color: var(--admin-success);
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.status-dismissed {
    display: inline-block;
    background: var(--admin-danger-lt);
    color: var(--admin-danger);
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    text-decoration: none;
    padding: 6px 14px;
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 0.75rem;
    display: inline-block;
    transition: all 0.2s;
}

.approve-btn {
    background: var(--admin-success);
    color: white;
    border: none;
}

.approve-btn:hover {
    background: #059669;
}

.dismiss-btn {
    background: var(--admin-danger);
    color: white;
    border: none;
}

.dismiss-btn:hover {
    background: #b91c1c;
}

.no-actions {
    color: var(--muted);
    font-size: 0.7rem;
    font-style: italic;
}

.no-column {
    width: 60px;
    text-align: center;
    font-weight: 600;
    color: var(--admin-primary);
}

.empty-state {
    text-align: center;
    padding: 60px;
    color: var(--muted);
    font-size: 0.85rem;
}

@media (max-width: 960px) {
    .top-bar { padding: 0 20px; height: auto; flex-wrap: wrap; padding: 12px 20px; gap: 12px; }
    .search-wrap { max-width: 100%; order: 3; flex-basis: 100%; }
    .main-container { padding: 0 16px 48px; margin: 20px; }
    .nav-bar { padding: 0 16px; flex-wrap: wrap; height: auto; padding: 8px 16px; }
    .nav-bar .logout { margin-left: 0; }
    .stats-summary { justify-content: center; }
    .filter-bar form { flex-direction: column; }
    .filter-bar input, .filter-bar select, .filter-bar button { width: 100%; }
}

@media (max-width: 560px) {
    .table-wrapper { overflow-x: auto; }
    table { min-width: 900px; }
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

    <div class="search-wrap">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by reporter or reported student…" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Search</button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="report_management.php" class="clear-btn">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="icon-section">
    </div>
</header>

<nav class="nav-bar">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="student_management.php">Student Management</a>
    <a href="item_management.php">Item Management</a>
    <a href="report_management.php" class="active">Report Management</a>
    <a href="logout.php" class="logout">Logout</a>
</nav>

<main class="main-container">
    <div class="page-title">
        <h2>Report Management</h2>
        <p>Review and manage all reports submitted by students</p>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert-success">✅ <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert-error">❌ <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
    <?php endif; ?>

    <?php
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    
    $where_conditions = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(r.FullName LIKE '%$search%' OR t.FullName LIKE '%$search%')";
    }
    
    if ($status_filter == 'pending') {
        $where_conditions[] = "reports.Status = 'Pending'";
    } elseif ($status_filter == 'resolved') {
        $where_conditions[] = "reports.Status = 'Resolved'";
    } elseif ($status_filter == 'dismissed') {
        $where_conditions[] = "reports.Status = 'Dismissed'";
    }
    
    $where_sql = "";
    if (count($where_conditions) > 0) {
        $where_sql = "WHERE " . implode(" AND ", $where_conditions);
    }
    
    $total_reports = $conn->query("SELECT COUNT(*) as count FROM reports")->fetch_assoc()['count'];
    $pending_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE Status = 'Pending'")->fetch_assoc()['count'];
    $resolved_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE Status = 'Resolved'")->fetch_assoc()['count'];
    $dismissed_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE Status = 'Dismissed'")->fetch_assoc()['count'];
    
    $sql = "
        SELECT reports.*, 
               r.FullName AS ReporterName,
               t.FullName AS ReportedName,
               items.Title AS ItemTitle
        FROM reports
        JOIN students r ON reports.ReporterStudentID = r.StudentID
        JOIN students t ON reports.ReportedStudentID = t.StudentID
        LEFT JOIN items ON reports.ItemID = items.ItemID
        $where_sql
        ORDER BY 
            CASE WHEN reports.Status = 'Pending' THEN 1 ELSE 2 END,
            reports.CreatedAt DESC
    ";
    
    $result = $conn->query($sql);
    ?>

    <div class="stats-summary">
        <div class="stat-badge" style="background:var(--admin-accent-lt); color:var(--admin-primary);">📋 Total Reports: <?php echo $total_reports; ?></div>
        <div class="stat-badge" style="background:var(--admin-warning-lt); color:#92400e;">⏳ Pending: <?php echo $pending_reports; ?></div>
        <div class="stat-badge" style="background:var(--admin-success-lt); color:var(--admin-success);">✅ Resolved: <?php echo $resolved_reports; ?></div>
        <div class="stat-badge" style="background:var(--admin-danger-lt); color:var(--admin-danger);">❌ Dismissed: <?php echo $dismissed_reports; ?></div>
    </div>

    <div class="filter-bar">
        <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%;">
            <input type="text" name="search" placeholder="Search by reporter or reported student..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending Only</option>
                <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved Only</option>
                <option value="dismissed" <?php echo $status_filter == 'dismissed' ? 'selected' : ''; ?>>Dismissed Only</option>
            </select>
            <button type="submit">Filter</button>
            <?php if (!empty($search) || !empty($status_filter)): ?>
                <a href="report_management.php" class="clear-btn">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th class="no-column">No.</th>
                    <th>Reporter</th>
                    <th>Reported Student</th>
                    <th>Related Item</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $counter = 1; while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="no-column" style="text-align:center;"><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($row['ReporterName']); ?></td>
                            <td><?php echo htmlspecialchars($row['ReportedName']); ?></td>
                            <td>
                                <?php if ($row['ItemTitle']): ?>
                                    <?php echo htmlspecialchars($row['ItemTitle']); ?>
                                    <br><small style="color:var(--muted); font-size:0.7rem;">Item ID: <?php echo $row['ItemID']; ?></small>
                                <?php else: ?>
                                    <span style="color:var(--muted);">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="report-reason"><?php echo nl2br(htmlspecialchars(substr($row['Reason'], 0, 80))) . (strlen($row['Reason']) > 80 ? '...' : ''); ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($row['CreatedAt'])); ?></td>
                            <td>
                                <?php if ($row['Status'] == 'Resolved'): ?>
                                    <span class="status-resolved">✅ Resolved</span>
                                <?php elseif ($row['Status'] == 'Dismissed'): ?>
                                    <span class="status-dismissed">❌ Dismissed</span>
                                <?php else: ?>
                                    <span class="status-pending">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <?php if ($row['Status'] == 'Pending'): ?>
                                    <a class="action-btn approve-btn" href="approve_report.php?id=<?php echo $row['ReportID']; ?>" onclick="return confirm('Approve this report? Action will be taken.')">✅ Approve</a>
                                    <a class="action-btn dismiss-btn" href="dismiss_report.php?id=<?php echo $row['ReportID']; ?>" onclick="return confirm('Dismiss this report? No action will be taken.')">❌ Dismiss</a>
                                <?php else: ?>
                                    <span class="no-actions">— No actions —</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-state">No reports found.<?php if (!empty($search)): ?><br>Try a different search term.<?php endif; ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>

<?php $conn->close(); ?>