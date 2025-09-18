<?php
session_start();

// Database connection
$host = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'tiksumadb';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch user profile picture
$user_sql = "SELECT profile_picture FROM users WHERE username = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$profilePicture = $user_data['profile_picture'] ?: 'default-profile.png';
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $username = $_SESSION['username'];
    $description = $_POST['description'];
    $category = $_POST['category'] ?? 'general';
    $priority = $_POST['priority'] ?? 'medium';
    $filename = '';

    // Handle file upload with enhanced validation
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $fileType = $_FILES['attachment']['type'];
        $fileSize = $_FILES['attachment']['size'];
        
        if (in_array($fileType, $allowedTypes) && $fileSize <= 5 * 1024 * 1024) { // 5MB limit
            $filename = basename($_FILES['attachment']['name']);
            $targetFile = $uploadDir . time() . '_' . $filename;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
                $filename = $targetFile;
            } else {
                $filename = '';
            }
        } else {
            echo "<script>alert('Invalid file type or size. Please upload images, PDF, or Word documents under 5MB.');</script>";
        }
    }

    // Save to database with enhanced fields
    $stmt = $conn->prepare("INSERT INTO feedbacks (username, description, category, priority, attachment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $username, $description, $category, $priority, $filename);

    if ($stmt->execute()) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
              <script>
                Swal.fire({
                    title: 'Thank you!',
                    text: 'Your feedback has been submitted successfully.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'clarc-feedback.php';
                });
              </script>";
    } else {
        echo "<script>
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to submit feedback. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
              </script>";
    }

    $stmt->close();
}

// Fetch recent notifications
$notif_sql = "
    SELECT tickets.ticket_id, ticket_statuses.status_name, tickets.created_at 
    FROM tickets
    JOIN ticket_statuses ON tickets.status_id = ticket_statuses.id 
    WHERE tickets.username = ?
    ORDER BY tickets.created_at DESC 
    LIMIT 5
