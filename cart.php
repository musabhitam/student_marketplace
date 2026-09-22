<?php
session_start();
include("connection.php");

// Function to save cart to database
function saveCartToDatabase($conn, $student_id) {
    if ($student_id > 0) {
        // Delete existing cart items for this user
        $conn->query("DELETE FROM saved_carts WHERE StudentID = $student_id");
        
        // Save current cart items
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item_id => $item) {
                $quantity = $item['quantity'];
                $conn->query("INSERT INTO saved_carts (StudentID, ItemID, Quantity) VALUES ($student_id, $item_id, $quantity)");
            }
        }
    }
}

/* ── CART ACTIONS (all handled here) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action  = $_POST['action'];
    $item_id = intval($_POST['item_id'] ?? 0);

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if ($action === 'add' && $item_id > 0) {
        // Verify item exists
        $stmt = $conn->prepare("SELECT ItemID, Title, Price FROM items WHERE ItemID = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            if (isset($_SESSION['cart'][$item_id])) {
                $_SESSION['cart'][$item_id]['quantity']++;
            } else {
                $_SESSION['cart'][$item_id] = [
                    'item_id'  => $row['ItemID'],
                    'title'    => $row['Title'],
                    'price'    => $row['Price'],
                    'quantity' => 1,
                ];
            }
            // Save to database
            if (isset($_SESSION['student_id'])) {
                saveCartToDatabase($conn, $_SESSION['student_id']);
            }
            $redirect = $_POST['redirect'] ?? 'dashboard.php';
            header("Location: " . $redirect . "?added=1");
            exit;
        }

    } elseif ($action === 'increase' && $item_id > 0) {
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity']++;
            if (isset($_SESSION['student_id'])) {
                saveCartToDatabase($conn, $_SESSION['student_id']);
            }
        }
        header("Location: cart.php");
        exit;

    } elseif ($action === 'decrease' && $item_id > 0) {
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity']--;
            if ($_SESSION['cart'][$item_id]['quantity'] <= 0) {
                unset($_SESSION['cart'][$item_id]);
            }
            if (isset($_SESSION['student_id'])) {
                saveCartToDatabase($conn, $_SESSION['student_id']);
            }
        }
        header("Location: cart.php");
        exit;

    } elseif ($action === 'remove' && $item_id > 0) {
        unset($_SESSION['cart'][$item_id]);
        if (isset($_SESSION['student_id'])) {
            saveCartToDatabase($conn, $_SESSION['student_id']);
        }
        header("Location: cart.php?removed=1");
        exit;
    }
}

/* ── LOAD CART DATA ── */
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$enriched = [];

if (!empty($cart)) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $sql = "
        SELECT items.ItemID, items.Title, items.Price, items.Condition, items.Campus,
               (SELECT ImagePath FROM item_images WHERE item_images.ItemID = items.ItemID LIMIT 1) AS ImagePath
        FROM items WHERE items.ItemID IN ($ids)
    ";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $id = $row['ItemID'];
        $enriched[$id] = array_merge($cart[$id], $row);
    }
}

$total = 0;
foreach ($enriched as $item) {
    $total += $item['Price'] * $item['quantity'];
}

$cart_count = array_sum(array_column($cart, 'quantity'));

// Get unread notification count for badge
$unread_count = 0;
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    $unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE StudentID = $student_id AND IsRead = 0";
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
<title>My Cart | Student Marketplace</title>

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
    top: calc(100% + 8px);
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
    gap: 6px;
    margin-left: auto;
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

/* Toast */
.toast {
    background: var(--green-lt);
    border: 1px solid #6ee7b7;
    color: #065f46;
    padding: 12px 20px;
    border-radius: var(--radius-sm);
    margin-bottom: 20px;
    font-weight: 600;
    text-align: center;
}

/* Cart Container */
.cart-container {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    overflow: hidden;
}

.cart-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--bg);
}

.cart-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}

/* Cart Table */
.cart-table {
    width: 100%;
    border-collapse: collapse;
}

.cart-table th {
    text-align: left;
    padding: 16px 20px;
    background: var(--bg);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted);
    border-bottom: 1px solid var(--border);
}

.cart-table td {
    padding: 20px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.cart-table tr:last-child td {
    border-bottom: none;
}

/* Product Image */
.item-thumb {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    box-shadow: var(--shadow-sm);
}

/* Item Info */
.item-info strong {
    display: block;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
}

.item-info small {
    font-size: 0.7rem;
    color: var(--muted);
}

/* Price */
.item-price {
    font-weight: 700;
    color: var(--navy);
    font-size: 0.9rem;
}

/* Quantity Controls */
.qty-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: 1px solid var(--border);
    background: var(--surface);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    color: var(--navy);
    transition: all 0.2s;
}

.qty-btn:hover {
    background: var(--amber);
    color: var(--navy);
    border-color: var(--amber);
}

