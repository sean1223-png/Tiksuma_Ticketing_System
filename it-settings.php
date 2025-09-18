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
$profilePicture = $user_result && $user_result->num_rows > 0 ? $user_result->fetch_assoc()['profile_picture'] : 'default-profile.png';
$stmt->close();

$conn->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulate saving settings
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
    $language = $_POST['language'] ?? 'en';
    $export_data = isset($_POST['export_data']);

    // For now, just display a success message. In a real app, save to database.
    echo "<script>Swal.fire('Settings Saved', 'Your settings have been updated successfully.', 'success');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Settings - TIKSUMA</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="icon" type="image/x-icon" href="./png/logo-favicon.ico" />
  <link rel="stylesheet" href="./src/it-staff.css" />
   <style>
    .priority-badge {
      padding: 4px 8px;
      border-radius: 12px;
      color: white;
      font-size: 0.8em;
      text-transform: uppercase;
    }
    .priority-low { background: green; }
    .priority-medium { background: orange; }
    .priority-high { background: red; }

    .status-badge {
      padding: 4px 8px;
      border-radius: 12px;
      background: #3498db;
      color: white;
      font-size: 0.8em;
      text-transform: uppercase;
    }

    .view-btn {
      padding: 5px 10px;
      background-color: #3498db;
      color: white;
      text-decoration: none;
      font-size: 14px;
      border-radius: 4px;
    }
    .view-btn:hover {
      background-color: #2980b9;
    }
    .desc { font-size: 12px; color: #777; }

    /* Notification Dropdown Styles */
    .notification-dropdown {
      display: none; /* Initially hidden */
      position: absolute;
      right: 0;
      background-color: white;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      width: 300px; /* Set a width for the dropdown */
    }

    .notification-container:hover .notification-dropdown {
      display: block; /* Show on hover */
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
      border-bottom: none; /* Remove border for the last item */
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
     @media print {
    body * {
      visibility: hidden;
    }

    .main-content, .main-content * {
      visibility: visible;
    }

    .main-content {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
    }

    .print-btn {
      display: none !important;
    }
  }
  .print-btn{
  height: 36px;
  background-color: #065fd4;
  color: #fff;
  padding: 0 20px;
  border: none;
  border-radius: 20px;
  font-size: 14px;
  cursor: pointer;
  font-weight: 500;
}

.print:hover {
  background-color: #0b4abf;
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
    .switch {
      position: relative;
      display: inline-block;
      width: 40px;
      height: 20px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 14px;
      width: 14px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .4s;
    }

    input:checked + .slider {
      background-color: #065fd4;
    }

    input:checked + .slider:before {
      transform: translateX(20px);
    }

    .slider.round {
      border-radius: 20px;
    }

    .slider.round:before {
      border-radius: 50%;
    }

    /* Main content adjustments for sidebar */
    .main-content {
      margin-left: 280px;
      width: calc(100% - 280px);
      transition: all 0.3s ease;
      padding: 50px;
      min-height: calc(100vh - 60px);
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

    .sidebar-item:nth-child(1) .sidebar-link { animation-delay: 0.1s; }
    .sidebar-item:nth-child(2) .sidebar-link { animation-delay: 0.2s; }
    .sidebar-item:nth-child(3) .sidebar-link { animation-delay: 0.3s; }

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

    /* Settings specific styles */
    .settings-section {
      margin-bottom: 30px;
      padding: 20px;
      background: var(--card-bg);
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .settings-section h3 {
      margin-bottom: 15px;
      color: var(--text-color);
      font-size: 18px;
    }
    .setting-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding: 10px 0;
      border-bottom: 1px solid var(--card-border);
    }
    .setting-item:last-child {
      border-bottom: none;
    }
    .setting-item span, .setting-item label {
      font-size: 14px;
      color: var(--text-color);
    }
    .setting-item select {
      padding: 5px 10px;
      border: 1px solid var(--card-border);
      border-radius: 4px;
      background: var(--sidebar-bg);
      color: var(--text-color);
    }
    .settings-actions {
      text-align: center;
      margin-top: 30px;
    }
    .settings-actions .view-btn {
      padding: 10px 20px;
      font-size: 16px;
    }
    .settings-wrapper {
      max-height: calc(100vh - 150px);
      overflow-y: auto;
      padding-right: 10px;
    }
    .settings-wrapper::-webkit-scrollbar {
      width: 8px;
    }
    .settings-wrapper::-webkit-scrollbar-track {
      background: var(--sidebar-bg);
      border-radius: 4px;
    }
    .settings-wrapper::-webkit-scrollbar-thumb {
      background: var(--card-border);
      border-radius: 4px;
    }
    .settings-wrapper::-webkit-scrollbar-thumb:hover {
      background: #065fd4;
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

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-user-info">
    <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile" class="sidebar-user-avatar" />
    <div class="sidebar-user-details">
      <span class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
      <span class="sidebar-user-role">IT Staff</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <ul class="sidebar-menu">
      <li class="sidebar-section">
        <span class="sidebar-section-title">Main Menu</span>
        <ul>
          <li class="sidebar-item">
            <a href="it-dashboard.php" class="sidebar-link">
              <i class="fas fa-chart-pie"></i>
              <span>Dashboard</span>
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
            <a href="it-in_progress.php" class="sidebar-link">
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

      <li class="sidebar-section">
        <span class="sidebar-section-title">Settings</span>
        <ul>
          <li class="sidebar-item">
            <a href="it-settings.php" class="sidebar-link active">
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

  <div class="sidebar-footer">
    <div class="sidebar-version">
      <span>Version 1.0</span>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <h2>Settings</h2>
  <div class="settings-wrapper">
    <form method="POST" action="">
    <section class="settings-section">
      <h3>Theme Mode</h3>
      <div class="theme-toggle">
        <span>Toggle Dark Mode</span>
        <label class="switch">
          <input type="checkbox" id="themeToggle" name="dark_mode">
          <span class="slider round"></span>
        </label>
      </div>
    </section>

    <section class="settings-section">
      <h3>Notification Preferences</h3>
      <div class="setting-item">
        <span>Enable Email Notifications</span>
        <label class="switch">
          <input type="checkbox" name="email_notifications" checked>
          <span class="slider round"></span>
        </label>
      </div>
    </section>

    <section class="settings-section">
      <h3>Security Settings</h3>
      <div class="setting-item">
        <a href="#" class="view-btn">Change Password</a>
      </div>
      <div class="setting-item">
        <span>Enable Two-Factor Authentication</span>
        <label class="switch">
          <input type="checkbox" name="two_factor_auth">
          <span class="slider round"></span>
        </label>
      </div>
    </section>

    <section class="settings-section">
      <h3>General Preferences</h3>
      <div class="setting-item">
        <label for="language">Language:</label>
        <select name="language" id="language">
          <option value="en">English</option>
          <option value="es">Spanish</option>
          <option value="fr">French</option>
        </select>
      </div>
    </section>

    <section class="settings-section">
      <h3>Data Management</h3>
      <div class="setting-item">
        <button type="submit" name="export_data" class="view-btn">Export My Data</button>
      </div>
    </section>

    <div class="settings-actions">
      <button type="submit" class="view-btn">Save Settings</button>
    </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./js/it-staff.js"></script>
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
  });
</script>

</body>
</html>
