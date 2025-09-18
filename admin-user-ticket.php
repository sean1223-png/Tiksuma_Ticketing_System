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

// Fetch user's tickets
$sql = "
    SELECT t.ticket_id, t.subject, 
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

// Fetch 5 most recent accepted ticket updates
$notif_sql = "
    SELECT tickets.ticket_id, ticket_statuses.status_name, tickets.updated_at 
    FROM tickets
    JOIN ticket_statuses ON tickets.status_id = ticket_statuses.id 
    WHERE tickets.username = ? AND ticket_statuses.status_name = 'Accepted'
    ORDER BY tickets.updated_at DESC 
    LIMIT 5
";
$stmt = $conn->prepare($sql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$tickets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>TIKSUMA Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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

  <div class="topbar-center">
    <div class="search-container">
      <input type="text" class="search-box" id="searchInput" placeholder="Search" />
      <button class="search-button" onclick="performSearch()">
        <i class="fas fa-search"></i>
      </button>
    </div>
  </div>

  
<div class="topbar-right">
  <button class="print" onclick="()">PRINT</button>

    <div class="notification-container">
      <i class="fas fa-bell" id="notificationBell"></i>
<div class="notification-dropdown" id="notificationDropdown">
  <?php if (!empty($notifications)): ?>
    <ul class="notif-list">
      <?php foreach ($notifications as $note): ?>
        <li>
          <strong>Ticket #<?= $note['ticket_id'] ?></strong> was <span style="color:green"><?= $note['status_name'] ?></span><br>
          <small><?= date('M d, Y h:i A', strtotime($note['updated_at'])) ?></small>
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
      <li><a href="view-it-profile.php">View Profile</a></li>
      <li><a href="#" id="logoutBtn">Logout</a></li>
    </ul>
  </div>
</div>
  </div>
</div>


  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
     <ul>
    <li><a href="admin-dashboards.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
    <li><a href="admin-user-ticket.php"><i class="fas fa-envelope-open-text"></i> User Ticket Submit</a></li>
    <li><a href="admin-archive.php"><i class="fas fa-archive"></i> Archive Tickets</a></li>
    <li><a href="admin-users.php"><i class="fas fa-users"></i> Users</a></li>
    <li><a href="admin-reports.php"><i class="fas fa-file-alt"></i> Ticket Reports</a></li>
  </ul>
<<!-- Main Content -->
<div class="main-content">
   <button class="print-btn" onclick="window.print()">Print</button>
  <section>
    <table class="ticket-table" id="ticketsTable">
      <thead>
        <tr>
          <th>Ticket ID</th><th>Subject</th><th>Priority</th><th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($tickets): ?>
          <?php foreach ($tickets as $row): 
            $pri = strtolower($row['priority']);
          ?>
            <tr data-id="<?= htmlspecialchars($row['ticket_id']) ?>">
              <td><?= htmlspecialchars($row['ticket_id']) ?></td>
              <td><?= htmlspecialchars($row['subject']) ?></td>
              <td><span class="priority-badge priority-<?= $pri ?>"><?= htmlspecialchars($row['priority']) ?></span></td>
              <td><?= htmlspecialchars($row['status']) ?></td>
              <td><a href="view-ticket.php?id=<?= urlencode($row['ticket_id']) ?>" class="view-btn">View</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
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
});
</script>

</script>
<script src="./js/it-staff.js"></script>

</body>
</html>