.qty-number {
    min-width: 30px;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Subtotal */
.item-subtotal {
    font-weight: 700;
    color: var(--text);
    font-size: 0.9rem;
}

/* Remove Button */
.btn-remove {
    background: none;
    border: none;
    color: var(--muted);
    cursor: pointer;
    font-size: 1rem;
    padding: 8px;
    border-radius: var(--radius-sm);
    transition: all 0.2s;
}

.btn-remove:hover {
    background: #fee2e2;
    color: var(--red);
}

/* Cart Summary */
.cart-summary {
    background: var(--bg);
    border-top: 1px solid var(--border);
    padding: 24px 30px;
    display: flex;
    justify-content: flex-end;
}

.summary-box {
    width: 320px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 0.85rem;
    color: var(--muted);
}

.summary-row.total {
    font-weight: 800;
    font-size: 1rem;
    border-top: 1px solid var(--border);
    padding-top: 12px;
    margin-top: 8px;
    color: var(--navy);
}

/* Continue Button */
.btn-continue {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: var(--amber);
    color: var(--navy);
    font-weight: 700;
    font-size: 0.85rem;
    border-radius: 40px;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-continue:hover {
    background: var(--amber-dk);
    transform: translateY(-2px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--surface);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border);
}

.empty-state .icon {
    font-size: 3rem;
    margin-bottom: 16px;
}

.empty-state h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 0.85rem;
    color: var(--muted);
}

.empty-state a {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 24px;
    background: var(--amber);
    color: var(--navy);
    text-decoration: none;
    border-radius: 40px;
    font-weight: 700;
    font-size: 0.85rem;
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
    .cart-table th, .cart-table td { padding: 12px; }
    .item-thumb { width: 60px; height: 60px; }
    .cart-summary { padding: 20px; }
    .summary-box { width: 100%; }
}

@media (max-width: 560px) {
    .hero-strip { text-align: center; flex-direction: column; }
    .hero-strip h2 { font-size: 1.3rem; }
    .cart-table thead { display: none; }
    .cart-table tr { display: block; margin-bottom: 20px; border: 1px solid var(--border); border-radius: var(--radius-md); }
    .cart-table td { display: block; text-align: right; padding: 12px; border-bottom: 1px solid var(--border); }
    .cart-table td:before { content: attr(data-label); float: left; font-weight: 600; color: var(--muted); }
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
            <h2>My Cart</h2>
            <p>Review and manage items before checkout</p>
        </div>
        <div class="hero-emoji">🛒</div>
    </div>

    <?php if (isset($_GET['removed'])): ?>
    <div class="toast">✓ Item removed from cart.</div>
    <?php endif; ?>

    <?php if (empty($enriched)): ?>
    <div class="empty-state">
        <div class="icon">🛍️</div>
        <h3>Your cart is empty</h3>
        <p>Looks like you haven't added any items yet</p>
        <a href="dashboard.php">Browse Marketplace</a>
    </div>
    <?php else: ?>

    <div class="cart-container">
        <div class="cart-header">
            <h3>Shopping Cart</h3>
        </div>
        
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($enriched as $item): ?>
            <tr>
                <td data-label="Item" style="display: flex; align-items: center; gap: 15px;">
                    <a href="item_details.php?id=<?php echo $item['ItemID']; ?>">
                        <img class="item-thumb"
                             src="<?php echo $item['ImagePath'] ? htmlspecialchars($item['ImagePath']) : 'images/default.png'; ?>">
                    </a>
                    <div class="item-info">
                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        <small>Condition: <?php echo htmlspecialchars($item['Condition']); ?> | Campus: <?php echo htmlspecialchars($item['Campus']); ?></small>
                    </div>
                </td>
                <td data-label="Price" class="item-price">RM <?php echo number_format($item['Price'], 2); ?></td>
                <td data-label="Quantity">
                    <div class="qty-controls">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="decrease">
                            <input type="hidden" name="item_id" value="<?php echo $item['ItemID']; ?>">
                            <button class="qty-btn" type="submit">−</button>
                        </form>
                        <span class="qty-number"><?php echo $item['quantity']; ?></span>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="increase">
                            <input type="hidden" name="item_id" value="<?php echo $item['ItemID']; ?>">
                            <button class="qty-btn" type="submit">+</button>
                        </form>
                    </div>
                </td>
                <td data-label="Subtotal" class="item-subtotal">RM <?php echo number_format($item['Price'] * $item['quantity'], 2); ?></td>
                <td data-label="Remove">
                    <form method="post" onsubmit="return confirm('Remove this item from cart?')">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="item_id" value="<?php echo $item['ItemID']; ?>">
                        <button class="btn-remove" type="submit">✕ Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="summary-box">
                <div class="summary-row">
                    <span>Subtotal (<?php echo $cart_count; ?> items)</span>
                    <span>RM <?php echo number_format($total, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>RM <?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 24px;">
        <a href="dashboard.php" class="btn-continue">← Continue Shopping</a>
    </div>

    <?php endif; ?>
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