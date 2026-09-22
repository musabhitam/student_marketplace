<?php
session_start();
include("connection.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$myID = $_SESSION['student_id'];

// Cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c) {
        $cart_count += $c['quantity'];
    }
}

// Unread notification count
$unread_count = 0;
$unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE StudentID = $myID AND IsRead = 0";
$unread_result = $conn->query($unread_sql);
if ($unread_result && $unread_result->num_rows > 0) {
    $unread_count = $unread_result->fetch_assoc()['count'];
}

/* AUTO USER FROM ITEM PAGE */
$autoUser = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages | Student Marketplace</title>

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
    padding: 0 24px;
}

/* Messages Container */
.messages-container {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    overflow: hidden;
    display: flex;
    height: 640px;
}

/* Sidebar */
.sidebar {
    width: 320px;
    flex-shrink: 0;
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    background: var(--surface);
}

.sidebar-header {
    padding: 20px 20px 16px;
    border-bottom: 1px solid var(--border);
    background: var(--bg);
}

.sidebar-header h2 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}

.sidebar-header p {
    font-size: 0.7rem;
    color: var(--muted);
    margin-top: 2px;
}

.user-search {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
}

.user-search input {
    width: 100%;
    padding: 8px 14px;
    border: 1px solid var(--border);
    border-radius: 40px;
    font-family: inherit;
    font-size: 0.8rem;
    outline: none;
    transition: all 0.2s;
}

.user-search input:focus {
    border-color: var(--amber);
    box-shadow: 0 0 0 2px rgba(245,158,11,0.1);
}

.user-list {
    overflow-y: auto;
    flex: 1;
}

.user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid var(--bg);
    transition: background 0.15s;
}

.user-item:hover {
    background: var(--bg);
}

.user-item.active {
    background: var(--amber-lt);
    border-left: 3px solid var(--amber);
}

.user-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--navy);
    color: white;
    font-weight: 700;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-info .user-name {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--text);
    cursor: pointer;
    transition: color 0.2s;
}

.user-info .user-name:hover {
    color: var(--amber);
    text-decoration: underline;
}

.user-info .user-sub {
    font-size: 0.7rem;
    color: var(--muted);
    cursor: pointer;
    margin-top: 2px;
}

.user-info .user-sub:hover {
    color: var(--amber);
}

/* Chat Area */
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: var(--surface);
}

.chat-header {
    padding: 16px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 14px;
    background: var(--bg);
}

.chat-header .chat-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--navy);
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: opacity 0.2s;
}

.chat-header .chat-avatar:hover {
    opacity: 0.8;
}

.chat-header-info {
    flex: 1;
}

.chat-header-info .chat-name {
    font-weight: 800;
    font-size: 1rem;
    color: var(--text);
    cursor: pointer;
    transition: color 0.2s;
}

.chat-header-info .chat-name:hover {
    color: var(--amber);
    text-decoration: underline;
}

.chat-header-info .chat-status {
    font-size: 0.7rem;
    color: var(--green);
    font-weight: 500;
    margin-top: 2px;
}

/* Chat Empty */
.chat-empty {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted);
    gap: 10px;
    flex-direction: column;
}

.chat-empty .empty-icon {
    font-size: 3rem;
    opacity: 0.5;
}

.chat-empty p {
    font-size: 0.85rem;
}

/* Chat Messages */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.chat-messages .msg-row {
    display: flex;
    gap: 8px;
}

.chat-messages .msg-row.me {
    flex-direction: row-reverse;
}

.chat-messages .bubble {
    max-width: 65%;
    padding: 10px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    line-height: 1.45;
    word-break: break-word;
}

.chat-messages .bubble.them {
    background: var(--bg);
    color: var(--text);
    border-bottom-left-radius: 4px;
}

.chat-messages .bubble.me {
    background: var(--amber);
    color: var(--navy);
    border-bottom-right-radius: 4px;
}

.chat-messages .msg-time {
    font-size: 0.65rem;
    color: var(--muted);
    align-self: flex-end;
    margin: 0 8px;
}

/* Chat Input Bar */
.chat-input-bar {
    padding: 16px 24px;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 12px;
    background: var(--surface);
}

.chat-input-bar input {
    flex: 1;
    padding: 12px 18px;
    border: 1px solid var(--border);
    border-radius: 40px;
    font-family: inherit;
    font-size: 0.85rem;
    outline: none;
    transition: all 0.2s;
}

.chat-input-bar input:focus {
    border-color: var(--amber);
    box-shadow: 0 0 0 2px rgba(245,158,11,0.1);
}

.chat-input-bar button {
    padding: 0 24px;
    background: var(--amber);
    color: var(--navy);
    border: none;
    border-radius: 40px;
    font-weight: 700;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
}

.chat-input-bar button:hover {
    background: var(--amber-dk);
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 960px) {
    .top-bar { padding: 0 20px; height: auto; flex-wrap: wrap; padding: 12px 20px; gap: 12px; }
    .search-wrap { max-width: 100%; order: 3; flex-basis: 100%; }
    .nav-bar { padding: 0 16px; flex-wrap: wrap; height: auto; padding: 8px 16px; gap: 8px; }
    .nav-bar .logout { margin-left: 0; }
    .main-container { padding: 0 16px; }
    .messages-container { flex-direction: column; height: auto; }
    .sidebar { width: 100%; max-height: 300px; border-right: none; border-bottom: 1px solid var(--border); }
}

