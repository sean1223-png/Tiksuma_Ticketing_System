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

// Fetch profile picture from database
$user_sql = "SELECT profile_picture FROM users WHERE username = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$profilePicture = $user_data['profile_picture'] ?: 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="#ccc"/><circle cx="50" cy="35" r="15" fill="#999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80" fill="#999"/></svg>');
$stmt->close();

// Fetch user's tickets with additional fields for template
$sql = "
    SELECT t.ticket_id, t.subject, t.created_at,
           p.level_name AS priority, 
           s.status_name AS status,
           t.description
    FROM tickets t
    JOIN priority_levels p ON t.priority_id = p.id
    JOIN ticket_statuses s ON t.status_id = s.id
    WHERE t.username = ?
    ORDER BY t.ticket_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$tickets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch 5 most recent ticket updates
$notif_sql = "
    SELECT tickets.ticket_id, ticket_statuses.status_name, tickets.created_at 
    FROM tickets
    JOIN ticket_statuses ON tickets.status_id = ticket_statuses.id 
    WHERE tickets.username = ?
    ORDER BY tickets.created_at DESC 
    LIMIT 5
";
$stmt = $conn->prepare($notif_sql);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch sidebar statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN s.status_name = 'Open' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN s.status_name = 'Resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM tickets t
    LEFT JOIN ticket_statuses s ON t.status_id = s.id
    WHERE t.username = ?