";
$stmt = $conn->prepare($notif_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user's previous feedback count
$feedback_count_sql = "SELECT COUNT(*) as count FROM feedbacks WHERE username = ?";
$stmt = $conn->prepare($feedback_count_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$feedback_result = $stmt->get_result();
$feedback_data = $feedback_result->fetch_assoc();
$previousFeedbackCount = $feedback_data['count'];
$stmt->close();

// Fetch sidebar statistics (same as clarc-my-ticket.php)
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

// Fetch user's tickets count for sidebar badge
$tickets_sql = "SELECT COUNT(*) as count FROM tickets WHERE username = ?";
$stmt = $conn->prepare($tickets_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$tickets_result = $stmt->get_result();
$tickets_data = $tickets_result->fetch_assoc();
$ticketCount = $tickets_data['count'];
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TIKSUMA - Send Feedback</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="./src/feedback.css" />
    <link rel="icon" type="image/x-icon" href="./png/logo-favicon.ico" />

  <style>
    /* Additional enhanced styles while preserving original design */
    .feedback-stats {
      background: rgba(255, 255, 255, 0.1);
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      text-align: center;
    }
    
    .feedback-stats span {
      color: #065fd4;
      font-weight: bold;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-row {
      display: flex;
      gap: 15px;
      margin-bottom: 15px;
    }
    
    .form-row .form-group {
      flex: 1;
      margin-bottom: 0;
    }
    
    select {
      width: 100%;
      padding: 10px;
      border: 1px solid #aaa;
      border-radius: 10px;
      font-size: 1rem;
      background: #f2f2f2;
    }
    
    .char-counter {
      text-align: right;
      font-size: 0.9rem;
      color: #666;
      margin-top: 5px;
    }
    
    .drag-drop-area {
      border: 2px dashed #ccc;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .drag-drop-area:hover {
      border-color: #065fd4;
      background-color: #f0f8ff;
    }
    
    .drag-drop-area.dragover {
      border-color: #065fd4;
      background-color: #e6f0ff;
    }
    
    .file-info {
      margin-top: 10px;
      font-size: 0.9rem;
      color: #666;
    }
    
    .loading-spinner {
      display: none;
      text-align: center;
      margin-top: 10px;
    }
    
    .spinner {
      border: 4px solid #f3f3f3;
      border-top: 4px solid #065fd4;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      animation: spin 1s linear infinite;
      margin: 0 auto;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
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
    
    <div class="notification-container">
      <i class="fas fa-bell" id="notificationBell"></i>
<div class="notification-dropdown" id="notificationDropdown">
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
              <span class="sidebar-badge" id="ticketCount"><?= $ticketCount ?></span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="clarc-action.php" class="sidebar-link">
              <i class="fas fa-bolt"></i>
              <span>Quick Actions</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="clarc-feedback.php" class="sidebar-link active">
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

<!-- Feedback Form UI -->
<div class="main-container" id="mainContent">
  <div class="feedback-card">
    <h2>TIKSUMA</h2>
    <p>Send feedback to us</p>
    
    <!-- Enhanced feedback stats -->
    <div class="feedback-stats">
      <i class="fas fa-history"></i> You have submitted <span><?= $previousFeedbackCount ?></span> feedback(s) previously
    </div>
    
    <form action="clarc-feedback.php" method="POST" enctype="multipart/form-data" id="feedbackForm">
      <!-- Enhanced form with categories and priority -->
      <div class="form-row">
        <div class="form-group">
          <label for="category">Category</label>
          <select name="category" id="category" required>
            <option value="general">General Feedback</option>
            <option value="bug">Bug Report</option>
            <option value="feature">Feature Request</option>
            <option value="ui">UI/UX Suggestion</option>
            <option value="performance">Performance Issue</option>
            <option value="other">Other</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="priority">Priority</label>
          <select name="priority" id="priority" required>
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>
      
      <label for="description">Describe your feedback</label>
      <textarea name="description" id="description" required 
                placeholder="Please provide detailed feedback about your experience, suggestions, or any issues you've encountered..."
                maxlength="1000"></textarea>
      <div class="char-counter"><span id="charCount">0</span>/1000 characters</div>

      <!-- Enhanced file upload with drag and drop -->
      <div class="form-group">
        <label>Attachment (Optional)</label>
        <div class="drag-drop-area" id="dragDropArea">
          <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #065fd4;"></i>
          <p>Drag & drop files here or <span style="color: #065fd4;">browse</span></p>
          <p style="font-size: 0.8rem; color: #666;">Supports: Images, PDF, Word (Max 5MB)</p>
          <input type="file" name="attachment" id="fileInput" style="display: none;" accept="image/*,.pdf,.doc,.docx" />
        </div>
        <div class="file-info" id="fileInfo"></div>
      </div>

      <button type="submit" name="submit" class="submit-btn" id="submitBtn">
        <i class="fas fa-paper-plane"></i> Submit Feedback
      </button>
      
      <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
        <p>Submitting your feedback...</p>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./src/clarendon-staff.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Sidebar toggle function
  window.toggleSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    const mainContainer = document.querySelector('.main-container');
    if (sidebar && mainContainer) {
      sidebar.classList.toggle('closed');
      mainContainer.classList.toggle('expanded');
    }
  };

  // Profile dropdown
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

  // Bell dropdown
  const bellIcon = document.getElementById('notificationBell');
  const dropdown = document.getElementById('notificationDropdown');
  if (bellIcon && dropdown) {
    bellIcon.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('show');
    });
    window.addEventListener('click', function(e) {
      if (!dropdown.contains(e.target) && !bellIcon.contains(e.target)) {
        dropdown.classList.remove('show');
      }
    });
  }

  // Logout SweetAlert
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

  // Enhanced form functionality
  const form = document.getElementById('feedbackForm');
  const description = document.getElementById('description');
  const charCount = document.getElementById('charCount');
  const submitBtn = document.getElementById('submitBtn');
  const loadingSpinner = document.getElementById('loadingSpinner');
  const dragDropArea = document.getElementById('dragDropArea');
  const fileInput = document.getElementById('fileInput');
  const fileInfo = document.getElementById('fileInfo');

  // Character counter
  if (description && charCount) {
    description.addEventListener('input', () => {
      const count = description.value.length;
      charCount.textContent = count;
      if (count > 900) {
        charCount.style.color = '#d33';
      } else {
        charCount.style.color = '#666';
      }
    });
  }

  // Drag and drop functionality
  if (dragDropArea && fileInput) {
    dragDropArea.addEventListener('click', () => fileInput.click());
    
    dragDropArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      dragDropArea.classList.add('dragover');
    });
    
    dragDropArea.addEventListener('dragleave', () => {
      dragDropArea.classList.remove('dragover');
    });
    
    dragDropArea.addEventListener('drop', (e) => {
      e.preventDefault();
      dragDropArea.classList.remove('dragover');
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        fileInput.files = files;
        displayFileInfo(files[0]);
      }
    });
    
    fileInput.addEventListener('change', (e) => {
      if (e.target.files.length > 0) {
        displayFileInfo(e.target.files[0]);
      }
    });
  }

  function displayFileInfo(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
      Swal.fire('Error', 'File size exceeds 5MB limit', 'error');
      fileInput.value = '';
      fileInfo.innerHTML = '';
      return;
    }
    
    fileInfo.innerHTML = `
      <i class="fas fa-file"></i> ${file.name} 
      <span style="color: #666;">(${formatFileSize(file.size)})</span>
      <button type="button" onclick="removeFile()" style="background: none; border: none; color: #d33; cursor: pointer;">
        <i class="fas fa-times"></i>
      </button>
    `;
  }

  window.removeFile = function() {
    fileInput.value = '';
    fileInfo.innerHTML = '';
  };

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  // Form validation and submission
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const descriptionValue = description.value.trim();
      if (!descriptionValue) {
        Swal.fire('Error', 'Please provide a description for your feedback', 'error');
        return;
      }
      
      if (descriptionValue.length < 10) {
        Swal.fire('Error', 'Please provide at least 10 characters for your feedback', 'error');
        return;
      }

      // Show loading spinner
      submitBtn.style.display = 'none';
      loadingSpinner.style.display = 'block';

      // Submit form after short delay for UX
      setTimeout(() => {
        form.submit();
      }, 1000);
    });
  }

  // Help function
  window.showHelp = function() {
    Swal.fire({
      title: 'Help & Support',
      text: 'For assistance, please contact the IT support team or submit a feedback ticket.',
      icon: 'info',
      confirmButtonText: 'OK'
    });
  };
});
</script>

</body>
</html>