@media (max-width: 560px) {
    .chat-messages .bubble { max-width: 85%; }
    .chat-input-bar { padding: 12px 16px; }
    .chat-input-bar button { padding: 0 16px; }
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
    <a href="messages.php" class="active">Messages</a>
    <a href="logout.php" class="logout">Logout</a>
</nav>

<!-- MAIN CONTENT -->
<main class="main-container">
    <div class="messages-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>💬 Messages</h2>
                <p>Select a user to start chatting</p>
            </div>
            <div class="user-search">
                <input type="text" id="userSearch" placeholder="🔍 Search users..." oninput="filterUsers()">
            </div>
            <div class="user-list" id="userList">
                <?php
                $users = mysqli_query($conn, "SELECT * FROM students WHERE StudentID != $myID ORDER BY FullName ASC");
                while ($u = mysqli_fetch_assoc($users)):
                    $initial = strtoupper(mb_substr($u['FullName'], 0, 1));
                ?>
                <div class="user-item" id="user-<?php echo $u['StudentID']; ?>" data-name="<?php echo htmlspecialchars(strtolower($u['FullName'])); ?>" data-id="<?php echo $u['StudentID']; ?>">
                    <div class="user-avatar"><?php echo $initial; ?></div>
                    <div class="user-info">
                        <div class="user-name" onclick="event.stopPropagation(); goToProfile(<?php echo $u['StudentID']; ?>)"><?php echo htmlspecialchars($u['FullName']); ?></div>
                        <div class="user-sub" onclick="event.stopPropagation(); openChat(<?php echo $u['StudentID']; ?>, '<?php echo htmlspecialchars(addslashes($u['FullName'])); ?>')">💬 Tap to chat</div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <div class="chat-empty" id="chatEmpty">
                <div class="empty-icon">💬</div>
                <p>Select a user to start a conversation</p>
            </div>
            <div class="chat-header" id="chatHeader" style="display:none;">
                <div class="chat-avatar" id="chatAvatar" onclick="goToProfileFromChat()"></div>
                <div class="chat-header-info">
                    <div class="chat-name" id="chatName" onclick="goToProfileFromChat()"></div>
                    <div class="chat-status">Active now</div>
                </div>
            </div>
            <div class="chat-messages" id="chatBox" style="display:none;"></div>
            <div class="chat-input-bar" id="chatInputBar" style="display:none;">
                <input type="text" id="messageInput" placeholder="Type a message..." autocomplete="off">
                <button id="sendBtn">Send ➤</button>
            </div>
        </div>
    </div>
</main>

<script>
let currentChat = 0;
let currentUserId = 0;
let currentUserName = "";

function goToProfile(userId) {
    window.location.href = "profile.php?id=" + userId;
}

function goToProfileFromChat() {
    if (currentUserId) {
        window.location.href = "profile.php?id=" + currentUserId;
    }
}

function openChat(userID, userName) {
    currentUserId = userID;
    currentUserName = userName;

    // Highlight active user
    document.querySelectorAll('.user-item').forEach(el => el.classList.remove('active'));
    const userEl = document.getElementById('user-' + userID);
    if (userEl) userEl.classList.add('active');

    // Show chat UI
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('chatHeader').style.display = 'flex';
    document.getElementById('chatBox').style.display = 'flex';
    document.getElementById('chatInputBar').style.display = 'flex';

    // Set header with clickable elements
    document.getElementById('chatName').textContent = userName;
    document.getElementById('chatAvatar').textContent = userName.charAt(0).toUpperCase();

    // Get or create chat
    fetch("get_chat.php?user=" + userID)
        .then(res => res.text())
        .then(chatID => {
            currentChat = chatID.trim();
            loadMessages();
        });
}

function loadMessages() {
    if (!currentChat) return;

    fetch("load_messages.php?chat_id=" + currentChat)
        .then(res => res.text())
        .then(data => {
            const box = document.getElementById("chatBox");
            box.innerHTML = data;
            box.scrollTop = box.scrollHeight;
        });
}

// Send on button click
document.getElementById('sendBtn').addEventListener('click', sendMessage);

// Send on Enter key
document.getElementById('messageInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') sendMessage();
});

function sendMessage() {
    const input = document.getElementById("messageInput");
    const msg = input.value.trim();
    if (!msg || !currentChat) return;

    fetch("send_message.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: "chat_id=" + currentChat + "&message=" + encodeURIComponent(msg)
    }).then(() => {
        input.value = "";
        loadMessages();
    });
}

// Filter sidebar users
function filterUsers() {
    const q = document.getElementById('userSearch').value.toLowerCase();
    document.querySelectorAll('.user-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

// Auto-refresh messages every 3 seconds
setInterval(loadMessages, 3000);

// Auto-open chat if coming from item page
const autoUser = <?php echo $autoUser; ?>;
if (autoUser !== 0) {
    const el = document.getElementById('user-' + autoUser);
    if (el) {
        const name = el.querySelector('.user-name').textContent;
        openChat(autoUser, name);
    }
}

// Search dropdown functionality
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