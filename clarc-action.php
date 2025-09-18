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

// ✅ Fetch profile picture from database
$user_sql = "SELECT profile_picture FROM users WHERE username = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$profilePicture = $user_data['profile_picture'] ?: 'default-profile.png';
$stmt->close();


// Fetch user's tickets with enhanced details
$sql = "
    SELECT t.ticket_id, t.subject, t.created_at,
           p.level_name AS priority, 
           s.status_name AS status
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
if (!$stmt) die("Prepare failed: " . $conn->error);
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
  <title>TIKSUMA Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="./src/clarendon-staff.css" />
  <link rel="icon" type= "image/x-icon" href="./png/logo-favicon.ico" />

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


    /* Export buttons */
    .export-btn {
      background: #6c757d;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.2s ease;
    }

    .export-btn:hover {
      background: #5a6268;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    /* Clear filters button */
    .clear-btn {
      background: #dc3545;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.2s ease;
    }

    .clear-btn:hover {
      background: #c82333;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    /* Pagination buttons */
    .page-btn {
      background: #f8f9fa;
      color: #495057;
      border: 1px solid #dee2e6;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.2s ease;
    }

    .page-btn:hover:not(:disabled) {
      background: #e9ecef;
      border-color: #adb5bd;
    }

    .page-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* Feedback button in action buttons */
    .btn-feedback {
      background: #17a2b8;
      color: white;
      border: none;
      padding: 6px 10px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      transition: all 0.2s;
    }

    .btn-feedback:hover {
      background: #138496;
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
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
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
    margin-top:;
    width: calc(100% - 280px);
    transition: all 0.3s ease;
    padding: 60px;
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

    .notif-header {
    display: flex
;
    justify-content: space-between;
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 5px;
}

    /* ===== Responsive adjustments ===== */
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

  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left"><button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button><div class="logo">TIKSUMA</div></div>
  
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
            <a href="clarc-my-ticket.php" class="sidebar-link">
              <i class="fas fa-ticket-alt"></i>
              <span>My Tickets</span>
              <span class="sidebar-badge" id="ticketCount"><?= count($tickets) ?></span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="clarc-action.php" class="sidebar-link active">
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
              <?= $stats['pending_count'] ?? 0 ?>
            </span>
          </li>
          <li class="sidebar-stat">
            <i class="fas fa-check-circle"></i>
            <span>Resolved</span>
            <span class="sidebar-stat-value" id="resolvedCount">
              <?= $stats['resolved_count'] ?? 0 ?>
            </span>
          </li>
          <li class="sidebar-stat">
            <i class="fas fa-star"></i>
            <span>Total Tickets</span>
            <span class="sidebar-stat-value" id="totalCount"><?= $stats['total_tickets'] ?? 0 ?></span>
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
  <div class="sidebar-footer">
    <div class="sidebar-version">
      <span>Version 0.1</span>
    </div>
  </div>
</div>

<!-- Logo Icon for Closed Sidebar -->
<div class="logo-icon" id="logoIcon" onclick="openSidebarFromLogo()" title="Open Sidebar"></div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Enhanced Table Container -->
    <div class="table-container">
        
        <!-- Table Controls -->
        <div class="table-controls">
            <div class="left-controls">
                <button class="print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="export-btn" onclick="exportTable('csv')">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button class="export-btn" onclick="exportTable('pdf')">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
            </div>
            
            <div class="right-controls">
                <select id="filterPriority" class="filter-select">
                    <option value="">All Priorities</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
                
                <select id="filterStatus" class="filter-select">
                    <option value="">All Status</option>
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Pending">Pending</option>
                    <option value="Resolved">Resolved</option>
                </select>
                
                <button id="clearFilters" class="clear-btn">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>


        <!-- Search Box -->
        <div class="table-search">
            <input type="text" id="tableSearch" placeholder="Search tickets..." class="search-input">
            <div class="search-filters">
                <label>
                    <input type="checkbox" id="searchSubject" checked> Subject
                </label>
                <label>
                    <input type="checkbox" id="searchID"> Ticket ID
                </label>
                <label>
                    <input type="checkbox" id="searchDate"> Date
                </label>
            </div>
        </div>

        <!-- Enhanced Table -->
        <table class="ticket-table enhanced-table" id="ticketsTable">
            <thead>
                <tr>
                    <th class="sortable" data-sort="ticket_id">
                        Ticket ID <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="subject">
                        Subject <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="priority">
                        Priority <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="status">
                        Status <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="created_at">
                        Created <i class="fas fa-sort"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tickets): ?>
                    <?php foreach ($tickets as $index => $ticket): 
                        $pri = strtolower($ticket['priority']);
                        $status = strtolower(str_replace(' ', '-', $ticket['status']));
                        $created_at = $ticket['created_at'] ?? date('Y-m-d H:i:s');
                    ?>
                        <tr data-id="<?= htmlspecialchars($ticket['ticket_id']) ?>" 
                            data-priority="<?= htmlspecialchars($ticket['priority']) ?>"
                            data-status="<?= htmlspecialchars($ticket['status']) ?>"
                            data-subject="<?= htmlspecialchars($ticket['subject']) ?>">
                            
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
                                    <?= date('M d, Y', strtotime($created_at)) ?>
                                </span>
                            </td>
                            
                            <td class="ticket-actions">
                                <div class="action-buttons">
                                    <a href="clarc-view-ticket.php?id=<?= urlencode($ticket['ticket_id']) ?>" 
                                       class="btn-view" title="View Ticket">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <button onclick="quickAction(<?= htmlspecialchars($ticket['ticket_id']) ?>, 'edit')" 
                                            class="btn-edit" title="Quick Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <button onclick="quickAction(<?= htmlspecialchars($ticket['ticket_id']) ?>, 'cancel')" 
                                            class="btn-cancel" title="Cancel Ticket">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    
                                    <button onclick="quickAction(<?= htmlspecialchars($ticket['ticket_id']) ?>, 'feedback')" 
                                            class="btn-feedback" title="Add Feedback">
                                        <i class="fas fa-comment"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="no-data">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No tickets found.</p>
                                <a href="add-ticket.php" class="btn-add-ticket">
                                    <i class="fas fa-plus"></i> Create New Ticket
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="table-pagination" id="pagination">
            <div class="pagination-info">
                Showing <span id="showingFrom">1</span> to <span id="showingTo">10</span> of <span id="totalRecords"><?= count($tickets) ?></span> entries
            </div>
            <div class="pagination-buttons">
                <button id="prevPage" class="page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                <span id="pageNumbers"></span>
                <button id="nextPage" class="page-btn"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./src/clarendon-staff.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tickets = <?= json_encode($tickets) ?>;
  if (tickets.length === 0) {
    Swal.fire({
      title: 'No Tickets Yet',
      text: 'You haven’t submitted any tickets yet!',
      icon: 'info',
      timer: 3000,
      showConfirmButton: false,
      timerProgressBar: true
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

  // Search functionality
  const searchInput = document.getElementById('tableSearch');
  const table = document.getElementById('ticketsTable');
  
  if (searchInput && table) {
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const rows = table.querySelectorAll('tbody tr[data-id]');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  }

  // Filter functionality
  const filterPriority = document.getElementById('filterPriority');
  const filterStatus = document.getElementById('filterStatus');
  const clearFilters = document.getElementById('clearFilters');

  function applyFilters() {
    const priorityValue = filterPriority.value.toLowerCase();
    const statusValue = filterStatus.value.toLowerCase();
    const rows = table.querySelectorAll('tbody tr[data-id]');
    
    rows.forEach(row => {
      const priority = row.getAttribute('data-priority').toLowerCase();
      const status = row.getAttribute('data-status').toLowerCase();
      
      const showPriority = !priorityValue || priority === priorityValue;
      const showStatus = !statusValue || status === statusValue;
      
      row.style.display = (showPriority && showStatus) ? '' : 'none';
    });
  }

  if (filterPriority) {
    filterPriority.addEventListener('change', applyFilters);
  }
  
  if (filterStatus) {
    filterStatus.addEventListener('change', applyFilters);
  }
  
  if (clearFilters) {
    clearFilters.addEventListener('click', () => {
      if (filterPriority) filterPriority.value = '';
      if (filterStatus) filterStatus.value = '';
      applyFilters();
    });
  }

  // Quick action function
  window.quickAction = function(ticketId, action) {
    switch(action) {
      case 'edit':
        Swal.fire({
          title: 'Edit Ticket?',
          text: 'Are you sure you want to edit this ticket?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#007bff',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, edit it!'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = `clarc-view-ticket.php?id=${ticketId}&action=edit`;
          }
        });
        break;
      case 'cancel':
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
        break;
      case 'feedback':
        Swal.fire({
          title: 'Add Feedback?',
          text: 'Are you sure you want to add feedback to this ticket?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#28a745',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, add feedback!'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = `clarc-feedback.php?ticket_id=${ticketId}`;
          }
        });
        break;
      default:
        console.log('Unknown action:', action);
    }
  };

  // Export functionality
  window.exportTable = function(format) {
    const table = document.getElementById('ticketsTable');
    const rows = table.querySelectorAll('tbody tr[data-id]');
    
    if (rows.length === 0) {
      Swal.fire('No Data', 'No tickets to export', 'info');
      return;
    }

    let csvContent = "Ticket ID,Subject,Priority,Status,Created\n";
    
    rows.forEach(row => {
      if (row.style.display !== 'none') {
        const ticketId = row.querySelector('.ticket-id').textContent.replace('#', '');
        const subject = row.querySelector('.ticket-subject').textContent.trim();
        const priority = row.querySelector('.priority-badge').textContent.trim();
        const status = row.querySelector('.status-badge').textContent.trim();
        const created = row.querySelector('.ticket-date').textContent.trim();
        
        csvContent += `"${ticketId}","${subject}","${priority}","${status}","${created}"\n`;
      }
    });

    if (format === 'csv') {
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'tickets.csv';
      link.click();
    } else if (format === 'pdf') {
      Swal.fire('PDF Export', 'PDF export functionality would be implemented with a library like jsPDF', 'info');
    }
  };


  // Clickable rows
  document.querySelectorAll('#ticketsTable tbody tr[data-id]').forEach(row => {
    row.addEventListener('click', function(e) {
      // Don't trigger if clicking on action buttons or checkboxes
      if (e.target.closest('.ticket-actions') || e.target.type === 'checkbox') return;
      
      const ticketId = this.getAttribute('data-id');
      window.location.href = `clarc-view-ticket.php?id=${ticketId}`;
    });
  });

  // Pagination (basic implementation)
  const totalRecords = <?= count($tickets) ?>;
  const recordsPerPage = 10;
  let currentPage = 1;
  
  function updatePagination() {
    const totalPages = Math.ceil(totalRecords / recordsPerPage);
    const showingFrom = Math.min((currentPage - 1) * recordsPerPage + 1, totalRecords);
    const showingTo = Math.min(currentPage * recordsPerPage, totalRecords);
    
    document.getElementById('showingFrom').textContent = showingFrom;
    document.getElementById('showingTo').textContent = showingTo;
    document.getElementById('totalRecords').textContent = totalRecords;
    
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    
    if (prevBtn) prevBtn.disabled = currentPage === 1;
    if (nextBtn) nextBtn.disabled = currentPage === totalPages;
  }

  const prevBtn = document.getElementById('prevPage');
  const nextBtn = document.getElementById('nextPage');
  
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        updatePagination();
      }
    });
  }
  
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      const totalPages = Math.ceil(totalRecords / recordsPerPage);
      if (currentPage < totalPages) {
        currentPage++;
        updatePagination();
      }
    });
  }

  updatePagination();
});

// Show help function
function showHelp() {
  Swal.fire({
    title: 'Help & Support',
    html: `
      <div style="text-align: left;">
        <h3>Quick Actions Guide</h3>
        <p><strong>View Ticket:</strong> Click the eye icon to view ticket details</p>
        <p><strong>Quick Edit:</strong> Click the edit icon to modify ticket information</p>
        <p><strong>Cancel Ticket:</strong> Click the X icon to cancel a ticket</p>
        <p><strong>Add Feedback:</strong> Click the comment icon to provide feedback</p>
        <br>
        <p>For additional support, please contact the IT department.</p>
      </div>
    `,
    icon: 'info',
    confirmButtonText: 'Got it!',
    width: '600px'
  });
}
</script>
<script src="./js/clarc-action.js"></script>

</body>
</html>
