<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$item_id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

// Fetch existing item
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
    header("Location: my_listings.php");
    exit();
}

$item = $result->fetch_assoc();

$success = "";
$error   = "";

// Fetch categories
$cat_result = $conn->query("SELECT * FROM categories WHERE CategoryID > 1 ORDER BY CategoryName ASC");
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price']       ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $condition   = trim($_POST['condition']   ?? '');
    $campus      = trim($_POST['campus']      ?? '');

    if ($title === '' || $description === '' || $price === '') {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Please enter a valid price.";
    } elseif ($category_id === 0) {
        $error = "Please select a category.";
    } else {
        $upd = $conn->prepare("
            UPDATE items
            SET Title = ?, Description = ?, Price = ?, CategoryID = ?, `Condition` = ?, Campus = ?
            WHERE ItemID = ? AND StudentID = ?
        ");
        $upd->bind_param("ssdissii", $title, $description, $price, $category_id, $condition, $campus, $item_id, $student_id);
        $upd->execute();

        // Handle new image upload
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime    = mime_content_type($_FILES['image']['tmp_name']);

            if (!in_array($mime, $allowed)) {
                $error = "Invalid image type. Please upload JPG, PNG, GIF, or WEBP.";
            } else {
                $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'uploads/' . uniqid('img_', true) . '.' . $ext;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $filename)) {
                    if (!empty($item['ImageID'])) {
                        $img_upd = $conn->prepare("UPDATE item_images SET ImagePath = ? WHERE ImageID = ?");
                        $img_upd->bind_param("si", $filename, $item['ImageID']);
                        $img_upd->execute();
                    } else {
                        $img_ins = $conn->prepare("INSERT INTO item_images (ItemID, ImagePath) VALUES (?, ?)");
                        $img_ins->bind_param("is", $item_id, $filename);
                        $img_ins->execute();
                    }
                    $item['ImagePath'] = $filename;
                } else {
                    $error = "Failed to upload image. Please try again.";
                }
            }
        }

        if ($error === '') {
            $success = "Item updated successfully!";
            $item['Title']       = $title;
            $item['Description'] = $description;
            $item['Price']       = $price;
            $item['CategoryID']  = $category_id;
            $item['Condition']   = $condition;
            $item['Campus']      = $campus;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Listing | Student Marketplace</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========================================
   STUDENT COLOR SCHEME - NAVY / AMBER
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

/* ========================================
   TOP BAR - STUDENT STYLE
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
    border-color: var(--amber);
}

.search-wrap input::placeholder { color: rgba(255,255,255,.45); }

.search-wrap button {
    background: var(--amber);
    color: var(--navy);
    border: none;
    padding: 0 20px;
    border-radius: 999px;
    font-family: inherit;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    height: 40px;
}

.search-wrap button:hover { background: var(--amber-dk); }

.search-results-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
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
.search-result-section h4 { padding: 6px 10px 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--muted); }
.search-result-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 10px;
    text-decoration: none;
    color: var(--text);
    border-radius: var(--radius-sm);
}
.search-result-item:hover { background: var(--bg); }
.search-result-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--navy);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}
.search-result-info .name { font-weight: 700; font-size: 0.875rem; }
.search-result-info .detail { font-size: 0.72rem; color: var(--muted); }

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
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,.1);
    color: rgba(255,255,255,.75);
    text-decoration: none;
    font-size: 1.15rem;
}

.icon-btn:hover {
    background: rgba(255,255,255,.2);
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
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1.5px solid var(--navy);
}

@keyframes bellShake {
    0%,100% { transform: rotate(0); }
    20% { transform: rotate(12deg); }
    40% { transform: rotate(-12deg); }
    60% { transform: rotate(6deg); }
    80% { transform: rotate(-4deg); }
}
.has-notifications { animation: bellShake .5s ease-in-out; }

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
}

.nav-bar a:hover, .nav-bar a.active {
    background: var(--bg);
    color: var(--navy);
}

.nav-bar .logout {
    margin-left: auto;
    color: var(--red) !important;
}

/* ========================================
   MAIN CONTAINER
   ======================================== */
.main-container {
    max-width: 800px;
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

.alert {
    padding: 14px 20px;
    border-radius: var(--radius-md);
    margin-bottom: 20px;
    font-weight: 500;
    font-size: 0.85rem;
}

.alert-success {
    background: var(--green-lt);
    color: var(--green);
    border-left: 4px solid var(--green);
}

.alert-error {
    background: #fee2e2;
    color: var(--red);
    border-left: 4px solid var(--red);
}

.current-image-wrap {
    margin-bottom: 20px;
}

.current-image-wrap label {
    display: block;
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--text);
    margin-bottom: 8px;
}

.current-image-wrap img {
    width: 180px;
    height: 140px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    border: 2px solid var(--border);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--text);
    margin-bottom: 6px;
}

.form-group label span.required {
    color: var(--red);
    margin-left: 2px;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 0.85rem;
    color: var(--text);
    transition: all 0.2s;
    background: white;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--amber);
    box-shadow: 0 0 0 3px rgba(245,158,11,0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.upload-box {
    border: 2px dashed var(--border);
    border-radius: var(--radius-md);
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: var(--bg);
}

.upload-box:hover {
    border-color: var(--amber);
    background: var(--amber-lt);
}

.upload-box input[type="file"] {
    display: none;
}

.upload-box .upload-label {
    display: block;
    cursor: pointer;
    color: var(--muted);
    font-size: 0.8rem;
}

.upload-box .upload-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 6px;
}

