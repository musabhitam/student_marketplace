<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Example session data
$admin_name = $_SESSION['admin_name'] ?? "Admin User";
$admin_email = $_SESSION['admin_email'] ?? "admin@email.com";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Profile</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>

:root {
    --primary: #1e40af;
    --card-bg: rgba(255,255,255,0.96);
    --header-bg: #e5e7eb;
    --text-main: #1f2937;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
    color: var(--text-main);
}

/* Background */
body::before {
    content: "";
    position: fixed;
    inset: 0;
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

.logo-section {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo-section h1 {
    margin: 0;
    font-size: 0.9rem;
    text-transform: uppercase;
}

.search-section input {
    width: 300px;
    padding: 10px 14px;
    border: 1px solid #9ca3af;
    border-radius: 6px;
}

/* NAVIGATION */
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
}

.nav-bar a:hover {
    color: var(--primary);
}

/* MAIN CONTAINER */
.main-container {
    max-width: 900px;
    margin: 50px auto;
    background: var(--card-bg);
    padding: 50px;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

/* PAGE TITLE */
.page-title h2 {
    margin: 0;
    font-size: 2rem;
}

.page-title p {
    color: gray;
    margin-top: 8px;
    margin-bottom: 35px;
}

/* PROFILE CARD */
.profile-card {
    background: white;
    border-radius: 14px;
    padding: 40px;
    box-shadow: 0 5px 18px rgba(0,0,0,0.08);
    text-align: center;
}

/* PROFILE DETAILS */
.profile-card h3 {
    margin: 0 0 10px;
    font-size: 1.8rem;
}

.profile-card p {
    color: #6b7280;
    margin: 5px 0;
}

/* INFO GRID */
.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 35px;
}

.info-box {
    background: #f9fafb;
    padding: 20px;
    border-radius: 12px;
}

.info-box h4 {
    margin: 0 0 8px;
    color: #6b7280;
    font-size: 0.95rem;
}

.info-box p {
    margin: 0;
    font-weight: 800;
    color: var(--primary);
}

/* RESPONSIVE */
@media (max-width: 900px) {

    .top-bar {
        flex-direction: column;
        gap: 15px;
    }

    .search-section input {
        width: 100%;
    }

    .nav-bar {
        flex-wrap: wrap;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

}

</style>
</head>

<body>

<!-- TOP BAR -->
<div class="top-bar">

    <div class="logo-section">
        <img src="umpsalogos.png" width="35">
        <h1>Student Marketplace<br>Admin Panel</h1>
    </div>

    <div class="search-section">
        <input type="text" placeholder="🔍 Search...">
    </div>

</div>

<!-- NAVIGATION -->
<div class="nav-bar">

    <a href="admin_dashboard.php">Dashboard</a>
    <a href="student_management.php">Students</a>
    <a href="item_management.php">Items</a>
    <a href="report_management.php">Reports</a>
    <a href="admin_profile.php">My Profile</a>

    <a href="logout.php" style="margin-left:auto;color:red;">
        Logout
    </a>

</div>

<!-- MAIN CONTENT -->
<div class="main-container">

    <div class="page-title">
        <h2>Admin Profile</h2>
        <p>Your administrator account information and access details.</p>
    </div>

    <div class="profile-card">

        <h3>
            <?php echo $admin_name; ?>
        </h3>

        <p>
            <?php echo $admin_email; ?>
        </p>

        <!-- INFO GRID -->
        <div class="info-grid">

            <div class="info-box">
                <h4>Role</h4>
                <p>Administrator</p>
            </div>

            <div class="info-box">
                <h4>Status</h4>
                <p>Active</p>
            </div>

            <div class="info-box">
                <h4>Permissions</h4>
                <p>Full Access</p>
            </div>

        </div>

    </div>

</div>

</body>
</html>