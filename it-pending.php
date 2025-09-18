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

// Fetch assigned pending tickets
$sql = "
    SELECT t.ticket_id, t.subject, t.description,
           p.level_name AS priority, s.status_name AS status,
           t.created_at, u.username
    FROM tickets t
    JOIN users u ON t.username = u.username
    JOIN priority_levels p ON t.priority_id = p.id
    JOIN ticket_statuses s ON t.status_id = s.id
    WHERE t.assigned_to = ? AND s.status_name = 'Pending'
    ORDER BY t.ticket_id DESC
";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result === false) {
    die("Error executing query: " . $conn->error);
}
$tickets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch pending ticket notifications
$notif_sql = "
    SELECT tickets.ticket_id, ticket_statuses.status_name, tickets.created_at
    FROM tickets
    JOIN ticket_statuses ON tickets.status_id = ticket_statuses.id
    WHERE ticket_statuses.status_name = 'Pending'
    ORDER BY tickets.created_at DESC
    LIMIT 5
";
$notif_result = $conn->query($notif_sql);
$notifications = $notif_result->fetch_all(MYSQLI_ASSOC);

// Count of new pending tickets for bell badge
$new_ticket_count_sql = "
    SELECT COUNT(*) AS new_count
    FROM tickets
    WHERE status_id = (SELECT id FROM ticket_statuses WHERE status_name='Pending')
";
$new_ticket_count = $conn->query($new_ticket_count_sql)->fetch_assoc()['new_count'];

// Fetch sidebar statistics for IT staff (pending tickets specific)
$stats_sql = "
    SELECT
        COUNT(*) as total_tickets,
        SUM(CASE WHEN s.status_name = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN s.status_name = 'Resolved' THEN 1 ELSE 0 END) as resolved_count,
        SUM(CASE WHEN s.status_name = 'In Progress' THEN 1 ELSE 0 END) as in_progress_count
    FROM tickets t
    LEFT JOIN ticket_statuses s ON t.status_id = s.id
    WHERE t.assigned_to = ?
";
$stmt = $conn->prepare($stats_sql);
if ($stmt === false) {
    die("Error preparing stats query: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$stats_result = $stmt->get_result();
if ($stats_result === false) {
    die("Error executing stats query: " . $conn->error);
}
$stats = $stats_result->fetch_assoc();
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
  <title>TIKSUMA Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="icon" type="image/x-icon" href="./png/logo-favicon.ico" />
  <link rel="stylesheet" href="./src/it-staff.css" />
  <style>
    .priority-badge {
      padding: 4px 8px;
      border-radius: 12px;
      color: white;
      font-size: 0.9em;
      text-transform: uppercase;
    }
    .priority-low { background: green; }
    .priority-medium { background: orange; }
    .priority-high { background: red; }
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
    /* Notification Dropdown Styles */
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
        <div class="notif-title">Pending Ticket*</div>
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
          <p class="notif-empty">No pending tickets.</p>
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
            <a href="it-dashboard.php" class="sidebar-link ">
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
            <a href="it-pending.php" class="sidebar-link active">
              <i class="fas fa-clock"></i>
              <span>Pending</span>
              <span class="sidebar-badge"><?= $stats['pending_count'] ?></span>
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
    <section>
    <?php
    // Prepare tickets data for the template
    $formattedTickets = [];
    foreach ($tickets as $ticket) {
        $formattedTickets[] = [
            'ticket_id' => $ticket['ticket_id'],
            'subject' => $ticket['subject'],
            'priority' => $ticket['priority'],
            'status' => $ticket['status'],
            'created_at' => $ticket['created_at'],
            'username' => $ticket['username']
        ];
    }

    // Configure table for the template
    $tableConfigCustom = [
        'id' => 'ticketsTable',
        'class' => 'ticket-table',
        'showActions' => true,
        'showPriority' => true,
        'showStatus' => true,
        'actionButtons' => ['view', 'reopen', 'add-note', 'pending'],
        'emptyMessage' => 'No pending tickets found.',
        'printButton' => true,
        'searchBox' => true
    ];

    // Include the table template
    include './templates/table-template-main-content.php';
    ?>
  </section>
</div>


<!-- SweetAlert & JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tickets = <?= json_encode($tickets) ?>;

  // Show SweetAlert if no tickets exist
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

  // Row click to view ticket
  document.querySelectorAll('#ticketsTable tbody tr').forEach(row => {
    row.addEventListener('click', () => {
      const id = row.getAttribute('data-id');
      window.location.href = `view-ticket.php?id=${id}`;
    });
  });

  // Profile dropdown toggle
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

  // Sidebar close button
  const closeBtn = document.querySelector('.sidebar-close-btn');
  if (closeBtn) {
    closeBtn.addEventListener('click', toggleSidebar);
  }
});
</script>

<script src="./js/it-staff.js"></script>

</body>
</html>
