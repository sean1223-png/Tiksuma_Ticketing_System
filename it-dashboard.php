<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_SESSION['username'];

// Fetch profile picture
$user_sql = "SELECT profile_picture FROM users WHERE username = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$profilePicture = $user_data['profile_picture'] ?: 'default-profile.png';
$stmt->close();

// Fetch stats for cards
$stats_sql = "
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN s.status_name = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN s.status_name = 'Resolved' THEN 1 ELSE 0 END) as resolved_count,
        SUM(CASE WHEN s.status_name = 'In Progress' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN s.status_name = 'Archived' THEN 1 ELSE 0 END) as archived_count
    FROM tickets t
    LEFT JOIN ticket_statuses s ON t.status_id = s.id
    WHERE t.assigned_to = ?
";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();

// Fetch daily, weekly, and monthly ticket counts for all tickets
$daily_sql = "
    SELECT COUNT(*) as daily_count
    FROM tickets
    WHERE created_at >= CURDATE()
";
$stmt = $conn->prepare($daily_sql);
$stmt->execute();
$daily_result = $stmt->get_result();
$daily_count = $daily_result->fetch_assoc()['daily_count'];
$stmt->close();

$weekly_sql = "
    SELECT COUNT(*) as weekly_count
    FROM tickets
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
";
$stmt = $conn->prepare($weekly_sql);
$stmt->execute();
$weekly_result = $stmt->get_result();
$weekly_count = $weekly_result->fetch_assoc()['weekly_count'];
$stmt->close();

$monthly_sql = "
    SELECT COUNT(*) as monthly_count
    FROM tickets
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$stmt = $conn->prepare($monthly_sql);
$stmt->execute();
$monthly_result = $stmt->get_result();
$monthly_count = $monthly_result->fetch_assoc()['monthly_count'];
$stmt->close();

// Fetch status distribution for pie chart
$status_chart_sql = "
    SELECT s.status_name, COUNT(*) as count
    FROM tickets t
    JOIN ticket_statuses s ON t.status_id = s.id
    WHERE t.assigned_to = ?
    GROUP BY s.status_name