";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>My Tickets - TIKSUMA Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="./src/clarendon-staff.css" />
  <link rel="icon" type="image/x-icon" href="./png/logo-favicon.ico" />
  <style>
    /* Enhanced styles for the new table template */
    .table-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      overflow: hidden;
      margin: 20px 0;
    }

    .table-controls {
      padding: 15px 20px;
      background: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
    }

    .table-search {
      padding: 15px 20px;
      border-bottom: 1px solid #dee2e6;
    }

    .search-input {
      width: 100%;
      max-width: 300px;
      padding: 8px 12px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-size: 14px;
    }

    .print-btn {
      background: #007bff;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }

    .print-btn:hover {
      background: #0056b3;
    }

    .ticket-table {
      width: 100%;
      border-collapse: collapse;
      margin: 0;
    }

    .ticket-table th {
      background: #f8f9fa;
      padding: 15px 12px;
      text-align: left;
      font-weight: 600;
      color: #495057;
      border-bottom: 2px solid #dee2e6;
      font-size: 14px;
    }

    .ticket-table td {
      padding: 12px;
      border-bottom: 1px solid #dee2e6;
      vertical-align: middle;
    }

    .ticket-table tbody tr:hover {
      background-color: #f8f9fa;
      cursor: pointer;
    }

    .priority-badge {
      padding: 4px 8px;
      border-radius: 12px;
      color: white;
      font-size: 0.85em;
      font-weight: 600;
      text-transform: uppercase;
    }

    .priority-high { background-color: #dc3545; }
    .priority-medium { background-color: #fd7e14; }
    .priority-low { background-color: #28a745; }

    .status-badge {
      padding: 4px 8px;
      border-radius: 12px;
      color: white;
      font-size: 0.85em;
      font-weight: 600;
    }

    .status-open { background-color: #007bff; }
    .status-in-progress { background-color: #ffc107; color: #212529; }
    .status-resolved { background-color: #28a745; }
    .status-closed { background-color: #6c757d; }
    .status-pending { background-color: #077E8C; }

    .ticket-id {
      font-weight: 600;
      color: #007bff;
    }

    .ticket-subject {
      font-weight: 500;
      color: #495057;
    }

    .ticket-date {
      color: #6c757d;
      font-size: 0.9em;
    }

    .action-buttons {
      display: flex;
      gap: 5px;
    }

    .action-buttons a,
    .action-buttons button {
      padding: 6px 10px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      font-size: 12px;
      transition: all 0.2s;
    }

    .btn-view {
      background: #007bff;
      color: white;
    }

    .btn-edit {
      background: #ffc107;
      color: #212529;
    }

    .btn-cancel {
      background: #dc3545;
      color: white;
    }

    .btn-view:hover,
    .btn-edit:hover,
    .btn-cancel:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .no-data {
      text-align: center;
      padding: 40px 20px;
      color: #6c757d;
    }

    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
    }

    .empty-state i {
      font-size: 48px;
      color: #dee2e6;
    }

    /* Enhanced Sidebar Styles */
    .sidebar {
    position: fixed;
    top: 60px;
    left: 0;
    width: 280px;
    height: calc(100% - 60px);
    background: linear-gradient(135deg, #ffffff 0%, #dce2e8ff 100%);
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    z-index: 1000;
    overflow-y: auto;
    border-right: 1px solid #e9ecef;
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
      border-bottom: 1px solid #e9ecef;
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
      color: #2d3e50;
    }

    .sidebar-toggle {
      background: none;
      border: none;
      font-size: 18px;
      color: #6c757d;
      cursor: pointer;
      padding: 5px;
      border-radius: 3px;
      transition: color 0.3s ease;
    }

    .sidebar-toggle:hover {
      color: #2d3e50;
    }

    .sidebar-user-info {
      display: flex;
      align-items: center;
      padding: 15px 20px;
      border-bottom: 1px solid #e9ecef;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
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
      color: #2d3e50;
      font-size: 14px;
    }

    .sidebar-user-role {
      font-size: 12px;
      color: #6c757d;
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
      color: #6c757d;
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

    .sidebar-link {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: #495057;
      text-decoration: none;
      transition: all 0.3s ease;
      position: relative;
      border-left: 3px solid transparent;
    }

    .sidebar-link:hover {
      background-color: #f8f9fa;
      color: #065fd4;
      border-left-color: #065fd4;
    }

    .sidebar-link.active {
      background-color: #e3f2fd;
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
      color: #6c757d;
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
      color: #2d3e50;
    }

    .sidebar-footer {
      padding: 15px 20px;
      border-top: 1px solid #eee;
      margin-top: auto;
    }

    .sidebar-version {
      text-align: center;
      font-size: 11px;
      color: #999;
    }
    .sidebar-theme-toggle {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
      font-size: 13px;
      color: #6c757d;
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
      padding: 20px;
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
    .notif-header {
    display: flex
    justify-content: space-between;
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 5px;
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
    <form method="POST" action="add-ticket.php" class="add-ticket">
      <button class="add-ticket" onclick="()">+ Add Ticket</button>
    </form>
    <div class="notification-container">
      <i class="fas fa-bell" id="notificationBell"></i>
      <div class="notification-dropdown" id="notificationDropdown">
                <div class="notif-header"><span>Notification</span></div>

        <?php if (!empty($notifications)): ?>
          <ul class="notif-list">
            <?php foreach ($notifications as $note): ?>
              <li>
                <strong>Ticket #<?= $note['ticket_id'] ?></strong> was <span style="color:green"><?= $note['status_name'] ?></span><br>
                <small><?= date('M d, Y h:i A', strtotime($note['created_at'])) ?></small>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p style="padding: 10px;">No new ticket updates.</p>
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
          <li><a href="view-profile.php">View Profile</a></li>
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
      <span class="sidebar-user-role">User</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <ul class="sidebar-menu">
      <!-- Dashboard Section -->
      <li class="sidebar-section">
        <span class="sidebar-section-title">Main Menu</span>
        <ul>
          <li class="sidebar-item">
            <a href="clarc-my-ticket.php" class="sidebar-link active">
              <i class="fas fa-ticket-alt"></i>
              <span>My Tickets</span>
              <span class="sidebar-badge" id="ticketCount"><?= count($tickets) ?></span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="clarc-action.php" class="sidebar-link">
              <i class="fas fa-bolt"></i>
              <span>Quick Actions</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="clarc-feedback.php" class="sidebar-link">
              <i class="fas fa-comment-dots"></i>
              <span>Send Feedback</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Quick Stats Section -->
      <li class="sidebar-section">
        <span class="sidebar-section-title">Quick Stats</span>
        <ul>
          <li class="sidebar-stat">
            <i class="fas fa-clock"></i>
            <span>Pending Tickets</span>
            <span class="sidebar-stat-value" id="pendingCount">
              <?= array_reduce($tickets, function($carry, $ticket) {
                return $carry + ($ticket['status'] === 'Open' ? 1 : 0);
              }, 0) ?>
            </span>
          </li>
          <li class="sidebar-stat">
            <i class="fas fa-check-circle"></i>
            <span>Resolved</span>
            <span class="sidebar-stat-value" id="resolvedCount">
              <?= array_reduce($tickets, function($carry, $ticket) {
                return $carry + ($ticket['status'] === 'Resolved' ? 1 : 0);
              }, 0) ?>
            </span>
          </li>
          <li class="sidebar-stat">
            <i class="fas fa-star"></i>
            <span>Total Tickets</span>
            <span class="sidebar-stat-value" id="totalCount"><?= count($tickets) ?></span>
          </li>
        </ul>
      </li>

      <!-- Quick Links Section -->
      <li class="sidebar-section">
        <span class="sidebar-section-title">Quick Links</span>
        <ul>
          <li class="sidebar-item">
            <a href="view-profile.php" class="sidebar-link">
              <i class="fas fa-user"></i>
              <span>Profile</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="#" class="sidebar-link" onclick="showHelp()">
              <i class="fas fa-question-circle"></i>
              <span>Help & Support</span>
            </a>
          </li>
        </ul>
      </li>
    </ul>
  </nav>

  <!-- Sidebar Footer -->

    <div class="sidebar-version">
      <span>Version 0.1</span>
    </div>
  </div>
</div>

<!-- Logo Icon for Closed Sidebar -->
<div class="logo-icon" id="logoIcon" onclick="openSidebarFromLogo()" title="Open Sidebar"></div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
  <div class="table-container">
    <div class="table-controls">
      <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Print
      </button>
    </div>
    
    <div class="table-search">
      <input type="text" id="tableSearch" placeholder="Search tickets..." class="search-input">
    </div>

    <table class="ticket-table" id="ticketsTable">
      <thead>
        <tr>
          <th>Ticket ID</th>
          <th>Subject</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($tickets): ?>
          <?php foreach ($tickets as $ticket): 
            $pri = strtolower($ticket['priority']);
            $status = strtolower(str_replace(' ', '-', $ticket['status']));
          ?>
            <tr data-id="<?= htmlspecialchars($ticket['ticket_id']) ?>">
              <td>
                <span class="ticket-id">#<?= htmlspecialchars($ticket['ticket_id']) ?></span>
              </td>
              <td>
                <div class="ticket-subject">
                  <?= htmlspecialchars($ticket['subject']) ?>
                </div>
              </td>
              <td>
                <span class="priority-badge priority-<?= $pri ?>">
                  <?= htmlspecialchars($ticket['priority']) ?>
                </span>
              </td>
              <td>
                <span class="status-badge status-<?= $status ?>">
                  <?= htmlspecialchars($ticket['status']) ?>
                </span>
              </td>
              <td>
                <span class="ticket-date">
                  <?= date('M d, Y', strtotime($ticket['created_at'])) ?>
                </span>
              </td>
              <td class="ticket-actions">
                <div class="action-buttons">
                  <a href="clarc-view-ticket.php?id=<?= urlencode($ticket['ticket_id']) ?>" 
                     class="btn-view" title="View Ticket">
                    <i class="fas fa-eye"></i>
                  </a>
                  <button onclick="cancelTicket(<?= htmlspecialchars($ticket['ticket_id']) ?>)" 
                          class="btn-cancel" title="Cancel Ticket">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="no-data">
              <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No tickets found.</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./src/clarendon-staff.js"></script>
<script>
// Search functionality
function initializeTableSearch() {
    const searchInput = document.getElementById('tableSearch');
    const table = document.getElementById('ticketsTable');
    
    if (!searchInput || !table) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// Cancel ticket confirmation
function cancelTicket(ticketId) {
    Swal.fire({
        title: 'Cancel Ticket?',
        text: 'Are you sure you want to cancel this ticket?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, cancel it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `cancel-ticket.php?id=${ticketId}`;
        }
    });
}

// Help function
function showHelp() {
    Swal.fire({
        title: 'Help & Support',
        html: `
            <div style="text-align: left;">
                <h4>Quick Guide:</h4>
                <ul>
                    <li><strong>My Tickets:</strong> View all your submitted tickets</li>
                    <li><strong>Quick Actions:</strong> Fast access to common tasks</li>
                    <li><strong>Send Feedback:</strong> Share your experience with us</li>
                </ul>
                <p>For more help, contact support@tiksuma.com</p>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Got it!'
    });
}

// Enhanced sidebar toggle functionality
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar && mainContent) {
        sidebar.classList.toggle('closed');
        mainContent.classList.toggle('expanded');
        
        // Save state to localStorage
        const isClosed = sidebar.classList.contains('closed');
        localStorage.setItem('sidebarClosed', isClosed);
    }
}

// Initialize sidebar state
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar && mainContent) {
        const savedState = localStorage.getItem('sidebarClosed');
        if (savedState === 'true') {
            sidebar.classList.add('closed');
            mainContent.classList.add('expanded');
            
            
        }
    }
}

// Theme toggle functionality
const themeToggle = document.getElementById('themeToggle');
if (themeToggle) {
    themeToggle.addEventListener('change', function() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    });

    // Load saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        themeToggle.checked = true;
        document.body.classList.add('dark-mode');
    }
}

// Logout functionality
const logoutLink = document.getElementById('logoutLink');
if (logoutLink) {
    logoutLink.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Logout?',
            text: 'Are you sure you want to log out?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, logout'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php';
            }
        });
    });
}

  // Logout dropdown toggle
  const pt = document.getElementById('profileToggle'), pm = document.getElementById('profileMenu');
  pt.addEventListener('click', e => { e.stopPropagation(); pm.classList.toggle('show'); });
  window.addEventListener('click', e => { if (!pt.contains(e.target) && !pm.contains(e.target)) pm.classList.remove('show'); });
  document.getElementById('logoutBtn').addEventListener('click', e => {
    e.preventDefault();
    Swal.fire({
      title:'Logout?', text:'Confirm logout.', icon:'warning',
      showCancelButton:true, confirmButtonColor:'#d33',
      cancelButtonColor:'#3085d6', confirmButtonText:'Logout'
    }).then(res => { if (res.isConfirmed) window.location.href='index.php'; });
  });

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


// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeTableSearch();
    
    // Add click handlers for rows
    const rows = document.querySelectorAll('#ticketsTable tbody tr[data-id]');
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.ticket-actions')) return;
            
            const ticketId = this.getAttribute('data-id');
            window.location.href = `clarc-view-ticket.php?id=${ticketId}`;
        });
    });
});
</script>

</body>
</html>