#file-name {
    margin-top: 8px;
    font-size: 0.75rem;
    color: var(--amber);
    font-weight: 600;
}

.form-actions {
    display: flex;
    gap: 14px;
    margin-top: 30px;
}

.btn-save {
    padding: 11px 28px;
    background: var(--amber);
    color: var(--navy);
    font-family: inherit;
    font-weight: 700;
    font-size: 0.85rem;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-save:hover {
    background: var(--amber-dk);
    transform: translateY(-2px);
}

.btn-cancel {
    padding: 11px 28px;
    background: transparent;
    color: var(--muted);
    font-family: inherit;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1.5px solid var(--border);
    border-radius: 40px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
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
    .main-container { padding: 0 16px 48px; margin: 20px; }
    .nav-bar { padding: 0 16px; flex-wrap: wrap; height: auto; padding: 8px 16px; }
    .nav-bar .logout { margin-left: 0; }
    .form-row { grid-template-columns: 1fr; gap: 0; }
}

@media (max-width: 560px) {
    .form-actions { flex-direction: column; }
    .btn-save, .btn-cancel { justify-content: center; }
}
</style>
</head>
<body>

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

<nav class="nav-bar">
    <a href="dashboard.php">Home</a>
    <a href="post_item.php">Post Item</a>
    <a href="my_listings.php" class="active">My Listings</a>
    <a href="messages.php">Messages</a>
    <a href="notifications.php">Notifications</a>
    <a href="logout.php" class="logout">Logout</a>
</nav>

<main class="main-container">
    <div class="page-title">
        <h2>✏️ Edit Listing</h2>
        <p>Update the details of your posted item</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($item['ImagePath'])): ?>
    <div class="current-image-wrap">
        <label>Current Image</label>
        <img src="<?php echo htmlspecialchars($item['ImagePath']); ?>" alt="Current listing image">
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-group">
            <label>Title <span class="required">*</span></label>
            <input type="text" name="title" maxlength="150" value="<?php echo htmlspecialchars($item['Title']); ?>" required>
        </div>

        <div class="form-group">
            <label>Description <span class="required">*</span></label>
            <textarea name="description" required><?php echo htmlspecialchars($item['Description']); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Price (RM) <span class="required">*</span></label>
                <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($item['Price']); ?>" required>
            </div>
            <div class="form-group">
                <label>Category <span class="required">*</span></label>
                <select name="category_id" required>
                    <option value="" disabled>Select a category...</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['CategoryID']; ?>" <?php echo $item['CategoryID'] == $cat['CategoryID'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['CategoryName']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Condition</label>
                <select name="condition">
                    <?php foreach (['New', 'Like New', 'Good', 'Fair', 'Poor'] as $cond): ?>
                    <option value="<?php echo $cond; ?>" <?php echo ($item['Condition'] ?? '') === $cond ? 'selected' : ''; ?>><?php echo $cond; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Campus</label>
                <select name="campus">
                    <?php foreach (['Pekan', 'Gambang'] as $camp): ?>
                    <option value="<?php echo $camp; ?>" <?php echo ($item['Campus'] ?? '') === $camp ? 'selected' : ''; ?>><?php echo $camp; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Replace Image <small style="font-weight:400;color:var(--muted);">(optional)</small></label>
            <div class="upload-box" onclick="document.getElementById('image-input').click()">
                <label class="upload-label">
                    <span class="upload-icon">📷</span>
                    Click to upload a new image (JPG, PNG, GIF, WEBP)
                </label>
                <input type="file" id="image-input" name="image" accept="image/*" onchange="document.getElementById('file-name').textContent = this.files[0]?.name || ''">
                <div id="file-name"></div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-save">Save Changes</button>
            <a href="my_listings.php" class="btn-cancel">Cancel</a>
        </div>

    </form>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const searchDropdown = document.getElementById('searchDropdown');
let debounceTimer;

if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        if (query.length < 2) { searchDropdown.classList.remove('show'); return; }

        debounceTimer = setTimeout(() => {
            fetch(`search_suggestions.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.items.length === 0 && data.students.length === 0) {
                        searchDropdown.classList.remove('show');
                        return;
                    }
                    
                    let html = '';
                    if (data.items.length) {
                        html += '<div class="search-result-section"><h4>📦 Items</h4>';
                        data.items.forEach(item => {
                            html += `<a href="item_details.php?id=${item.ItemID}" class="search-result-item">
                                <div class="search-result-avatar">📦</div>
                                <div class="search-result-info">
                                    <div class="name">${escapeHtml(item.Title)}</div>
                                    <div class="detail">RM ${parseFloat(item.Price).toFixed(2)} · ${escapeHtml(item.CategoryName)}</div>
                                </div></a>`;
                        });
                        html += '</div>';
                    }
                    if (data.students.length) {
                        html += '<div class="search-result-section"><h4>👤 Students</h4>';
                        data.students.forEach(student => {
                            html += `<a href="profile.php?id=${student.StudentID}" class="search-result-item">
                                <div class="search-result-avatar">${escapeHtml(student.FullName.charAt(0))}</div>
                                <div class="search-result-info">
                                    <div class="name">${escapeHtml(student.FullName)}</div>
                                    <div class="detail">${escapeHtml(student.Email)}</div>
                                </div></a>`;
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