";
$stmt = $conn->prepare($status_chart_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$status_result = $stmt->get_result();
$status_data = $status_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch priority distribution for bar chart
$priority_chart_sql = "
    SELECT p.level_name, COUNT(*) as count
    FROM tickets t
    JOIN priority_levels p ON t.priority_id = p.id
    WHERE t.assigned_to = ?
    GROUP BY p.level_name
";
$stmt = $conn->prepare($priority_chart_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$priority_result = $stmt->get_result();
$priority_data = $priority_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch tickets over time (last 12 months) for line chart
$time_chart_sql = "
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM tickets
    WHERE assigned_to = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month
";
$stmt = $conn->prepare($time_chart_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$time_result = $stmt->get_result();
$time_data = $time_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch notifications (recent tickets)
$notif_sql = "
    SELECT tickets.ticket_id, ticket_statuses.status_name, tickets.created_at
    FROM tickets
    JOIN ticket_statuses ON tickets.status_id = ticket_statuses.id
    WHERE tickets.assigned_to = ?
    ORDER BY tickets.created_at DESC
    LIMIT 5
";
$stmt = $conn->prepare($notif_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$notif_result = $stmt->get_result();
$notifications = $notif_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent ticket activities (last 10 tickets)
$activity_sql = "
    SELECT t.ticket_id, t.subject, s.status_name, t.created_at, t.updated_at, u.username
    FROM tickets t
    JOIN ticket_statuses s ON t.status_id = s.id
    JOIN users u ON t.username = u.username
    WHERE t.assigned_to = ?
    ORDER BY COALESCE(t.updated_at, t.created_at) DESC
    LIMIT 10
";
$user_sql = "SELECT profile_picture FROM users WHERE username = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
$profilePicture = 'default-profile.png';
if ($user_result && $user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $profilePicture = $user_data['profile_picture'] ?: 'default-profile.png';
}
$stmt->close();

// Count of new tickets for bell badge
$new_ticket_count_sql = "
    SELECT COUNT(*) AS new_count
    FROM tickets
    WHERE assigned_to = ? AND status_id = (SELECT id FROM ticket_statuses WHERE status_name='Pending')
";
$stmt = $conn->prepare($new_ticket_count_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$new_ticket_count = $stmt->get_result()->fetch_assoc()['new_count'];
$stmt->close();

$conn->close();

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'min',
        's' => 'sec',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '') . ' ago';
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) : 'just now';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>TIKSUMA IT Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="icon" type="image/x-icon" href="./png/logo-favicon.ico" />
  <link rel="stylesheet" href="./src/it-staff.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Reuse styles from it-resolved.php */
    .notification-dropdown {
      display: none; /* Initially hidden */
      position: absolute;
      right: 0;
      background-color: var(--dropdown-bg);
      border: 1px solid var(--card-border);
      border-radius: 4px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      width: 300px; /* Set a width for the dropdown */
    }
    .notification-container:hover .notification-dropdown {
      display: block;
    }
    .notif-list {
      list-style: none;
      padding: 10px;
      margin: 0;
    }
    .notif-item {
      padding: 10px;
      border-bottom: 1px solid #f0f0f0;
    }
    .notif-item:last-child {
      border-bottom: none;
    }
    .notif-empty {
      padding: 10px;
      text-align: center;
      color: #999;
    }
    .notif-header{
      margin-left: 10px;
    }
    .notif-title {
      margin-left: 10px;
    }
    .bell-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: red;
      color: white;
      font-size: 11px;
      padding: 2px 5px;
      border-radius: 50%;
    }
    /* Enhanced Sidebar Styles */
     .sidebar {
      position: fixed;
      top: 60px;
      left: 0;
      width: 280px;
      height: calc(100% - 60px);
      background: var(--sidebar-bg);
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      z-index: 1000;
      overflow-y: auto;
      border-right: 1px solid var(--card-border);
    }
    .sidebar.closed {
      width: 0;
      transform: translateX(-100%);
    }
    .sidebar-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px;
      border-bottom: 1px solid var(--card-border);
    }
    .sidebar-logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .sidebar-logo-img {
      width: 30px;
      height: 30px;
      border-radius: 5px;
    }
    .sidebar-logo-text {
      font-size: 18px;
      font-weight: bold;
      color: var(--text-color);
    }
    .sidebar-toggle {
      background: none;
      border: none;
      font-size: 18px;
      color: var(--text-color);
      cursor: pointer;
      padding: 5px;
      border-radius: 3px;
      transition: color 0.3s ease;
    }
    .sidebar-toggle:hover {
      color: var(--text-color);
    }
    .sidebar-user-info {
      display: flex;
      align-items: center;
      padding: 15px 20px;
      border-bottom: 1px solid var(--card-border);
      background: var(--sidebar-bg);
    }
    .sidebar-user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      margin-right: 12px;
      border: 2px solid #065fd4;
    }
    .sidebar-user-details {
      display: flex;
      flex-direction: column;
    }
    .sidebar-user-name {
      font-weight: 600;
      color: var(--text-color);
      font-size: 14px;
    }
    .sidebar-user-role {
      font-size: 12px;
      color: var(--text-color);
    }
    .sidebar-nav {
      padding: 20px 0;
    }
    .sidebar-section {
      margin-bottom: 25px;
    }
    .sidebar-section-title {
      display: block;
      padding: 0 20px;
      font-size: 12px;
      font-weight: 600;
      color: var(--text-color);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 10px;
    }
    .sidebar-menu,
    .sidebar-menu ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .sidebar-item {
      margin-bottom: 2px;
    }
    .sidebar ul li {
      padding: 5px 20px;
      transition: background 0.2s ease;
    }
    .sidebar-link {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: var(--sidebar-text);
      text-decoration: none;
      transition: all 0.3s ease;
      position: relative;
      border-left: 3px solid transparent;
    }
    .sidebar-link:hover {
      background-color: var(--sidebar-bg);
      color: #065fd4;
      border-left-color: #065fd4;
    }
    .sidebar-link.active {
      background-color: var(--sidebar-bg);
      color: #065fd4;
      border-left-color: #065fd4;
      font-weight: 500;
    }
    .sidebar-link i {
      width: 20px;
      margin-right: 12px;
      text-align: center;
    }
    .sidebar-badge {
      margin-left: auto;
      background: #dc3545;
      color: white;
      padding: 2px 6px;
      border-radius: 10px;
      font-size: 11px;
      min-width: 18px;
      text-align: center;
    }
    .sidebar-stat {
      display: flex;
      align-items: center;
      padding: 8px 20px;
      color: var(--text-color);
      font-size: 13px;
    }
    .sidebar-stat i {
      width: 16px;
      margin-right: 10px;
      color: #065fd4;
    }
    .sidebar-stat-value {
      margin-left: auto;
      font-weight: 600;
      color: var(--text-color);
    }
    .sidebar-footer {
      padding: 15px 20px;
      border-top: 1px solid var(--card-border);
      margin-top: auto;
    }
    .sidebar-version {
      text-align: center;
      font-size: 11px;
      color: var(--text-color);
    }
    /* Main content adjustments for sidebar */
    .main-content {
      margin-left: 280px;
      width: calc(100% - 280px);
      transition: all 0.3s ease;
      padding: 50px;
      min-height: calc(100vh - 60px);
      max-height: calc(100vh - 60px);
      overflow-y: auto;
      box-sizing: border-box;
    }
    .main-content.expanded {
      margin-left: 0;
      width: 100%;
    }
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: 100%;
        top: 0;
      }
      .sidebar.closed {
        width: 0;
      }
      .main-content {
        margin-left: 0;
        padding: 10px;
      }
    }
    /* Dashboard Styles */
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    .stat-card h3 {
      margin: 0 0 10px 0;
      color: #2d3e50;
      font-size: 14px;
      text-transform: uppercase;
    }
    .stat-card .value {
      font-size: 36px;
      font-weight: bold;
      color: #065fd4;
    }
    .chart-container {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .chart-container h3 {
      margin: 0 0 20px 0;
      color: #2d3e50;
    }
    .chart-wrapper {
      position: relative;
      height: 300px;
    }
    /* Activity Styles */
    .activity-container {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .activity-container h3 {
      margin: 0 0 20px 0;
      color: #2d3e50;
    }
    .activity-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .activity-item {
      display: flex;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .activity-item:hover {
      background: #f8f9fa;
    }
    .activity-item:last-child {
      border-bottom: none;
    }
    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #065fd4;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-size: 16px;
    }
    .activity-content {
      flex: 1;
    }
    .activity-title {
      font-weight: 600;
      color: #2d3e50;
      margin: 0 0 5px 0;
    }
    .activity-details {
      font-size: 14px;
      color: #6c757d;
      margin: 0;
    }
    .activity-time {
      font-size: 12px;
      color: #999;
      margin-left: auto;
    }
    /* Toggle Dropdown Styles */
    .toggle-section {
      margin-bottom: 20px;
    }
    .toggle-btn {
      background: #065fd4;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background 0.3s ease;
    }
    .toggle-btn:hover {
      background: #0543a8;
    }
    .toggle-btn i {
      transition: transform 0.3s ease;
    }
    .toggle-btn.collapsed i {
      transform: rotate(180deg);
    }
    .collapsible-content {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
      transition: all 0.3s ease;
    }
    .collapsible-content.collapsed {
      display: none;
    }
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: 100%;
        top: 0;
      }
      
      .sidebar.closed {
        width: 0;
      }
      
      .main-content {
        margin-left: 0;
        padding: 10px;
      }
    }

    /* Animation for sidebar items */
    .sidebar-link {
      animation: slideInLeft 0.3s ease forwards;
    }

    @keyframes slideInLeft {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

     @media (max-width: 768px) {
  .sidebar {
    width: 100%;
    height: calc(100% - 60px);
    top: 60px;
  }
  .sidebar.closed {
    width: 0;
  }
  .main-content {
    margin-left: 0;
    padding: 10px;
  }
}
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <button class="menu-toggle" onclick="toggleSidebar()">
      <i class="fas fa-bars"></i>
    </button>
    <div class="logo">TIKSUMA</div>
  </div>

  
  
<div class="topbar-right">
    <div class="notification-container" style="position: relative;">
      <i class="fas fa-bell" id="notificationBell" style="cursor:pointer; position: relative;">
        <?php if ($new_ticket_count > 0): ?>
          <span class="bell-badge" id="bellBadge"><?= $new_ticket_count ?></span>
        <?php endif; ?>
      </i>
      <div class="notification-dropdown" id="notificationDropdown">
        <div class="notif-header"><span>Notification</span></div>
        <div class="notif-title">Recent Tickets*</div>
        <?php if (!empty($notifications)): ?>
          <ul class="notif-list">
            <?php foreach ($notifications as $note): ?>
              <li class="notif-item">
                <div class="notif-user">
                  <strong>Ticket #<?= htmlspecialchars($note['ticket_id']) ?></strong>
                  <small><?= time_elapsed_string($note['created_at']) ?></small>
                </div>
                <div class="notif-content">
                  <span class="badge status-pending"><?= htmlspecialchars($note['status_name']) ?></span>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="notif-empty">No recent tickets.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="profile-dropdown">
      <button id="profileToggle" class="profile-icon-btn">
        <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile" style="width: 30px; height: 30px; border-radius: 50%;" />
      </button>
      <div id="profileMenu" class="dropdown-menu">
        <div class="user-info">
          <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile" class="avatar-icon" style="width: 50px; height: 50px; border-radius: 50%;">
          <span><?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
        <ul>
          <li><a href="view-it-profile.php">View Profile</a></li>
          <li><a href="#" id="logoutBtn">Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Enhanced Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-user-info">
    <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile" class="sidebar-user-avatar">
    <div class="sidebar-user-details">
      <span class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
      <span class="sidebar-user-role">IT Staff</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <ul class="sidebar-menu">
      <!-- Main Menu Section -->
      <li class="sidebar-section">
        <span class="sidebar-section-title">Main Menu</span>
        <ul>
              <li class="sidebar-item">
            <a href="it-dashboard.php" class="sidebar-link active ">
              <i class="fas fa-chart-pie"></i>   
              <span>Dashboard</span>
              <span class="sidebar-badge"><?= $stats['total_tickets'] ?></span>

            </a>
          </li>
          <li class="sidebar-item">
            <a href="it-tickets.php" class="sidebar-link">
              <i class="fas fa-ticket-alt"></i>
              <span>All Tickets</span>
            </a>
          </li>
          
          <li class="sidebar-item">
            <a href="it-assigned-tickets.php" class="sidebar-link">
              <i class="fas fa-tasks"></i>
              <span>Assigned Tickets</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="it-in_progress.php" class="sidebar-link ">
              <i class="fas fa-hourglass-half"></i> 
              <span>In Progress</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="it-resolved.php" class="sidebar-link">
              <i class="fas fa-check-circle"></i>
              <span>Resolved</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="it-pending.php" class="sidebar-link">
              <i class="fas fa-clock"></i>
              <span>Pending</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="it-archive.php" class="sidebar-link">
              <i class="fas fa-archive"></i>
              <span>Archive</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Quick Stats Section -->
      <li class="sidebar-section">
        <span class="sidebar-section-title">Quick Stats</span>
        <ul>
          <li class="sidebar-stat">
            <i class="fas fa-ticket-alt"></i>
            <span>Total Tickets</span>
            <span class="sidebar-stat-value"><?= $stats['total_tickets'] ?></span>
          </li>
          <li class="sidebar-stat">
            <i class="fas fa-clock"></i>
            <span>Pending</span>
            <span class="sidebar-stat-value"><?= $stats['pending_count'] ?></span>
          </li>
          <li class="sidebar-stat">
            <i class="fas fa-spinner"></i>
            <span>In Progress</span>
            <span class="sidebar-stat-value"><?= $stats['in_progress_count'] ?></span>
          </li>
          <li class="sidebar-stat">
            <i class="fas fa-check-circle"></i>
            <span>Resolved</span>
            <span class="sidebar-stat-value"><?= $stats['resolved_count'] ?></span>
          </li>
          <li class="sidebar-stat">
            <i class="fas fa-archive"></i>
            <span>Archived</span>
            <span class="sidebar-stat-value"><?= $stats['archived_count'] ?></span>
          </li>
        </ul>
      </li>

      <!-- Settings Section -->
      <li class="sidebar-section">
        <span class="sidebar-section-title">Settings</span>
        <ul>
          <li class="sidebar-item">
            <a href="it-settings.php" class="sidebar-link">
              <i class="fas fa-cog"></i>
              <span>Settings</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="view-it-profile.php" class="sidebar-link">
              <i class="fas fa-user"></i>
              <span>Profile</span>
            </a>
          </li>
        </ul>
      </li>
    </ul>
  </nav>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer">
    <div class="sidebar-version">
      <span>Version 1.0</span>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <h2>IT Dashboard</h2>
  
  <!-- Stat Cards -->
  <div class="dashboard-grid">
    <div class="stat-card">
      <h3>Total Tickets</h3>
      <div class="value"><?= $stats['total_tickets'] ?></div>
    </div>
    <div class="stat-card">
      <h3>Pending</h3>
      <div class="value"><?= $stats['pending_count'] ?></div>
    </div>
    <div class="stat-card">
      <h3>In Progress</h3>
      <div class="value"><?= $stats['in_progress_count'] ?></div>
    </div>
    <div class="stat-card">
      <h3>Resolved</h3>
      <div class="value"><?= $stats['resolved_count'] ?></div>
    </div>
    <div class="stat-card">
      <h3>Archived</h3>
      <div class="value"><?= $stats['archived_count'] ?></div>
    </div>
  </div>

  <!-- Toggle Section for Time-based Stats -->
  <div class="toggle-section">
    <button class="toggle-btn" id="toggleBtn">
      <i class="fas fa-chevron-down"></i>
      <span>Show Time-based Statistics</span>
    </button>
    <div class="collapsible-content collapsed" id="collapsibleContent">
      <div class="stat-card">
        <h3>Daily Tickets</h3>
        <div class="value"><?= $daily_count ?></div>
      </div>
      <div class="stat-card">
        <h3>Weekly Tickets</h3>
        <div class="value"><?= $weekly_count ?></div>
      </div>
      <div class="stat-card">
        <h3>Monthly Tickets</h3>
        <div class="value"><?= $monthly_count ?></div>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="dashboard-grid">
    <div class="chart-container">
      <h3>Ticket Status Distribution</h3>
      <div class="chart-wrapper">
        <canvas id="statusChart"></canvas>
      </div>
    </div>
    <div class="chart-container">
      <h3>Priority Distribution</h3>
      <div class="chart-wrapper">
        <canvas id="priorityChart"></canvas>
      </div>
    </div>
  </div>

  <div class="chart-container">
    <h3>Tickets Over Time (Last 12 Months)</h3>
    <div class="chart-wrapper">
      <canvas id="timeChart"></canvas>
    </div>
  </div>

  <!-- Recent Ticket Activity -->
  <div class="activity-container">
    <h3>Recent Ticket Activity</h3>
    <?php if (!empty($activities)): ?>
      <ul class="activity-list">
        <?php foreach ($activities as $activity): ?>
          <li class="activity-item" data-ticket-id="<?= htmlspecialchars($activity['ticket_id']) ?>">
            <div class="activity-icon">
              <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="activity-content">
              <div class="activity-title">Ticket #<?= htmlspecialchars($activity['ticket_id']) ?> - <?= htmlspecialchars($activity['subject']) ?></div>
              <div class="activity-details">Status: <?= htmlspecialchars($activity['status_name']) ?> | Created by: <?= htmlspecialchars($activity['username']) ?></div>
            </div>
            <div class="activity-time">
              <?= time_elapsed_string($activity['updated_at'] ?: $activity['created_at']) ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p style="text-align: center; color: #999; padding: 20px;">No recent activities.</p>
    <?php endif; ?>
  </div>
</div>

<!-- SweetAlert & JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Profile dropdown toggle
document.addEventListener('DOMContentLoaded', () => {
  const profileToggle = document.getElementById('profileToggle');
  const profileMenu = document.getElementById('profileMenu');

  if (profileToggle && profileMenu) {
    profileToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      profileMenu.classList.toggle('show');
    });

    window.addEventListener('click', (e) => {
      if (!profileToggle.contains(e.target) && !profileMenu.contains(e.target)) {
        profileMenu.classList.remove('show');
      }
    });
  }

  // Logout with SweetAlert
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', e => {
      e.preventDefault();
      Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to log out?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, logout'
      }).then(result => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Logged Out',
            text: 'You have been successfully logged out.',
            icon: 'success',
            showConfirmButton: false,
            timer: 1500
          }).then(() => {
            window.location.href = 'index.php';
          });
        }
      });
    });
  }

  // Bell icon notification dropdown toggle
  const bellIcon = document.getElementById('notificationBell');
  const dropdown = document.getElementById('notificationDropdown');

  if (bellIcon && dropdown) {
    bellIcon.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('show');
    });

    window.addEventListener('click', function (e) {
      if (!dropdown.contains(e.target) && !bellIcon.contains(e.target)) {
        dropdown.classList.remove('show');
      }
    });
  }

  // Charts
  const statusData = <?= json_encode($status_data) ?>;
  const priorityData = <?= json_encode($priority_data) ?>;
  const timeData = <?= json_encode($time_data) ?>;

  // Status Pie Chart
  const statusCtx = document.getElementById('statusChart').getContext('2d');
  new Chart(statusCtx, {
    type: 'pie',
    data: {
      labels: statusData.map(item => item.status_name),
      datasets: [{
        data: statusData.map(item => item.count),
        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false
    }
  });

  // Priority Bar Chart
  const priorityCtx = document.getElementById('priorityChart').getContext('2d');
  new Chart(priorityCtx, {
    type: 'bar',
    data: {
      labels: priorityData.map(item => item.level_name),
      datasets: [{
        label: 'Tickets',
        data: priorityData.map(item => item.count),
        backgroundColor: '#36A2EB'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

  // Time Line Chart
  const timeCtx = document.getElementById('timeChart').getContext('2d');
  new Chart(timeCtx, {
    type: 'line',
    data: {
      labels: timeData.map(item => item.month),
      datasets: [{
        label: 'Tickets Created',
        data: timeData.map(item => item.count),
        borderColor: '#FF6384',
        backgroundColor: 'rgba(255, 99, 132, 0.2)',
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

  // Toggle button functionality
  const toggleBtn = document.getElementById('toggleBtn');
  const collapsibleContent = document.getElementById('collapsibleContent');

  if (toggleBtn && collapsibleContent) {
    toggleBtn.addEventListener('click', () => {
      const isCollapsed = collapsibleContent.classList.contains('collapsed');
      if (isCollapsed) {
        collapsibleContent.classList.remove('collapsed');
        toggleBtn.classList.remove('collapsed');
        toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i><span>Hide Time-based Statistics</span>';
      } else {
        collapsibleContent.classList.add('collapsed');
        toggleBtn.classList.add('collapsed');
        toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i><span>Show Time-based Statistics</span>';
      }
    });
  }

  // Activity item click functionality
  const activityItems = document.querySelectorAll('.activity-item');
  activityItems.forEach(item => {
    item.addEventListener('click', () => {
      const ticketId = item.dataset.ticketId;
      if (ticketId) {
        window.location.href = `view-ticket.php?ticket_id=${ticketId}`;
      }
    });
  });
});
</script>

<script src="./js/it-staff.js"></script>

</body>
</html>